<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';

if (is_logged_in()) {
    redirect('index.php');
}

$error = flash('error');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        flash('error', 'Invalid registration submission.');
        redirect('auth/register.php');
    }

    $name = trim((string) ($_POST['name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($name === '' || $email === '' || $password === '') {
        set_old_input(['name' => $name, 'email' => $email]);
        flash('error', 'All fields are required.');
        redirect('auth/register.php');
    }

    if (strlen($password) < 8) {
        set_old_input(['name' => $name, 'email' => $email]);
        flash('error', 'Password must be at least 8 characters.');
        redirect('auth/register.php');
    }

    if (load_user_by_email($email) !== null) {
        set_old_input(['name' => $name, 'email' => $email]);
        flash('error', 'Email is already registered.');
        redirect('auth/register.php');
    }

    $statement = db()->prepare('INSERT INTO users (name, email, password, role, created_at) VALUES (?, ?, ?, ?, NOW())');
    $statement->execute([
        $name,
        $email,
        password_hash($password, PASSWORD_DEFAULT),
        'member',
    ]);

    clear_old_input();
    flash('success', 'Account created. You can now log in.');
    redirect('auth/login.php');
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register - <?= e(APP_NAME) ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f7f7fb; color: #111827; }
        .box { max-width: 420px; margin: 60px auto; background: #fff; padding: 28px; border-radius: 18px; box-shadow: 0 20px 48px rgba(15, 23, 42, .08); }
        label { display: block; margin: 14px 0 6px; }
        input { width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 10px; box-sizing: border-box; }
        button { margin-top: 18px; width: 100%; padding: 12px; border: 0; border-radius: 10px; background: #7c3aed; color: #fff; font-weight: 700; }
        a { color: #7c3aed; }
        .error { background: #fef3f2; color: #b42318; padding: 12px; border-radius: 10px; margin-bottom: 12px; }
    </style>
</head>
<body>
<div class="box">
    <h1>Register</h1>
    <?php if ($error !== null): ?><div class="error"><?= e($error) ?></div><?php endif; ?>
    <form method="post">
        <?= csrf_field() ?>
        <label for="name">Name</label>
        <input id="name" name="name" type="text" value="<?= e(old('name')) ?>" required>
        <label for="email">Email</label>
        <input id="email" name="email" type="email" value="<?= e(old('email')) ?>" required>
        <label for="password">Password</label>
        <input id="password" name="password" type="password" required>
        <button type="submit">Create account</button>
    </form>
    <p>Already have an account? <a href="<?= e(url('auth/login.php')) ?>">Login</a></p>
</div>
</body>
</html>
