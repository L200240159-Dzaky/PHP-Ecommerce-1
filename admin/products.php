<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';

require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        flash('error', 'Invalid product submission.');
        redirect('admin/products.php');
    }

    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'create') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $price = (float) ($_POST['price'] ?? 0);
        $categoryId = (int) ($_POST['category_id'] ?? 0);

        if ($name === '' || $price <= 0) {
            flash('error', 'Product name and price are required.');
            redirect('admin/products.php');
        }

        $statement = db()->prepare('INSERT INTO products (category_id, name, description, price, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())');
        $statement->execute([$categoryId > 0 ? $categoryId : null, $name, $description, $price]);

        flash('success', 'Product created.');
        redirect('admin/products.php');
    }

    if ($action === 'update') {
        $id = (int) ($_POST['id'] ?? 0);
        $name = trim((string) ($_POST['name'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $price = (float) ($_POST['price'] ?? 0);
        $categoryId = (int) ($_POST['category_id'] ?? 0);

        if ($id <= 0 || $name === '' || $price <= 0) {
            flash('error', 'Invalid product update.');
            redirect('admin/products.php');
        }

        $statement = db()->prepare('UPDATE products SET category_id = ?, name = ?, description = ?, price = ?, updated_at = NOW() WHERE id = ?');
        $statement->execute([$categoryId > 0 ? $categoryId : null, $name, $description, $price, $id]);

        flash('success', 'Product updated.');
        redirect('admin/products.php');
    }

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);

        if ($id > 0) {
            $statement = db()->prepare('DELETE FROM products WHERE id = ?');
            $statement->execute([$id]);
            flash('success', 'Product deleted.');
        }

        redirect('admin/products.php');
    }
}

$categories = db()->query('SELECT id, name FROM categories ORDER BY name ASC')->fetchAll();
$products = db()->query('SELECT p.id, p.name, p.description, p.price, p.category_id, c.name AS category_name FROM products p LEFT JOIN categories c ON c.id = p.category_id ORDER BY p.id DESC')->fetchAll();
$success = flash('success');
$error = flash('error');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Products - <?= e(APP_NAME) ?></title>
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
        }
        .wrap { max-width: 1160px; margin: 0 auto; padding: 20px; }
        .topbar {
            display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap;
            margin-bottom: 18px; padding: 14px 16px; border-radius: 18px; background: rgba(255,255,255,.72);
            border: 1px solid var(--border); backdrop-filter: blur(10px);
        }
        .topbar a { color: var(--accent-dark); text-decoration: none; font-weight: 800; }
        .title { margin: 0; font-size: clamp(1.7rem, 3vw, 2.4rem); letter-spacing: -0.04em; }
        .panel {
            background: var(--panel); border-radius: 22px; padding: 22px; box-shadow: var(--shadow);
            margin-bottom: 18px; border: 1px solid var(--border);
        }
        h2 { margin-top: 0; }
        label { display: block; margin: 12px 0 6px; color: var(--muted); font-weight: 700; }
        input, textarea, select {
            width: 100%; padding: 12px 13px; border: 1px solid #e9cfb6; border-radius: 12px;
            box-sizing: border-box; background: #fff; color: var(--text);
        }
        textarea { min-height: 96px; resize: vertical; }
        button {
            margin-top: 14px; padding: 10px 14px; border: 0; border-radius: 999px; background: var(--accent);
            color: #fff; font-weight: 800; cursor: pointer;
        }
        button:hover { background: var(--accent-dark); }
        .stack { display: grid; gap: 18px; }
        .table-wrap { overflow-x: auto; }
        .table { width: 100%; border-collapse: collapse; min-width: 920px; }
        .table th, .table td {
            padding: 14px 10px; border-bottom: 1px solid #f1ddca; text-align: left; vertical-align: top;
        }
        .table th { color: var(--muted); font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.08em; }
        .row-actions { display: flex; gap: 8px; flex-wrap: wrap; align-items: start; }
        .row-actions input, .row-actions select, .row-actions textarea { min-width: 140px; }
        .wide-form .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }
        .wide-form .full { grid-column: 1 / -1; }
        .danger { background: #ea580c; }
        .danger:hover { background: #c2410c; }
        .alert.success, .alert.error { padding: 12px; border-radius: 12px; margin-bottom: 10px; border: 1px solid transparent; }
        .alert.success { background: #fff5eb; color: #9a3412; border-color: #fed7aa; }
        .alert.error { background: #fff1f2; color: #be123c; border-color: #fecdd3; }
        .muted { color: var(--muted); }
        a { color: var(--accent-dark); }
    </style>
</head>
<body>
<div class="wrap">
    <div class="topbar">
        <a href="<?= e(url('index.php')) ?>">Back to home</a>
        <span class="muted">Admin products</span>
    </div>
    <h1 class="title">Product control</h1>

    <?php if ($success !== null): ?><div class="alert success"><?= e($success) ?></div><?php endif; ?>
    <?php if ($error !== null): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>

    <div class="stack">
        <section class="panel wide-form">
            <h2>Add product</h2>
            <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="create">
                <div class="form-grid">
                    <div>
                        <label for="create_name">Name</label>
                        <input id="create_name" name="name" type="text" required>
                    </div>
                    <div>
                        <label for="create_price">Price</label>
                        <input id="create_price" name="price" type="number" step="0.01" min="0.01" required>
                    </div>
                    <div class="full">
                        <label for="create_description">Description</label>
                        <textarea id="create_description" name="description"></textarea>
                    </div>
                    <div class="full">
                        <label for="create_category">Category</label>
                        <select id="create_category" name="category_id">
                            <option value="">Uncategorized</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= e((string) $category['id']) ?>"><?= e($category['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <button type="submit">Save product</button>
            </form>
        </section>

        <section class="panel">
            <h2>Inventory</h2>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td>
                                <strong><?= e($product['name']) ?></strong><br>
                                <small><?= e($product['description'] ?? '') ?></small>
                            </td>
                            <td><?= e($product['category_name'] ?? 'Uncategorized') ?></td>
                            <td>$<?= e(number_format((float) $product['price'], 2)) ?></td>
                            <td>
                                <form method="post" class="row-actions">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="id" value="<?= e((string) $product['id']) ?>">
                                    <input type="text" name="name" value="<?= e($product['name']) ?>" required>
                                    <input type="number" name="price" step="0.01" min="0.01" value="<?= e((string) $product['price']) ?>" required>
                                    <select name="category_id">
                                        <option value="">Uncategorized</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?= e((string) $category['id']) ?>" <?= (int) ($product['category_id'] ?? 0) === (int) $category['id'] ? 'selected' : '' ?>><?= e($category['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <textarea name="description" placeholder="description"><?= e($product['description'] ?? '') ?></textarea>
                                    <button type="submit">Update</button>
                                </form>
                                <form method="post" onsubmit="return confirm('Delete this product?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= e((string) $product['id']) ?>">
                                    <button type="submit" class="danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$products): ?>
                        <tr><td colspan="4">No products yet.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>
</body>
</html>
