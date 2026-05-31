<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

$statement = db()->query('SELECT p.id, p.name, p.description, p.price, c.name AS category_name FROM products p LEFT JOIN categories c ON c.id = p.category_id ORDER BY p.id DESC');
$products = $statement->fetchAll();

$success = flash('success');
$error = flash('error');
$user = user();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(APP_NAME) ?></title>
    <style>
        :root { color-scheme: light; --bg: #f4f1ea; --panel: #ffffff; --text: #1f2933; --muted: #667085; --accent: #7c3aed; --border: #e5e7eb; }
        body { margin: 0; font-family: Arial, Helvetica, sans-serif; background: linear-gradient(180deg, #f7f4ee 0%, #f0f7ff 100%); color: var(--text); }
        .wrap { max-width: 1100px; margin: 0 auto; padding: 24px; }
        .nav { display: flex; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 28px; }
        .nav a { color: var(--accent); text-decoration: none; font-weight: 700; margin-left: 12px; }
        .hero { background: rgba(255,255,255,.8); border: 1px solid var(--border); border-radius: 20px; padding: 28px; box-shadow: 0 16px 40px rgba(15, 23, 42, .08); margin-bottom: 24px; }
        .hero h1 { margin: 0 0 8px; font-size: 2.2rem; }
        .hero p { margin: 0; color: var(--muted); }
        .alerts { margin-bottom: 16px; }
        .alert { padding: 12px 14px; border-radius: 12px; margin-bottom: 10px; }
        .alert.success { background: #ecfdf3; color: #027a48; }
        .alert.error { background: #fef3f2; color: #b42318; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 18px; }
        .card { background: var(--panel); border: 1px solid var(--border); border-radius: 18px; padding: 18px; box-shadow: 0 10px 28px rgba(15, 23, 42, .05); }
        .card h3 { margin: 0 0 6px; }
        .price { color: var(--accent); font-weight: 700; margin: 10px 0; }
        .meta { color: var(--muted); font-size: .95rem; }
        .actions { display: flex; gap: 10px; flex-wrap: wrap; }
        .button { display: inline-block; padding: 10px 14px; border-radius: 10px; text-decoration: none; font-weight: 700; }
        .button.primary { background: var(--accent); color: #fff; }
        .button.secondary { background: #eef2ff; color: #3730a3; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="nav">
        <strong><?= e(APP_NAME) ?></strong>
        <div>
            <a href="<?= e(url('index.php')) ?>">Home</a>
            <?php if ($user === null): ?>
                <a href="<?= e(url('auth/login.php')) ?>">Login</a>
                <a href="<?= e(url('auth/register.php')) ?>">Register</a>
            <?php else: ?>
                <a href="<?= e(url('member/dashboard.php')) ?>">Dashboard</a>
                <?php if (has_role('admin')): ?>
                    <a href="<?= e(url('admin/products.php')) ?>">Admin Products</a>
                <?php endif; ?>
                <a href="<?= e(url('auth/logout.php')) ?>">Logout</a>
            <?php endif; ?>
        </div>
    </div>

    <section class="hero">
        <h1>Local ecommerce foundation is ready.</h1>
        <p>Guests can browse. Members can sign in. Admins can manage products from a protected dashboard.</p>
    </section>

    <div class="alerts">
        <?php if ($success !== null): ?><div class="alert success"><?= e($success) ?></div><?php endif; ?>
        <?php if ($error !== null): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
    </div>

    <div class="grid">
        <?php foreach ($products as $product): ?>
            <article class="card">
                <h3><?= e($product['name']) ?></h3>
                <?php if (!empty($product['category_name'])): ?>
                    <div class="meta"><?= e($product['category_name']) ?></div>
                <?php endif; ?>
                <div class="price">$<?= e(number_format((float) $product['price'], 2)) ?></div>
                <p class="meta"><?= e($product['description'] ?? '') ?></p>
            </article>
        <?php endforeach; ?>
        <?php if (!$products): ?>
            <article class="card">
                <h3>No products yet</h3>
                <p class="meta">Add your first product from the admin page after setting up the database.</p>
            </article>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
