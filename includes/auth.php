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
    // Only allow safe filenames: alphanumeric, underscore, hyphen, ending in .json
    if (!preg_match('/^[a-z0-9_\-]+\.json$/i', $file)) return [];
    $path = DATA_DIR . $file;
    if (!file_exists($path)) return [];
    return json_decode(file_get_contents($path), true) ?? [];
}

function saveJson($file, $data) {
    if (!preg_match('/^[a-z0-9_\-]+\.json$/i', $file)) return false;
    $path = DATA_DIR . $file;
    return file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function currentPage() {
    return basename($_SERVER['PHP_SELF']);
}
