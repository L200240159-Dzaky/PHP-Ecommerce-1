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
        }
        .wrap { max-width: 1120px; margin: 0 auto; padding: 20px; }
        .nav {
            display: flex; justify-content: space-between; align-items: center; gap: 16px;
            margin-bottom: 18px; padding: 14px 16px; border: 1px solid rgba(243, 220, 199, 0.9);
            border-radius: 18px; background: rgba(255, 255, 255, 0.7); backdrop-filter: blur(10px);
        }
        .brand { display: flex; align-items: center; gap: 10px; font-weight: 800; letter-spacing: 0.02em; }
        .brand-mark {
            width: 12px; height: 12px; border-radius: 999px; background: var(--accent);
            box-shadow: 0 0 0 6px rgba(249, 115, 22, 0.14);
        }
        .nav-links { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        .nav a {
            color: var(--text); text-decoration: none; font-weight: 700; padding: 9px 12px;
            border-radius: 999px; border: 1px solid transparent;
        }
        .nav a:hover { background: var(--accent-soft); border-color: #ffd7b8; color: var(--accent-dark); }
        .hero {
            display: grid; gap: 10px; padding: 18px 0 10px; margin-bottom: 14px;
        }
        .kicker {
            display: inline-flex; align-self: start; padding: 7px 11px; border-radius: 999px;
            background: var(--accent-soft); color: var(--accent-dark); font-size: 0.84rem; font-weight: 800;
            text-transform: uppercase; letter-spacing: 0.08em;
        }
        .hero h1 { margin: 0; font-size: clamp(2rem, 4vw, 3.4rem); line-height: 1; letter-spacing: -0.05em; }
        .alerts { margin-bottom: 16px; }
        .alert {
            padding: 12px 14px; border-radius: 14px; margin-bottom: 10px; border: 1px solid transparent;
        }
        .alert.success { background: #fff5eb; color: #9a3412; border-color: #fed7aa; }
        .alert.error { background: #fff1f2; color: #be123c; border-color: #fecdd3; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-top: 8px; }
        .card {
            background: var(--panel-strong); border: 1px solid var(--border); border-radius: 22px;
            padding: 18px; box-shadow: var(--shadow); min-height: 180px;
        }
        .card h3 { margin: 0 0 8px; font-size: 1.05rem; }
        .price {
            display: inline-flex; align-items: center; margin: 10px 0 14px; padding: 7px 10px;
            border-radius: 999px; background: var(--accent-soft); color: var(--accent-dark); font-weight: 800;
        }
        .meta { color: var(--muted); font-size: 0.95rem; line-height: 1.5; }
        .empty {
            padding: 20px; border-radius: 18px; border: 1px dashed #f1c9a8; background: rgba(255,255,255,.7);
        }
    </style>
</head>
<body>
<div class="wrap">
    <div class="nav">
        <div class="brand"><span class="brand-mark"></span><span><?= e(APP_NAME) ?></span></div>
        <div class="nav-links">
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
        <span class="kicker">Catalog</span>
        <h1>Minimal storefront.</h1>
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
                <?php if (!empty($product['description'])): ?>
                    <p class="meta"><?= e($product['description']) ?></p>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
        <?php if (!$products): ?>
            <article class="empty">
                <strong>No products yet.</strong>
            </article>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
