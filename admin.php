<?php
require_once 'db.php';

// Require admin role
requireAdmin();

$flash = getFlash();

// Handle add/update product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_product'])) {
    $product_id = !empty($_POST['product_id']) ? intval($_POST['product_id']) : null;
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $stock = intval($_POST['stock'] ?? 0);

    if (empty($name) || $price <= 0 || $stock < 0) {
        setFlash('Please fill all required fields correctly', 'danger');
    } else {
        // Determine the image to use
        $image = 'default.png';
        if ($product_id) {
            // Get existing image
            $stmt = $pdo->prepare('SELECT image FROM products WHERE id = ?');
            $stmt->execute([$product_id]);
            $currentProduct = $stmt->fetch();
            if ($currentProduct) {
                $image = $currentProduct['image'];
            }
        }

        // Handle file upload
        if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['product_image']['tmp_name'];
            $fileName = $_FILES['product_image']['name'];
            $fileNameCmps = explode(".", $fileName);
            $fileExtension = strtolower(end($fileNameCmps));

            $allowedExtensions = ['png', 'jpg', 'jpeg'];
            if (in_array($fileExtension, $allowedExtensions)) {
                // Sanitize and rename using timestamp prefix
                $cleanFileName = preg_replace("/[^a-zA-Z0-9_.-]/", "", $fileName);
                $newFileName = time() . '_' . $cleanFileName;
                $uploadFileDir = 'uploads/';
                $dest_path = $uploadFileDir . $newFileName;
                if (move_uploaded_file($fileTmpPath, $dest_path)) {
                    $image = $newFileName;
                } else {
                    setFlash('Failed to move uploaded file', 'danger');
                }
            } else {
                setFlash('Upload failed. Allowed extensions: ' . implode(', ', $allowedExtensions), 'danger');
            }
        }

        if ($product_id) {
            // Update existing product
            $stmt = $pdo->prepare('UPDATE products SET name = ?, description = ?, price = ?, stock = ?, image = ? WHERE id = ?');
            if ($stmt->execute([$name, $description, $price, $stock, $image, $product_id])) {
                setFlash('Product updated successfully', 'success');
            } else {
                setFlash('Failed to update product', 'danger');
            }
        } else {
            // Insert new product
            $stmt = $pdo->prepare('INSERT INTO products (name, description, price, stock, image) VALUES (?, ?, ?, ?, ?)');
            if ($stmt->execute([$name, $description, $price, $stock, $image])) {
                setFlash('Product added successfully', 'success');
            } else {
                setFlash('Failed to add product', 'danger');
            }
        }
    }

    header('Location: admin.php');
    exit;
}

// Handle delete product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_product'])) {
    $product_id = intval($_POST['product_id']);
    $stmt = $pdo->prepare('DELETE FROM products WHERE id = ?');
    if ($stmt->execute([$product_id])) {
        setFlash('Product deleted successfully', 'success');
    } else {
        setFlash('Failed to delete product', 'danger');
    }
    header('Location: admin.php');
    exit;
}

