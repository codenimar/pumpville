<?php
// DEBUG SCRIPT - Remove after testing
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>PumpVille Admin Debug</h1>";

// Check sessions
echo "<h2>Session Status</h2>";
session_start();
echo "<pre>";
echo "Session ID: " . session_id() . "\n";
echo "Session Data: " . print_r($_SESSION, true) . "\n";
echo "</pre>";

// Check directories
echo "<h2>Directory Permissions</h2>";
$dataDir = __DIR__ . '/data/';
$uploadsDir = __DIR__ . '/assets/uploads/';

echo "<pre>";
echo "Data Directory: $dataDir\n";
echo "Exists: " . (is_dir($dataDir) ? "YES" : "NO") . "\n";
echo "Writable: " . (is_writable($dataDir) ? "YES" : "NO") . "\n";
echo "Permissions: " . substr(sprintf('%o', fileperms($dataDir)), -4) . "\n\n";

echo "Uploads Directory: $uploadsDir\n";
echo "Exists: " . (is_dir($uploadsDir) ? "YES" : "NO") . "\n";
echo "Writable: " . (is_writable($uploadsDir) ? "YES" : "NO") . "\n";
if (is_dir($uploadsDir)) {
    echo "Permissions: " . substr(sprintf('%o', fileperms($uploadsDir)), -4) . "\n";
}
echo "</pre>";

// Check JSON files
echo "<h2>JSON Files</h2>";
$jsonFiles = ['token.json', 'guides.json', 'posts.json', 'industries.json'];
echo "<pre>";
foreach ($jsonFiles as $file) {
    $path = $dataDir . $file;
    echo "$file: " . (file_exists($path) ? "EXISTS" : "MISSING") . "\n";
    if (file_exists($path)) {
        echo "  Readable: " . (is_readable($path) ? "YES" : "NO") . "\n";
        echo "  Writable: " . (is_writable($path) ? "YES" : "NO") . "\n";
    }
}
echo "</pre>";

// Check PHP configuration
echo "<h2>PHP Configuration</h2>";
echo "<pre>";
echo "Upload Max Size: " . ini_get('upload_max_filesize') . "\n";
echo "Post Max Size: " . ini_get('post_max_size') . "\n";
echo "File Uploads Enabled: " . (ini_get('file_uploads') ? "YES" : "NO") . "\n";
echo "</pre>";

// Test file write
echo "<h2>Write Test</h2>";
echo "<pre>";
$testFile = $dataDir . 'write_test.txt';
$result = file_put_contents($testFile, 'test');
if ($result !== false) {
    echo "✓ Can write to data directory\n";
    unlink($testFile);
} else {
    echo "✗ Cannot write to data directory\n";
}
echo "</pre>";

echo "<hr>";
echo "<p><a href='admin.php'>Back to Admin</a></p>";
?>
