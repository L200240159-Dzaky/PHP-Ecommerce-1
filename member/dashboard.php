<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';

require_login();
$user = user();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard - <?= e(APP_NAME) ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f7f7fb; color: #111827; }
        .box { max-width: 760px; margin: 48px auto; background: #fff; padding: 28px; border-radius: 18px; box-shadow: 0 20px 48px rgba(15, 23, 42, .08); }
        .pill { display: inline-block; padding: 6px 10px; border-radius: 999px; background: #eef2ff; color: #3730a3; font-weight: 700; }
        a { color: #7c3aed; }
    </style>
</head>
<body>
<div class="box">
    <h1>Member dashboard</h1>
    <p>Welcome, <?= e($user['name']) ?>.</p>
    <p class="pill">Role: <?= e($user['role']) ?></p>
    <p>This is the protected member area. Guests should not see this page.</p>
    <p><a href="<?= e(url('index.php')) ?>">Back to home</a></p>
</div>
</body>
</html>