// Fetch all transactions with user info
$stmt = $pdo->query('
    SELECT t.id, t.user_id, t.total_price, t.status, t.created_at, u.name, u.email
    FROM transactions t
    JOIN users u ON t.user_id = u.id
    ORDER BY t.created_at DESC
    LIMIT 50
');
$transactions = $stmt->fetchAll();

// Fetch all products
$stmt = $pdo->query('SELECT id, name, description, price, stock, image FROM products ORDER BY name ASC');
$products = $stmt->fetchAll();

// Get edit product if specified
$editProduct = null;
if (isset($_GET['edit'])) {
    $editId = intval($_GET['edit']);
    $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
    $stmt->execute([$editId]);
    $editProduct = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .navbar-custom {
            background-color: #2c3e50;
        }
        .admin-section {
            margin-bottom: 3rem;
        }
        .product-image-thumb {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
        }
        .transaction-table {
            font-size: 0.95rem;
        }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="index.php"><?php echo SITE_NAME; ?> - Admin</a>
            <div class="ms-auto">
                <span class="text-light me-3">Welcome, <?php echo $_SESSION['user_name']; ?></span>
                <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid my-5">
        <?php if ($flash): ?>
            <div class="alert alert-<?php echo htmlspecialchars($flash['type']); ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($flash['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Transactions Section -->
        <div class="admin-section">
            <div class="row mb-4">
                <div class="col-12">
                    <h2>📊 Recent Transactions</h2>
                    <p class="text-muted">All orders across all users (last 50)</p>
                </div>
            </div>

            <?php if (empty($transactions)): ?>
                <div class="alert alert-info">No transactions yet.</div>
            <?php else: ?>
                <div class="card shadow-sm">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm transaction-table mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Email</th>
                                    <th>Items</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($transactions as $transaction): ?>
                                    <?php
                                    // Count items in this transaction
                                    $itemStmt = $pdo->prepare('SELECT COUNT(*) as count FROM transaction_items WHERE transaction_id = ?');
                                    $itemStmt->execute([$transaction['id']]);
                                    $itemCount = $itemStmt->fetch()['count'];
                                    ?>
                                    <tr>
                                        <td><strong>#<?php echo $transaction['id']; ?></strong></td>
                                        <td><?php echo htmlspecialchars($transaction['name']); ?></td>
                                        <td><small><?php echo htmlspecialchars($transaction['email']); ?></small></td>
                                        <td><span class="badge bg-info"><?php echo $itemCount; ?></span></td>
                                        <td><strong class="text-success">$<?php echo number_format($transaction['total_price'], 2); ?></strong></td>
                                        <td>
                                            <span class="badge bg-<?php echo $transaction['status'] === 'completed' ? 'success' : 'warning'; ?>">
                                                <?php echo ucfirst($transaction['status']); ?>
                                            </span>
                                        </td>
                                        <td><small><?php echo date('M d, Y H:i', strtotime($transaction['created_at'])); ?></small></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <hr class="my-5">

        <!-- Product Management Section -->
        <div class="admin-section">
            <div class="row mb-4">
                <div class="col-12">
                    <h2>📦 Product Management</h2>
                    <p class="text-muted"><?php echo count($products); ?> products in catalog</p>
                </div>
            </div>

            <div class="row">
                <!-- Product Form -->
                <div class="col-lg-5 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo $editProduct ? 'Edit Product' : 'Add New Product'; ?></h5>
                            <form method="POST" action="" enctype="multipart/form-data">
                                <?php if ($editProduct): ?>
                                    <input type="hidden" name="product_id" value="<?php echo $editProduct['id']; ?>">
                                <?php endif; ?>

                                <div class="mb-3">
                                    <label for="name" class="form-label">Product Name *</label>
                                    <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($editProduct['name'] ?? ''); ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($editProduct['description'] ?? ''); ?></textarea>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="price" class="form-label">Price ($) *</label>
                                            <input type="number" class="form-control" id="price" name="price" step="0.01" min="0" value="<?php echo htmlspecialchars($editProduct['price'] ?? ''); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="stock" class="form-label">Stock *</label>
                                            <input type="number" class="form-control" id="stock" name="stock" min="0" value="<?php echo htmlspecialchars($editProduct['stock'] ?? ''); ?>" required>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="product_image" class="form-label">Product Image</label>
                                    <input type="file" class="form-control" id="product_image" name="product_image" accept="image/png, image/jpeg, image/jpg">
                                    <?php if ($editProduct && !empty($editProduct['image'])): ?>
                                        <div class="mt-2">
                                            <small class="text-muted">Current image:</small><br>
                                            <img src="uploads/<?php echo htmlspecialchars($editProduct['image']); ?>" alt="Current Image" class="product-image-thumb mt-1">
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="d-grid gap-2">
                                    <button type="submit" name="save_product" class="btn btn-primary">
                                        <?php echo $editProduct ? 'Update Product' : 'Add Product'; ?>
                                    </button>
                                    <?php if ($editProduct): ?>
                                        <a href="admin.php" class="btn btn-secondary">Cancel</a>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Product List -->
                <div class="col-lg-7">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title mb-3">All Products</h5>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th></th>
                                            <th>Name</th>
                                            <th>Price</th>
                                            <th>Stock</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($products as $product): ?>
                                            <tr>
                                                <td>
                                                    <?php 
                                                    $imageSrc = 'uploads/' . (empty($product['image']) || !file_exists('uploads/' . $product['image']) ? 'default.png' : $product['image']);
                                                    ?>
                                                    <img src="<?php echo htmlspecialchars($imageSrc); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-image-thumb">
                                                </td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($product['name']); ?></strong><br>
                                                    <small class="text-muted"><?php echo htmlspecialchars(substr($product['description'], 0, 50)); ?></small>
                                                </td>
                                                <td>$<?php echo number_format($product['price'], 2); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $product['stock'] > 10 ? 'success' : ($product['stock'] > 0 ? 'warning' : 'danger'); ?>">
                                                        <?php echo $product['stock']; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="admin.php?edit=<?php echo $product['id']; ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this product?');">
                                                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                        <button type="submit" name="delete_product" class="btn btn-sm btn-outline-danger">Delete</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
