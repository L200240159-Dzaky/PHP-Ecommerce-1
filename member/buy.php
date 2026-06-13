<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';

require_role('member');

$user = user();
$productId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// Fetch product details
$statement = db()->prepare('
    SELECT p.id, p.name, p.description, p.price, c.name AS category_name 
    FROM products p 
    LEFT JOIN categories c ON c.id = p.category_id 
    WHERE p.id = ? 
    LIMIT 1
');
$statement->execute([$productId]);
$product = $statement->fetch();

if (!$product) {
    flash('error', 'Product not found.');
    redirect('index.php');
}

$success = flash('success');
$error = flash('error');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        flash('error', 'Invalid security token.');
        redirect('member/buy.php?id=' . $product['id']);
    }

    $quantity = isset($_POST['quantity']) ? (int) $_POST['quantity'] : 1;
    if ($quantity < 1) {
        flash('error', 'Quantity must be at least 1.');
        redirect('member/buy.php?id=' . $product['id']);
    }

    $price = (float) $product['price'];
    $totalPrice = $price * $quantity;

    try {
        $pdo = db();
        $pdo->beginTransaction();

        // Insert into orders
        $orderStmt = $pdo->prepare('
            INSERT INTO orders (user_id, total_price, created_at, updated_at) 
            VALUES (?, ?, NOW(), NOW())
        ');
        $orderStmt->execute([$user['id'], $totalPrice]);
        $orderId = $pdo->lastInsertId();

        // Insert into order_items
        $itemStmt = $pdo->prepare('
            INSERT INTO order_items (order_id, product_id, quantity, price, created_at) 
            VALUES (?, ?, ?, ?, NOW())
        ');
        $itemStmt->execute([$orderId, $product['id'], $quantity, $price]);

        $pdo->commit();

        flash('success', 'Thank you for your purchase! Order placed successfully.');
        redirect('member/orders.php');
    } catch (Exception $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        flash('error', 'Failed to place order: ' . $e->getMessage());
        redirect('member/buy.php?id=' . $product['id']);
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Buy <?= e($product['name']) ?> - <?= e(APP_NAME) ?></title>
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
        .wrap { max-width: 600px; margin: 40px auto; padding: 20px; }
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
        .card {
            background: var(--panel-strong); border: 1px solid var(--border); border-radius: 22px;
            padding: 28px; box-shadow: var(--shadow);
        }
        h2 { margin-top: 0; margin-bottom: 16px; font-size: 1.6rem; letter-spacing: -0.02em; }
        .price-badge {
            display: inline-flex; align-items: center; margin: 10px 0 16px; padding: 8px 14px;
            border-radius: 999px; background: var(--accent-soft); color: var(--accent-dark); font-weight: 800;
            font-size: 1.1rem;
        }
        .meta { color: var(--muted); font-size: 0.95rem; line-height: 1.5; margin-bottom: 8px; }
        .desc { background: var(--accent-soft); padding: 12px 16px; border-radius: 14px; border-left: 4px solid var(--accent); margin: 16px 0; }
        .form-group { margin-bottom: 20px; }
        label { display: block; font-weight: 700; margin-bottom: 8px; }
        .input-qty {
            width: 100px; padding: 10px 14px; border-radius: 10px; border: 1px solid var(--border);
            font-size: 1.1rem; text-align: center; font-weight: 700;
        }
        .btn-confirm {
            display: inline-block; width: 100%; padding: 14px; background: var(--accent);
            color: white; border: none; border-radius: 14px; font-size: 1.1rem; font-weight: 700;
            cursor: pointer; transition: background 0.2s; box-shadow: 0 4px 12px rgba(249, 115, 22, 0.2);
        }
        .btn-confirm:hover { background: var(--accent-dark); }
        .alert {
            padding: 12px 14px; border-radius: 14px; margin-bottom: 16px; border: 1px solid transparent;
        }
        .alert.error { background: #fff1f2; color: #be123c; border-color: #fecdd3; }
        .total-display { font-size: 1.2rem; font-weight: 800; color: var(--text); margin-top: 14px; }
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

    <div class="card">
        <h2>Confirm Your Purchase</h2>
        
        <?php if ($error !== null): ?>
            <div class="alert error"><?= e($error) ?></div>
        <?php endif; ?>

        <h3><?= e($product['name']) ?></h3>
        <?php if (!empty($product['category_name'])): ?>
            <div class="meta">Category: <strong><?= e($product['category_name']) ?></strong></div>
        <?php endif; ?>
        
        <div class="price-badge">$<?= e(number_format((float) $product['price'], 2)) ?></div>

        <?php if (!empty($product['description'])): ?>
            <div class="desc"><?= e($product['description']) ?></div>
        <?php endif; ?>

        <form action="<?= e(url('member/buy.php?id=' . $product['id'])) ?>" method="post">
            <?= csrf_field() ?>
            <div class="form-group">
                <label for="quantity">Quantity</label>
                <input type="number" name="quantity" id="quantity" class="input-qty" value="1" min="1" required>
            </div>

            <div class="total-display">
                Total Price: $<span id="total-price-val"><?= e(number_format((float) $product['price'], 2)) ?></span>
            </div>
            <br>
            <button type="submit" class="btn-confirm">Confirm Order</button>
        </form>
    </div>
</div>

<script>
    const price = <?= (float) $product['price'] ?>;
    const qtyInput = document.getElementById('quantity');
    const totalSpan = document.getElementById('total-price-val');

    qtyInput.addEventListener('input', function() {
        let val = parseInt(qtyInput.value) || 1;
        if (val < 1) val = 1;
        const total = price * val;
        totalSpan.textContent = total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    });
</script>
</body>
</html>
