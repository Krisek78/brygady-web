<?php
// api/update_version.php
session_start();
header('Content-Type: application/json');
ini_set('display_errors', 0);
define('APP_INIT', true);
require_once __DIR__ . '/../config/db.php';

// Tylko admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Brak uprawnień']);
    exit;
}

try {
    // Pobierz datę modyfikacji kluczowych plików
    $jsFile = __DIR__ . '/../js/dashboard.js';
    $cssFile = __DIR__ . '/../css/dashboard.css';
    
    $jsTime = file_exists($jsFile) ? filemtime($jsFile) : time();
    $cssTime = file_exists($cssFile) ? filemtime($cssFile) : time();
    
    // Weź nowszą datę
    $lastModified = max($jsTime, $cssTime);
    $buildDate = date('Y-m-d H:i:s', $lastModified);
    
    // Zwiększ numer wersji (opcjonalnie) lub zostaw ten sam z nową datą
    $stmt = $pdo->query("SELECT version_number FROM system_version ORDER BY id DESC LIMIT 1");
    $current = $stmt->fetch();
    $version = $current ? $current['version_number'] : '1.0.0';
    
    // Zapisz do bazy
    $stmt = $pdo->prepare("UPDATE system_version SET build_date = ?, version_number = ? WHERE id = (SELECT id FROM system_version ORDER BY id DESC LIMIT 1)");
    $stmt->execute([$buildDate, $version]);
    
    // Jeśli nie było rekordu, utwórz
    if ($stmt->rowCount() == 0) {
        $pdo->prepare("INSERT INTO system_version (version_number, build_date) VALUES (?, ?)")
            ->execute([$version, $buildDate]);
    }
    
    echo json_encode([
        'success' => true, 
        'build_date' => $buildDate,
        'version' => $version
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}