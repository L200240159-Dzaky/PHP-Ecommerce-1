<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';

require_role('admin');

// Fetch all transactions
$statement = db()->query('
    SELECT 
        o.id AS order_id, 
        o.total_price, 
        o.created_at AS order_date,
        u.name AS user_name,
        u.email AS user_email,
        oi.quantity, 
        oi.price AS item_price, 
        p.name AS product_name
    FROM orders o
    JOIN users u ON u.id = o.user_id
    JOIN order_items oi ON oi.order_id = o.id
    LEFT JOIN products p ON p.id = oi.product_id
    ORDER BY o.created_at DESC, oi.id ASC
');
$results = $statement->fetchAll();

// Group results by order_id
$orders = [];
foreach ($results as $row) {
    $orderId = $row['order_id'];
    if (!isset($orders[$orderId])) {
        $orders[$orderId] = [
            'id' => $orderId,
            'user_name' => $row['user_name'],
            'user_email' => $row['user_email'],
            'total_price' => (float) $row['total_price'],
            'order_date' => $row['order_date'],
            'items' => []
        ];
    }
    if ($row['product_name'] !== null) {
        $orders[$orderId]['items'][] = [
            'product_name' => $row['product_name'],
            'quantity' => (int) $row['quantity'],
            'price' => (float) $row['item_price']
        ];
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Transactions - <?= e(APP_NAME) ?></title>
    <style>
        :root {
            color-scheme: light;
            --bg: #fff7ef;
            --panel: rgba(255,255,255,.88);
            --text: #1f1308;
            --muted: #7a5d45;
            --accent: #f97316;
            --accent-dark: #ea580c;
            --accent-soft: #fff1e7;
            --border: #f3dcc7;
            --shadow: 0 24px 70px rgba(249, 115, 22, 0.12);
        }
        * { box-sizing: border-box; }
        body {
            margin: 0; font-family: "Trebuchet MS", "Segoe UI", sans-serif; color: var(--text);
            background:
                radial-gradient(circle at top left, rgba(249, 115, 22, 0.14), transparent 28%),
                linear-gradient(180deg, #fffaf5 0%, #fff4e9 100%);
            min-height: 100vh;
        }
        .wrap { max-width: 1160px; margin: 0 auto; padding: 20px; }
        .topbar {
            display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap;
            margin-bottom: 18px; padding: 14px 16px; border-radius: 18px; background: rgba(255,255,255,.72);
            border: 1px solid var(--border); backdrop-filter: blur(10px);
        }
        .topbar a { color: var(--accent-dark); text-decoration: none; font-weight: 800; margin-right: 15px; }
        .topbar a:last-child { margin-right: 0; }
        .title { margin: 0 0 20px; font-size: clamp(1.7rem, 3vw, 2.4rem); letter-spacing: -0.04em; }
        .panel {
            background: var(--panel); border-radius: 22px; padding: 22px; box-shadow: var(--shadow);
            margin-bottom: 18px; border: 1px solid var(--border);
        }
        .table-wrap { overflow-x: auto; }
        .table { width: 100%; border-collapse: collapse; min-width: 900px; }
        .table th, .table td {
            padding: 14px 10px; border-bottom: 1px solid #f1ddca; text-align: left; vertical-align: top;
        }
        .table th { color: var(--muted); font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.08em; }
        .order-id { font-weight: 800; color: var(--accent-dark); }
        .customer-info { line-height: 1.3; }
        .customer-name { font-weight: 700; }
        .customer-email { font-size: 0.85rem; color: var(--muted); }
        .item-list { list-style: none; padding: 0; margin: 0; }
        .item-desc { margin-bottom: 6px; font-size: 0.95rem; }
        .item-desc:last-child { margin-bottom: 0; }
        .total-price { font-weight: 800; font-size: 1.1rem; }
        .empty {
            padding: 40px; text-align: center; color: var(--muted); font-size: 1.1rem;
        }
        .nav-menu-container {
            margin-top: 10px;
        }
    </style>
</head>
<body>
<div class="wrap">
    <div class="topbar">
        <div>
            <a href="<?= e(url('index.php')) ?>">Back to home</a>
            <a href="<?= e(url('admin/products.php')) ?>">Products</a>
            <a href="<?= e(url('admin/categories.php')) ?>">Categories</a>
        </div>
        <span class="muted">Admin transactions</span>
    </div>

    <h1 class="title">All Transactions</h1>

    <div class="panel">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Purchased Items</th>
                        <th>Total Price</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td class="order-id">#<?= e((string)$order['id']) ?></td>
                        <td>
                            <div class="customer-info">
                                <div class="customer-name"><?= e($order['user_name']) ?></div>
                                <div class="customer-email"><?= e($order['user_email']) ?></div>
                            </div>
                        </td>
                        <td>
                            <div class="item-list">
                                <?php foreach ($order['items'] as $item): ?>
                                    <div class="item-desc">
                                        <strong><?= e($item['product_name']) ?></strong> x<?= e((string)$item['quantity']) ?>
                                        <span class="muted">($<?= e(number_format($item['price'], 2)) ?> each)</span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </td>
                        <td class="total-price">$<?= e(number_format($order['total_price'], 2)) ?></td>
                        <td><?= e(date('F j, Y, g:i a', strtotime($order['order_date']))) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="5" class="empty">No transactions found.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
