<?php
require_once 'db.php';

// user sudah login
requireLogin();

// untuk menghapus item dari cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_item'])) {
    $cart_id = intval($_POST['cart_id']);
    $stmt = $pdo->prepare('DELETE FROM carts WHERE id = ? AND user_id = ?');
    $stmt->execute([$cart_id, $_SESSION['user_id']]);
    setFlash('Item removed from cart', 'success');
    header('Location: cart.php');
    exit;
}

// untuk memperbarui jumlah item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_quantity'])) {
    $cart_id = intval($_POST['cart_id']);
    $quantity = intval($_POST['quantity']);

    if ($quantity < 1) {
        setFlash('Invalid quantity', 'danger');
    } else {
        // mengecek stok produk sebelum memperbarui jumlah item di cart
        $stmt = $pdo->prepare('SELECT p.stock FROM carts c JOIN products p ON c.product_id = p.id WHERE c.id = ?');
        $stmt->execute([$cart_id]);
        $result = $stmt->fetch();

        if ($result && $quantity > $result['stock']) {
            setFlash('Cannot exceed available stock', 'danger');
        } else {
            $stmt = $pdo->prepare('UPDATE carts SET quantity = ? WHERE id = ? AND user_id = ?');
            $stmt->execute([$quantity, $cart_id, $_SESSION['user_id']]);
            setFlash('Quantity updated', 'success');
        }
    }
    header('Location: cart.php');
    exit;
}

// buat transaksi baru saat checkout 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout'])) {
    try {
        $pdo->beginTransaction();

        // mengambil semua barang yang ada di keranjang, lalu memastikan keranjang tidak kosong. 
        $stmt = $pdo->prepare('
            SELECT c.id, c.product_id, c.quantity, p.price, p.stock
            FROM carts c
            JOIN products p ON c.product_id = p.id
            WHERE c.user_id = ?
        ');
        $stmt->execute([$_SESSION['user_id']]);
        $cartItems = $stmt->fetchAll();

        if (empty($cartItems)) {
            throw new Exception('Cart is empty');
        }

        // memvalidasi apakah jumlah barang yang dipesan melebihi stok yang tersedia
        foreach ($cartItems as $item) {
            if ($item['quantity'] > $item['stock']) {
                throw new Exception("Insufficient stock for product ID {$item['product_id']}");
            }
        }

        // menghitung jumlah total harga dari semua barang
        $totalPrice = 0;
        foreach ($cartItems as $item) {
            $totalPrice += $item['price'] * $item['quantity'];
        }

        // menyimpan transaksi ke database dengan status "completed"
        $stmt = $pdo->prepare('INSERT INTO transactions (user_id, total_price, status) VALUES (?, ?, ?)');
        $stmt->execute([$_SESSION['user_id'], $totalPrice, 'completed']);
        $transactionId = $pdo->lastInsertId();

        // menyimpan detail transaksi ke database dan mengurangi stok produk sesuai dengan jumlah yang dipesan
        $stmt = $pdo->prepare('INSERT INTO transaction_items (transaction_id, product_id, quantity, price) VALUES (?, ?, ?, ?)');
        $updateStockStmt = $pdo->prepare('UPDATE products SET stock = stock - ? WHERE id = ?');

        foreach ($cartItems as $item) {
            $stmt->execute([$transactionId, $item['product_id'], $item['quantity'], $item['price']]);
            $updateStockStmt->execute([$item['quantity'], $item['product_id']]);
        }

        // menghapus semua barang dari keranjang setelah checkout berhasil
        $deleteStmt = $pdo->prepare('DELETE FROM carts WHERE user_id = ?');
        $deleteStmt->execute([$_SESSION['user_id']]);

        $pdo->commit();

        setFlash("Order completed successfully! Order ID: #{$transactionId}", 'success');
        header('Location: history.php');
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        setFlash('Checkout failed: ' . $e->getMessage(), 'danger');
        header('Location: cart.php');
        exit;
    }
}

// mengambil semua barang yang ada di keranjang beserta informasi produk untuk ditampilkan di halaman cart
$stmt = $pdo->prepare('
    SELECT c.id, c.product_id, c.quantity, p.name, p.price, p.stock, p.image
    FROM carts c
    JOIN products p ON c.product_id = p.id
    WHERE c.user_id = ?
    ORDER BY c.added_at DESC
');
$stmt->execute([$_SESSION['user_id']]);
$cartItems = $stmt->fetchAll();

// menghitung subtotal, pajak, dan total harga untuk ditampilkan di halaman cart
$subtotal = 0;
foreach ($cartItems as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

$tax = round($subtotal * 0.1, 2); // 10% tax
$total = $subtotal + $tax;

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .navbar-custom {
            background-color: #2c3e50;
        }
        .cart-item-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 4px;
        }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php"><?php echo SITE_NAME; ?></a>
            <div class="ms-auto">
                <a href="index.php" class="btn btn-outline-light btn-sm">← Continue Shopping</a>
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

        <h1 class="mb-4">🛒 Shopping Cart</h1>

        <?php if (empty($cartItems)): ?>
            <div class="alert alert-info text-center">
                <p>Your cart is empty.</p>
                <a href="index.php" class="btn btn-primary">Continue Shopping</a>
            </div>
        <?php else: ?>
            <div class="row">
                <div class="col-lg-8">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <?php foreach ($cartItems as $item): ?>
                                <div class="row align-items-center border-bottom pb-3 mb-3">
                                    <div class="col-md-2">
                                        <?php 
                                        $imageSrc = 'uploads/' . (empty($item['image']) || !file_exists('uploads/' . $item['image']) ? 'default.png' : $item['image']);
                                        ?>
                                        <img src="<?php echo htmlspecialchars($imageSrc); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="cart-item-image">
                                    </div>
                                    <div class="col-md-4">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($item['name']); ?></h6>
                                        <small class="text-muted">$<?php echo number_format($item['price'], 2); ?> each</small>
                                    </div>
                                    <div class="col-md-3">
                                        <form method="POST" class="d-flex gap-1">
                                            <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                                            <input type="number" name="quantity" class="form-control form-control-sm" value="<?php echo $item['quantity']; ?>" min="1" max="<?php echo $item['stock']; ?>" style="width: 70px;">
                                            <button type="submit" name="update_quantity" class="btn btn-sm btn-outline-primary">Update</button>
                                        </form>
                                    </div>
                                    <div class="col-md-2 text-end">
                                        <strong>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></strong>
                                    </div>
                                    <div class="col-md-1 text-end">
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                                            <button type="submit" name="remove_item" class="btn btn-sm btn-danger">Remove</button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title mb-4">Order Summary</h5>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Subtotal:</span>
                                <span>$<?php echo number_format($subtotal, 2); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-3 border-bottom pb-3">
                                <span>Tax (10%):</span>
                                <span>$<?php echo number_format($tax, 2); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-4">
                                <strong>Total:</strong>
                                <strong class="text-primary h5">$<?php echo number_format($total, 2); ?></strong>
                            </div>
                            <form method="POST" action="">
                                <button type="submit" name="checkout" class="btn btn-success w-100 btn-lg">
                                    💳 Proceed to Checkout
                                </button>
                            </form>
                            <a href="index.php" class="btn btn-outline-secondary w-100 mt-2">Continue Shopping</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
