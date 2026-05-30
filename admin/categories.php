<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';

require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        flash('error', 'Invalid category submission.');
        redirect('admin/categories.php');
    }

    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'create') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $slug = trim((string) ($_POST['slug'] ?? ''));

        if ($name === '' || $slug === '') {
            flash('error', 'Category name and slug are required.');
            redirect('admin/categories.php');
        }

        $statement = db()->prepare('INSERT INTO categories (name, slug, created_at, updated_at) VALUES (?, ?, NOW(), NOW())');
        $statement->execute([$name, $slug]);

        flash('success', 'Category created.');
        redirect('admin/categories.php');
    }

    if ($action === 'update') {
        $id = (int) ($_POST['id'] ?? 0);
        $name = trim((string) ($_POST['name'] ?? ''));
        $slug = trim((string) ($_POST['slug'] ?? ''));

        if ($id <= 0 || $name === '' || $slug === '') {
            flash('error', 'Invalid category update.');
            redirect('admin/categories.php');
        }

        $statement = db()->prepare('UPDATE categories SET name = ?, slug = ?, updated_at = NOW() WHERE id = ?');
        $statement->execute([$name, $slug, $id]);

        flash('success', 'Category updated.');
        redirect('admin/categories.php');
    }

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);

        if ($id > 0) {
            $statement = db()->prepare('DELETE FROM categories WHERE id = ?');
            $statement->execute([$id]);
            flash('success', 'Category deleted.');
        }

        redirect('admin/categories.php');
    }
}

$categories = db()->query('SELECT id, name, slug FROM categories ORDER BY id DESC')->fetchAll();
$success = flash('success');
$error = flash('error');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Categories - <?= e(APP_NAME) ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f7f7fb; color: #111827; }
        .wrap { max-width: 1100px; margin: 0 auto; padding: 24px; }
        .panel { background: #fff; border-radius: 18px; padding: 24px; box-shadow: 0 20px 48px rgba(15, 23, 42, .08); margin-bottom: 20px; }
        label { display: block; margin: 12px 0 6px; }
        input { width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 10px; box-sizing: border-box; }
        button { margin-top: 14px; padding: 10px 14px; border: 0; border-radius: 10px; background: #7c3aed; color: #fff; font-weight: 700; cursor: pointer; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { padding: 10px; border-bottom: 1px solid #e5e7eb; text-align: left; vertical-align: top; }
        .row-actions { display: grid; gap: 8px; }
        .danger { background: #dc2626; }
        .alert.success { background: #ecfdf3; color: #027a48; padding: 12px; border-radius: 10px; margin-bottom: 10px; }
        .alert.error { background: #fef3f2; color: #b42318; padding: 12px; border-radius: 10px; margin-bottom: 10px; }
        a { color: #7c3aed; }
    </style>
</head>
<body>
<div class="wrap">
    <p><a href="<?= e(url('admin/products.php')) ?>">Back to products</a></p>
    <h1>Admin category management</h1>

    <?php if ($success !== null): ?><div class="alert success"><?= e($success) ?></div><?php endif; ?>
    <?php if ($error !== null): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>

    <div class="grid">
        <section class="panel">
            <h2>Create category</h2>
            <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="create">
                <label for="create_name">Name</label>
                <input id="create_name" name="name" type="text" required>
                <label for="create_slug">Slug</label>
                <input id="create_slug" name="slug" type="text" required>
                <button type="submit">Save category</button>
            </form>
        </section>

        <section class="panel">
            <h2>Existing categories</h2>
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Slug</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($categories as $category): ?>
                    <tr>
                        <td><?= e($category['name']) ?></td>
                        <td><?= e($category['slug']) ?></td>
                        <td>
                            <form method="post" class="row-actions">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="id" value="<?= e((string) $category['id']) ?>">
                                <input type="text" name="name" value="<?= e($category['name']) ?>" required>
                                <input type="text" name="slug" value="<?= e($category['slug']) ?>" required>
                                <button type="submit">Update</button>
                            </form>
                            <form method="post" onsubmit="return confirm('Delete this category?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= e((string) $category['id']) ?>">
                                <button type="submit" class="danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$categories): ?>
                    <tr><td colspan="3">No categories yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </section>
    </div>
</div>
</body>
</html>
