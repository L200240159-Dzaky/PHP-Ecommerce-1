<?php
require_once 'db.php';

// Require login
requireLogin();

// Fetch user's transactions
$stmt = $pdo->prepare('
    SELECT id, total_price, status, created_at
    FROM transactions
    WHERE user_id = ?
    ORDER BY created_at DESC
');
$stmt->execute([$_SESSION['user_id']]);
$transactions = $stmt->fetchAll();

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order History - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .navbar-custom {
            background-color: #2c3e50;
        }
        .transaction-row:hover {
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
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">🛍️ Products</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="cart.php">🛒 Cart</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="history.php">📋 Order History</a>
                    </li>
                    <?php if (isAdmin()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="admin.php">⚙️ Admin Panel</a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">👋 Logout</a>
                    </li>
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

        <h1 class="mb-4">📋 Order History</h1>

        <?php if (empty($transactions)): ?>
            <div class="alert alert-info text-center">
                <p>You haven't placed any orders yet.</p>
                <a href="index.php" class="btn btn-primary">Start Shopping</a>
            </div>
        <?php else: ?>
            <div class="card shadow-sm">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Order ID</th>
                                <th>Date</th>
                                <th>Items</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $transaction): ?>
                                <?php
                                // Fetch items for this transaction
                                $itemStmt = $pdo->prepare('
                                    SELECT ti.id, ti.product_id, ti.quantity, ti.price, p.name
                                    FROM transaction_items ti
                                    LEFT JOIN products p ON ti.product_id = p.id
                                    WHERE ti.transaction_id = ?
                                ');
                                $itemStmt->execute([$transaction['id']]);
                                $items = $itemStmt->fetchAll();
                                ?>
                                <tr class="transaction-row" data-bs-toggle="collapse" data-bs-target="#order-<?php echo $transaction['id']; ?>" role="button">
                                    <td><strong>#<?php echo $transaction['id']; ?></strong></td>
                                    <td><?php echo date('M d, Y H:i', strtotime($transaction['created_at'])); ?></td>
                                    <td><span class="badge bg-info"><?php echo count($items); ?> item(s)</span></td>
                                    <td><strong class="text-primary">$<?php echo number_format($transaction['total_price'], 2); ?></strong></td>
                                    <td>
                                        <span class="badge bg-<?php echo $transaction['status'] === 'completed' ? 'success' : 'warning'; ?>">
                                            <?php echo ucfirst($transaction['status']); ?>
                                        </span>
                                    </td>
                                    <td><small class="text-muted">Click to expand</small></td>
                                </tr>
                                <tr class="collapse" id="order-<?php echo $transaction['id']; ?>">
                                    <td colspan="6" class="bg-light p-4">
                                        <h6 class="mb-3">Order Details</h6>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-borderless">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Product</th>
                                                        <th>Quantity</th>
                                                        <th>Price</th>
                                                        <th>Subtotal</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($items as $item): ?>
                                                        <tr>
                                                            <td>
                                                                <?php
                                                                if ($item['name']) {
                                                                    echo htmlspecialchars($item['name']);
                                                                } else {
                                                                    echo '<em class="text-muted">Product removed</em>';
                                                                }
                                                                ?>
                                                            </td>
                                                            <td><?php echo $item['quantity']; ?></td>
                                                            <td>$<?php echo number_format($item['price'], 2); ?></td>
                                                            <td><strong>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></strong></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
