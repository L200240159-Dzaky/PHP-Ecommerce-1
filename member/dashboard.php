<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';

require_role('member');
$user = user();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard - <?= e(APP_NAME) ?></title>
    <style>
        body {
            margin: 0; font-family: "Trebuchet MS", "Segoe UI", sans-serif; color: #1f1308;
            background:
                radial-gradient(circle at top left, rgba(249, 115, 22, 0.12), transparent 28%),
                linear-gradient(180deg, #fffaf5 0%, #fff4e9 100%);
        }
        .box {
            max-width: 760px; margin: 56px auto; background: rgba(255,255,255,.9); padding: 28px;
            border-radius: 24px; box-shadow: 0 24px 70px rgba(249, 115, 22, 0.12); border: 1px solid #f3dcc7;
        }
        .pill { display: inline-block; padding: 7px 10px; border-radius: 999px; background: #fff1e7; color: #ea580c; font-weight: 800; }
        a { color: #ea580c; }
    </style>
</head>
<body>
<div class="box">
    <h1>Member dashboard</h1>
    <p>Welcome, <?= e($user['name']) ?>.</p>
    <p class="pill">Role: <?= e($user['role']) ?></p>
    <div style="margin-top: 20px;">
        <p><a href="<?= e(url('member/orders.php')) ?>" style="font-weight: bold; font-size: 1.1rem;">My Orders</a></p>
        <p><a href="<?= e(url('index.php')) ?>">Back to home</a></p>
    </div>
</div>
</body>
</html>
