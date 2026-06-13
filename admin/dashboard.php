<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';

require_role('admin');

$user = user();

// --- Stats queries ---

// Total orders & total revenue
$statsStmt = db()->query('SELECT COUNT(*) AS total_orders, COALESCE(SUM(total_price), 0) AS total_revenue FROM orders');
$stats = $statsStmt->fetch();

// Total members (role = member)
$membersStmt = db()->query("SELECT COUNT(*) AS total_members FROM users WHERE role = 'member'");
$memberStats = $membersStmt->fetch();

// Total products
$productsStmt = db()->query('SELECT COUNT(*) AS total_products FROM products');
$productStats = $productsStmt->fetch();

// Recent 5 orders
$recentStmt = db()->query('
    SELECT o.id AS order_id, o.total_price, o.created_at AS order_date,
           u.name AS user_name, u.email AS user_email
    FROM orders o
    JOIN users u ON u.id = o.user_id
    ORDER BY o.created_at DESC
    LIMIT 5
');
$recentOrders = $recentStmt->fetchAll();

$success = flash('success');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Dashboard - <?= e(APP_NAME) ?></title>
    <style>
        :root {
            color-scheme: light;
            --bg: #fff7ef;
            --panel: rgba(255, 255, 255, 0.92);
            --panel-strong: #ffffff;
            --text: #1f1308;
            --muted: #7a5d45;
            --accent: #f97316;
            --accent-dark: #ea580c;
            --accent-soft: #fff1e7;
            --border: #f3dcc7;
            --shadow: 0 24px 70px rgba(249, 115, 22, 0.13);
            --green: #16a34a;
            --green-soft: #f0fdf4;
            --blue: #2563eb;
            --blue-soft: #eff6ff;
            --purple: #7c3aed;
            --purple-soft: #f5f3ff;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Trebuchet MS", "Segoe UI", sans-serif;
            background:
                radial-gradient(circle at top left, rgba(249, 115, 22, 0.13), transparent 28%),
                radial-gradient(circle at 85% 10%, rgba(251, 146, 60, 0.11), transparent 24%),
                linear-gradient(180deg, #fffaf5 0%, #fff4e9 100%);
            color: var(--text);
            min-height: 100vh;
        }
        .wrap { max-width: 960px; margin: 0 auto; padding: 28px 20px; }

        /* Nav */
        .nav {
            display: flex; justify-content: space-between; align-items: center; gap: 12px;
            margin-bottom: 28px; padding: 14px 18px;
            border: 1px solid rgba(243, 220, 199, 0.9); border-radius: 18px;
            background: rgba(255, 255, 255, 0.72); backdrop-filter: blur(10px);
            flex-wrap: wrap;
        }
        .brand { display: flex; align-items: center; gap: 10px; font-weight: 800; letter-spacing: 0.02em; }
        .brand-mark {
            width: 12px; height: 12px; border-radius: 999px; background: var(--accent);
            box-shadow: 0 0 0 6px rgba(249, 115, 22, 0.14);
        }
        .nav-links { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
        .nav-links a {
            color: var(--text); text-decoration: none; font-weight: 700; padding: 8px 13px;
            border-radius: 999px; border: 1px solid transparent; font-size: 0.92rem;
        }
        .nav-links a:hover { background: var(--accent-soft); border-color: #ffd7b8; color: var(--accent-dark); }
        .nav-links a.active { background: var(--accent); color: #fff; }

        /* Hero */
        .hero { margin-bottom: 28px; }
        .hero-kicker {
            display: inline-flex; align-items: center; gap: 8px; padding: 7px 13px;
            border-radius: 999px; background: var(--accent-soft); color: var(--accent-dark);
            font-size: 0.82rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.09em;
            margin-bottom: 10px;
        }
        .hero h1 { margin: 0 0 6px; font-size: clamp(1.8rem, 4vw, 2.6rem); letter-spacing: -0.04em; }
        .hero p { margin: 0; color: var(--muted); font-size: 1rem; }

        /* Role badge */
        .role-badge {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 6px 14px; border-radius: 999px;
            background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
            color: #fff; font-weight: 800; font-size: 0.88rem; letter-spacing: 0.05em;
            box-shadow: 0 4px 12px rgba(249, 115, 22, 0.3);
            margin-bottom: 24px;
        }
        .role-dot { width: 7px; height: 7px; border-radius: 999px; background: rgba(255,255,255,0.7); }

        /* Stats grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
            gap: 16px;
            margin-bottom: 28px;
        }
        .stat-card {
            background: var(--panel-strong); border: 1px solid var(--border); border-radius: 20px;
            padding: 22px 20px; box-shadow: var(--shadow);
            transition: transform 0.18s, box-shadow 0.18s;
        }
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 32px 80px rgba(249, 115, 22, 0.17); }
        .stat-label { font-size: 0.82rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.09em; margin-bottom: 10px; }
        .stat-value { font-size: 1.9rem; font-weight: 800; letter-spacing: -0.04em; }
        .stat-card.orders .stat-label { color: var(--accent-dark); }
        .stat-card.orders .stat-value { color: var(--accent-dark); }
        .stat-card.revenue .stat-label { color: var(--green); }
        .stat-card.revenue .stat-value { color: var(--green); }
        .stat-card.members .stat-label { color: var(--blue); }
        .stat-card.members .stat-value { color: var(--blue); }
        .stat-card.products .stat-label { color: var(--purple); }
        .stat-card.products .stat-value { color: var(--purple); }

        /* Section */
        .section-title {
            font-size: 1.1rem; font-weight: 800; margin: 0 0 14px; letter-spacing: -0.02em;
            display: flex; justify-content: space-between; align-items: center;
        }
        .section-title a {
            font-size: 0.85rem; font-weight: 700; color: var(--accent-dark);
            text-decoration: none; padding: 5px 12px; border-radius: 999px;
            border: 1px solid var(--accent-soft); background: var(--accent-soft);
        }
        .section-title a:hover { background: var(--accent); color: #fff; border-color: var(--accent); }

        /* Recent orders panel */
        .panel {
            background: var(--panel-strong); border: 1px solid var(--border);
            border-radius: 22px; padding: 24px; box-shadow: var(--shadow); margin-bottom: 20px;
        }
        .order-row {
            display: flex; justify-content: space-between; align-items: flex-start;
            padding: 12px 0; border-bottom: 1px solid #f3dcc7; gap: 12px; flex-wrap: wrap;
        }
        .order-row:last-child { border-bottom: none; padding-bottom: 0; }
        .order-id-badge {
            font-weight: 800; color: var(--accent-dark); font-size: 0.95rem;
            background: var(--accent-soft); padding: 3px 10px; border-radius: 999px;
        }
        .order-customer { font-weight: 700; font-size: 0.95rem; }
        .order-email { font-size: 0.82rem; color: var(--muted); }
        .order-date { font-size: 0.82rem; color: var(--muted); }
        .order-total { font-weight: 800; color: var(--green); font-size: 1rem; white-space: nowrap; }
        .empty-state { text-align: center; color: var(--muted); padding: 30px; font-size: 1rem; }

        /* Quick links */
        .quick-links { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 10px; }
        .quick-link {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 12px 20px; border-radius: 14px; font-weight: 700; text-decoration: none;
            font-size: 0.95rem; transition: transform 0.15s, box-shadow 0.15s;
            border: 1px solid transparent;
        }
        .quick-link:hover { transform: translateY(-2px); }
        .quick-link.primary { background: var(--accent); color: #fff; box-shadow: 0 4px 14px rgba(249,115,22,0.28); }
        .quick-link.primary:hover { background: var(--accent-dark); }
        .quick-link.secondary { background: var(--panel-strong); color: var(--text); border-color: var(--border); box-shadow: var(--shadow); }
        .quick-link.secondary:hover { border-color: var(--accent); color: var(--accent-dark); }

        /* Alert */
        .alert { padding: 12px 16px; border-radius: 14px; margin-bottom: 18px; border: 1px solid transparent; }
        .alert.success { background: #fff5eb; color: #9a3412; border-color: #fed7aa; }
    </style>
</head>
<body>
<div class="wrap">

    <!-- Nav -->
    <div class="nav">
        <div class="brand"><span class="brand-mark"></span><span><?= e(APP_NAME) ?></span></div>
        <div class="nav-links">
            <a href="<?= e(url('index.php')) ?>">Home</a>
            <a href="<?= e(url('admin/dashboard.php')) ?>" class="active">Dashboard</a>
            <a href="<?= e(url('admin/products.php')) ?>">Products</a>
            <a href="<?= e(url('admin/categories.php')) ?>">Categories</a>
            <a href="<?= e(url('admin/transactions.php')) ?>">Transactions</a>
            <a href="<?= e(url('auth/logout.php')) ?>">Logout</a>
        </div>
    </div>

    <!-- Hero -->
    <?php if ($success !== null): ?>
        <div class="alert success"><?= e($success) ?></div>
    <?php endif; ?>

    <div class="hero">
        <div class="hero-kicker">⚙ Admin Panel</div>
        <h1>Welcome, <?= e($user['name']) ?></h1>
        <p>Here's an overview of your store.</p>
    </div>

    <!-- Role badge -->
    <div>
        <span class="role-badge">
            <span class="role-dot"></span>
            Role: <?= e(strtoupper($user['role'])) ?>
        </span>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card orders">
            <div class="stat-label">Total Orders</div>
            <div class="stat-value"><?= e((string)(int)$stats['total_orders']) ?></div>
        </div>
        <div class="stat-card revenue">
            <div class="stat-label">Total Revenue</div>
            <div class="stat-value">$<?= e(number_format((float)$stats['total_revenue'], 2)) ?></div>
        </div>
        <div class="stat-card members">
            <div class="stat-label">Members</div>
            <div class="stat-value"><?= e((string)(int)$memberStats['total_members']) ?></div>
        </div>
        <div class="stat-card products">
            <div class="stat-label">Products</div>
            <div class="stat-value"><?= e((string)(int)$productStats['total_products']) ?></div>
        </div>
    </div>

    <!-- Quick links -->
    <div class="quick-links" style="margin-bottom: 28px;">
        <a href="<?= e(url('admin/transactions.php')) ?>" class="quick-link primary">🧾 View All Orders</a>
        <a href="<?= e(url('admin/products.php')) ?>" class="quick-link secondary">📦 Manage Products</a>
        <a href="<?= e(url('admin/categories.php')) ?>" class="quick-link secondary">🗂 Manage Categories</a>
    </div>

    <!-- Recent Orders -->
    <div class="panel">
        <div class="section-title">
            <span>Recent Orders</span>
            <a href="<?= e(url('admin/transactions.php')) ?>">View all →</a>
        </div>

        <?php if (empty($recentOrders)): ?>
            <div class="empty-state">No orders have been placed yet.</div>
        <?php else: ?>
            <?php foreach ($recentOrders as $order): ?>
                <div class="order-row">
                    <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                        <span class="order-id-badge">#<?= e((string)$order['order_id']) ?></span>
                        <div>
                            <div class="order-customer"><?= e($order['user_name']) ?></div>
                            <div class="order-email"><?= e($order['user_email']) ?></div>
                        </div>
                    </div>
                    <div style="text-align:right;">
                        <div class="order-total">$<?= e(number_format((float)$order['total_price'], 2)) ?></div>
                        <div class="order-date"><?= e(date('M j, Y · g:i a', strtotime($order['order_date']))) ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>
</body>
</html>
