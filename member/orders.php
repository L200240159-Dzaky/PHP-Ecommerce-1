<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';

require_role('member');

$user = user();

// Fetch orders with order items and product names
$statement = db()->prepare('
    SELECT 
        o.id AS order_id, 
        o.total_price, 
        o.created_at AS order_date,
        oi.quantity, 
        oi.price AS item_price, 
        p.name AS product_name
    FROM orders o
    JOIN order_items oi ON oi.order_id = o.id
    LEFT JOIN products p ON p.id = oi.product_id
    WHERE o.user_id = ?
    ORDER BY o.created_at DESC, oi.id ASC
');
$statement->execute([$user['id']]);
$results = $statement->fetchAll();

// Group results by order_id
$orders = [];
foreach ($results as $row) {
    $orderId = $row['order_id'];
    if (!isset($orders[$orderId])) {
        $orders[$orderId] = [
            'id' => $orderId,
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

$success = flash('success');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Orders - <?= e(APP_NAME) ?></title>
    <style>
        :root {
            color-scheme: light;
            --bg: #fff7ef;
            --panel: rgba(255, 255, 255, 0.9);
            --panel-strong: #ffffff;
            --text: #1f1308;
            --muted: #7a5d45;
            --accent: #f97316;
            --accent-dark: #ea580c;
            --accent-soft: #fff1e7;
            --border: #f3dcc7;
            --shadow: 0 24px 70px rgba(249, 115, 22, 0.14);
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Trebuchet MS", "Segoe UI", sans-serif;
            background:
                radial-gradient(circle at top left, rgba(249, 115, 22, 0.14), transparent 28%),
                radial-gradient(circle at 85% 10%, rgba(251, 146, 60, 0.14), transparent 24%),
                linear-gradient(180deg, #fffaf5 0%, #fff4e9 100%);
            color: var(--text);
            min-height: 100vh;
        }
        .wrap { max-width: 800px; margin: 40px auto; padding: 20px; }
        .nav {
            display: flex; justify-content: space-between; align-items: center; gap: 16px;
            margin-bottom: 24px; padding: 14px 16px; border: 1px solid rgba(243, 220, 199, 0.9);
            border-radius: 18px; background: rgba(255, 255, 255, 0.7); backdrop-filter: blur(10px);
        }
        .brand { display: flex; align-items: center; gap: 10px; font-weight: 800; }
        .brand-mark {
            width: 12px; height: 12px; border-radius: 999px; background: var(--accent);
            box-shadow: 0 0 0 6px rgba(249, 115, 22, 0.14);
        }
        .nav-links a {
            color: var(--text); text-decoration: none; font-weight: 700; padding: 8px 12px;
            border-radius: 999px; border: 1px solid transparent;
        }
        .nav-links a:hover { background: var(--accent-soft); border-color: #ffd7b8; color: var(--accent-dark); }
        .alert {
            padding: 12px 14px; border-radius: 14px; margin-bottom: 16px; border: 1px solid transparent;
        }
        .alert.success { background: #fff5eb; color: #9a3412; border-color: #fed7aa; }
        .order-card {
            background: var(--panel-strong); border: 1px solid var(--border); border-radius: 22px;
            padding: 24px; box-shadow: var(--shadow); margin-bottom: 20px;
        }
        .order-header {
            display: flex; justify-content: space-between; align-items: center;
            border-bottom: 1px solid var(--border); padding-bottom: 12px; margin-bottom: 16px;
            flex-wrap: wrap; gap: 8px;
        }
        .order-id { font-weight: 800; font-size: 1.1rem; }
        .order-date { color: var(--muted); font-size: 0.9rem; }
        .order-total { font-weight: 800; color: var(--accent-dark); font-size: 1.2rem; }
        .item-list { list-style: none; padding: 0; margin: 0; }
        .item-row {
            display: flex; justify-content: space-between; padding: 8px 0;
            border-bottom: 1px dashed rgba(243, 220, 199, 0.5);
        }
        .item-row:last-child { border-bottom: none; }
        .item-name { font-weight: 700; }
        .item-meta { color: var(--muted); font-size: 0.9rem; }
        .empty {
            padding: 40px; border-radius: 18px; border: 1px dashed #f1c9a8;
            background: rgba(255,255,255,.7); text-align: center; font-size: 1.1rem;
        }
    </style>
</head>
<body>
<div class="wrap">
    <div class="nav">
        <div class="brand"><span class="brand-mark"></span><span><?= e(APP_NAME) ?></span></div>
        <div class="nav-links">
            <a href="<?= e(url('index.php')) ?>">Home</a>
            <a href="<?= e(url('member/dashboard.php')) ?>">Dashboard</a>
        </div>
    </div>

    <h1>My Order History</h1>

    <?php if ($success !== null): ?>
        <div class="alert success"><?= e($success) ?></div>
    <?php endif; ?>

    <div class="orders-container">
        <?php foreach ($orders as $order): ?>
            <div class="order-card">
                <div class="order-header">
                    <div>
                        <span class="order-id">Order #<?= e((string)$order['id']) ?></span>
                        <div class="order-date"><?= e(date('F j, Y, g:i a', strtotime($order['order_date']))) ?></div>
                    </div>
                    <span class="order-total">$<?= e(number_format($order['total_price'], 2)) ?></span>
                </div>
                <ul class="item-list">
                    <?php foreach ($order['items'] as $item): ?>
                        <li class="item-row">
                            <span class="item-name"><?= e($item['product_name']) ?> <span class="item-meta">x<?= e((string)$item['quantity']) ?></span></span>
                            <span>$<?= e(number_format($item['price'] * $item['quantity'], 2)) ?> <span class="item-meta">($<?= e(number_format($item['price'], 2)) ?> each)</span></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endforeach; ?>

        <?php if (empty($orders)): ?>
            <div class="empty">
                <strong>You haven't placed any orders yet.</strong>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
