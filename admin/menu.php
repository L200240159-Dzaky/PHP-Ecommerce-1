<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';

require_role('admin');

?>
<ul>
    <li><a href="<?= e(url('admin/products.php')) ?>">Products</a></li>
    <li><a href="<?= e(url('admin/categories.php')) ?>">Categories</a></li>
</ul>
