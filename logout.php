<?php
require_once 'db.php';
session_destroy();
// untuk mengarahkan pengguna kembali ke halaman login setelah logout
header('Location: login.php');
exit;
