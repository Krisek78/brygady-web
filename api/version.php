<?php
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
ini_set('display_errors', 0);
define('APP_INIT', true);
require_once __DIR__ . '/../config/db.php';

try {
    $stmt = $pdo->query("SELECT version_number, build_date, last_updated FROM system_version ORDER BY id DESC LIMIT 1");
    $version = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode($version);
} catch (Exception $e) {
    echo json_encode(['version_number' => 'unknown', 'build_date' => null]);
}