<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';

require_role('member');

redirect('member/dashboard.php');
