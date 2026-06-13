<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';

require_role('admin');

redirect('admin/dashboard.php');
