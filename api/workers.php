<?php
// --- TEMPORARY DEBUGGING ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// ---------------------------

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

session_start();
// Tymczasowo wyłączamy sprawdzanie sesji dla testu, jeśli problem leży w sesji, 
// ale najpierw sprawdźmy bazę. Jeśli chcesz zachować bezpieczeństwo, zostaw to:
if (!isset($_SESSION['user_id'])) { 
    // http_response_code(401); 
    // echo json_encode(['error'=>'Auth']); 
    // exit; 
    // UWAGA: Powyższe linie komentuję TYLKO DO TESTU. Jeśli działają, odkomentuj je później!
}

if (!defined('APP_INIT')) define('APP_INIT', true);

// Sprawdź czy plik istnieje przed require
if (!file_exists(__DIR__ . '/../config/db.php')) {
    die(json_encode(['error' => 'Nie znaleziono pliku config/db.php']));
}

require_once __DIR__ . '/../config/db.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $stmt = $pdo->query("SELECT * FROM employees ORDER BY full_name ASC");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    } 
    elseif ($method === 'POST') {
        $data = json_decode(file_get_contents("php://input"), true);
        if (!isset($data['full_name'])) { 
            http_response_code(400); 
            echo json_encode(['success'=>false, 'message'=>'Brak imienia']); 
            exit; 
        }
        
        $stmt = $pdo->prepare("INSERT INTO employees (full_name) VALUES (?)");
        $stmt->execute([$data['full_name']]);
        echo json_encode(['success'=>true]);
    }
} catch (PDOException $e) {
    // To jest kluczowe - zwróci dokładny błąd SQL do przeglądarki
    http_response_code(500);
    echo json_encode(['error' => 'Błąd Bazy Danych: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Błąd Ogólny: ' . $e->getMessage()]);
}
?>