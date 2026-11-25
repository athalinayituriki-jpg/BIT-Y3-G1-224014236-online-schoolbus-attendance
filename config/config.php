<?php
declare(strict_types=1);

// XAMPP defaults. Set these to match your local MySQL installation.
$mapsKeyFile = __DIR__ . '/maps.local.php';
$mapsKey = is_file($mapsKeyFile) ? require $mapsKeyFile : (getenv('GOOGLE_MAPS_EMBED_API_KEY') ?: '');
define('GOOGLE_MAPS_EMBED_API_KEY', is_string($mapsKey) ? trim($mapsKey) : '');
$host = 'localhost';
$db = 'school_attendance';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host={$host};dbname={$db};charset={$charset}";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    error_log('[Database connection] ' . $e->getMessage());
    http_response_code(500);
    exit('Database connection failed. Confirm MySQL is running and import database/schema.sql.');
}
