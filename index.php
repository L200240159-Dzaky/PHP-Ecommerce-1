<?php
require_once 'db.php';

// untuk menambah produk belanja ke keranjang
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    if (!isLoggedIn()) {
        setFlash('Please login to add items to cart', 'warning');
        header('Location: login.php');
        exit;
    }

    $product_id = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity']) ?? 1;

    // untuk validasi apakah produk masih tersedia dan jumlah yang diminta tidak melebihi stok yang ada
    $stmt = $pdo->prepare('SELECT id, stock FROM products WHERE id = ?');
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();

    if (!$product) {
        setFlash('Product not found', 'danger');
    } elseif ($quantity > $product['stock']) {
        setFlash('Insufficient stock available', 'danger');
    } elseif ($quantity < 1) {
        setFlash('Invalid quantity', 'danger');
    } else {
        // mengecek apakah produk sudah ada di keranjang pengguna, jika sudah maka update jumlahnya, jika belum maka tambahkan sebagai item baru di keranjang
        $stmt = $pdo->prepare('SELECT id, quantity FROM carts WHERE user_id = ? AND product_id = ?');
        $stmt->execute([$_SESSION['user_id'], $product_id]);
        $cartItem = $stmt->fetch();

        if ($cartItem) {
            // mengupdate jumlah produk
            $newQuantity = $cartItem['quantity'] + $quantity;
            if ($newQuantity > $product['stock']) {
                setFlash('Cannot exceed available stock', 'danger');
            } else {
                $stmt = $pdo->prepare('UPDATE carts SET quantity = ? WHERE id = ?');
                $stmt->execute([$newQuantity, $cartItem['id']]);
                setFlash('Product quantity updated in cart', 'success');
            }
        } else {
            // menambah item baru ke keranjang
            $stmt = $pdo->prepare('INSERT INTO carts (user_id, product_id, quantity) VALUES (?, ?, ?)');
            if ($stmt->execute([$_SESSION['user_id'], $product_id, $quantity])) {
                setFlash('Product added to cart', 'success');
            }
        }
    }

    header('Location: index.php');
    exit;
}

// buat mengambil semua produk dari database untuk ditampilkan di halaman utama
$stmt = $pdo->query('SELECT id, name, description, price, stock, image FROM products ORDER BY name ASC');
$products = $stmt->fetchAll();

// buatmenghitung jumlah item di keranjang untuk ditampilkan di navbar jika pengguna sudah login
$cartCount = 0;
if (isLoggedIn()) {
    $stmt = $pdo->prepare('SELECT SUM(quantity) as total FROM carts WHERE user_id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $result = $stmt->fetch();
    $cartCount = $result['total'] ?? 0;
}

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .navbar-custom {
            background-color: #2c3e50;
        }
        .product-card {
            transition: transform 0.2s, box-shadow 0.2s;
            height: 100%;
        }
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
        }
        .product-image {
            height: 250px;
            object-fit: cover;
            background-color: #f8f9fa;
        }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php"><?php echo SITE_NAME; ?></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="cart.php">
                                🛒 Cart <span class="badge bg-danger"><?php echo $cartCount; ?></span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="history.php">📋 Order History</a>
                        </li>
                        <?php if (isAdmin()): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="admin.php">⚙️ Admin Panel</a>
                            </li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">👋 Logout (<?php echo $_SESSION['user_name']; ?>)</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="register.php">Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <?php if ($flash): ?>
            <div class="alert alert-<?php echo htmlspecialchars($flash['type']); ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($flash['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <h1 class="mb-4">Our Products</h1>

        <?php if (empty($products)): ?>
            <div class="alert alert-info">No products available at the moment.</div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($products as $product): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card product-card shadow-sm">
                            <?php 
                            $imageSrc = 'uploads/' . (empty($product['image']) || !file_exists('uploads/' . $product['image']) ? 'default.png' : $product['image']);
                            ?>
                            <img src="<?php echo htmlspecialchars($imageSrc); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="card-img-top product-image">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                                <p class="card-text text-muted flex-grow-1"><?php echo htmlspecialchars(substr($product['description'], 0, 100)) . '...'; ?></p>
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="h5 mb-0 text-primary">$<?php echo number_format($product['price'], 2); ?></span>
                                    <span class="badge bg-<?php echo $product['stock'] > 0 ? 'success' : 'danger'; ?>">
                                        <?php echo $product['stock'] > 0 ? $product['stock'] . ' in stock' : 'Out of stock'; ?>
                                    </span>
                                </div>

                                <?php if (isLoggedIn() && $product['stock'] > 0): ?>
                                    <form method="POST" action="" class="mt-auto">
                                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                        <div class="input-group mb-2">
                                            <input type="number" class="form-control" name="quantity" value="1" min="1" max="<?php echo $product['stock']; ?>">
                                            <button type="submit" name="add_to_cart" class="btn btn-primary">Add to Cart</button>
                                        </div>
                                    </form>
                                <?php elseif (!isLoggedIn()): ?>
                                    <a href="login.php" class="btn btn-outline-primary w-100">Login to Buy</a>
                                <?php else: ?>
                                    <button class="btn btn-secondary w-100" disabled>Out of Stock</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
