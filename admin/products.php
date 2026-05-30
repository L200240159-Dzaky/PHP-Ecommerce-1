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
        $imagePath = trim((string) ($_POST['image_path'] ?? ''));
        $categoryId = (int) ($_POST['category_id'] ?? 0);

        if ($name === '' || $price <= 0) {
            flash('error', 'Product name and price are required.');
            redirect('admin/products.php');
        }

        $statement = db()->prepare('INSERT INTO products (category_id, name, description, price, image_path, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())');
        $statement->execute([$categoryId > 0 ? $categoryId : null, $name, $description, $price, $imagePath]);

        flash('success', 'Product created.');
        redirect('admin/products.php');
    }

    if ($action === 'update') {
        $id = (int) ($_POST['id'] ?? 0);
        $name = trim((string) ($_POST['name'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $price = (float) ($_POST['price'] ?? 0);
        $imagePath = trim((string) ($_POST['image_path'] ?? ''));
        $categoryId = (int) ($_POST['category_id'] ?? 0);

        if ($id <= 0 || $name === '' || $price <= 0) {
            flash('error', 'Invalid product update.');
            redirect('admin/products.php');
        }

        $statement = db()->prepare('UPDATE products SET category_id = ?, name = ?, description = ?, price = ?, image_path = ?, updated_at = NOW() WHERE id = ?');
        $statement->execute([$categoryId > 0 ? $categoryId : null, $name, $description, $price, $imagePath, $id]);

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
$products = db()->query('SELECT p.id, p.name, p.description, p.price, p.image_path, p.category_id, c.name AS category_name FROM products p LEFT JOIN categories c ON c.id = p.category_id ORDER BY p.id DESC')->fetchAll();
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
        body { font-family: Arial, sans-serif; margin: 0; background: #f7f7fb; color: #111827; }
        .wrap { max-width: 1100px; margin: 0 auto; padding: 24px; }
        .panel { background: #fff; border-radius: 18px; padding: 24px; box-shadow: 0 20px 48px rgba(15, 23, 42, .08); margin-bottom: 20px; }
        label { display: block; margin: 12px 0 6px; }
        input, textarea { width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 10px; box-sizing: border-box; }
        textarea { min-height: 100px; }
        button { margin-top: 14px; padding: 10px 14px; border: 0; border-radius: 10px; background: #7c3aed; color: #fff; font-weight: 700; cursor: pointer; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { padding: 10px; border-bottom: 1px solid #e5e7eb; text-align: left; vertical-align: top; }
        .row-actions { display: flex; gap: 8px; flex-wrap: wrap; }
        .danger { background: #dc2626; }
        .alert.success { background: #ecfdf3; color: #027a48; padding: 12px; border-radius: 10px; margin-bottom: 10px; }
        .alert.error { background: #fef3f2; color: #b42318; padding: 12px; border-radius: 10px; margin-bottom: 10px; }
        a { color: #7c3aed; }
    </style>
</head>
<body>
<div class="wrap">
    <p><a href="<?= e(url('index.php')) ?>">Back to home</a></p>
    <h1>Admin product management</h1>

    <?php if ($success !== null): ?><div class="alert success"><?= e($success) ?></div><?php endif; ?>
    <?php if ($error !== null): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>

    <div class="grid">
        <section class="panel">
            <h2>Create product</h2>
            <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="create">
                <label for="create_name">Name</label>
                <input id="create_name" name="name" type="text" required>
                <label for="create_description">Description</label>
                <textarea id="create_description" name="description"></textarea>
                <label for="create_price">Price</label>
                <input id="create_price" name="price" type="number" step="0.01" min="0.01" required>
                <label for="create_category">Category</label>
                <select id="create_category" name="category_id">
                    <option value="">Uncategorized</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= e((string) $category['id']) ?>"><?= e($category['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <label for="create_image">Image path</label>
                <input id="create_image" name="image_path" type="text" placeholder="images/product.jpg">
                <button type="submit">Save product</button>
            </form>
        </section>

        <section class="panel">
            <h2>Existing products</h2>
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
                                <input type="text" name="image_path" value="<?= e($product['image_path'] ?? '') ?>" placeholder="image path">
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
                    <tr><td colspan="3">No products yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </section>
    </div>
</div>
</body>
</html>
