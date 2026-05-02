<?php
session_start();
define('ADMIN_PIN', '12334');
define('DATA_DIR', __DIR__ . '/../data/');
define('UPLOADS_DIR', __DIR__ . '/../assets/uploads/');
define('UPLOADS_URL', 'assets/uploads/');

function isAdminLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function requireAdmin() {
    if (!isAdminLoggedIn()) {
        header('Location: admin.php');
        exit;
    }
}

function loadJson($file) {
    $path = DATA_DIR . $file;
    if (!file_exists($path)) return [];
    return json_decode(file_get_contents($path), true) ?? [];
}

function saveJson($file, $data) {
    $path = DATA_DIR . $file;
    return file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function currentPage() {
    return basename($_SERVER['PHP_SELF']);
}
