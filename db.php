<?php
require_once 'config.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// buat koneksi ke database menggunakan PDO dengan konfigurasi yang sudah didefinisikan di config.php
try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}

// mengecek apakah pengguna sudah login atau belum.
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// mengecek apakah pengguna memiliki peran admin atau tidak.
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// melindungi halaman agar hanya bisa diakses oleh pengguna yang sudah login.
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

// melindungi halaman agar hanya bisa diakses oleh pengguna dengan peran admin.
function requireAdmin() {
    if (!isAdmin()) {
        die('Access Denied: Admin privileges required.');
    }
}

// menyimpan pesan notifikasi (flash message) ke dalam session
function setFlash($message, $type = 'info') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

// mengambil pesan notifikasi (flash message) dari session dan menghapusnya setelah ditampilkan
function getFlash() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'info';
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
        return ['message' => $message, 'type' => $type];
    }
    return null;
}
