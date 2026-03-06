<?php
header('Content-Type: application/json');
ini_set('display_errors', 0); // Wyłącz błędy tekstowe, które psują JSON
define('APP_INIT', true);
require_once __DIR__ . '/../config/db.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $stmt = $pdo->query("SELECT id, full_name FROM employees ORDER BY full_name ASC");
        $res = $stmt->fetchAll();
        foreach($res as &$r) { $r['id'] = (int)$r['id']; }
        echo json_encode($res);
    } 
    elseif ($method === 'POST') {
        $data = json_decode(file_get_contents("php://input"), true);
        $name = isset($data['full_name']) ? trim($data['full_name']) : '';

        if (empty($name)) {
            echo json_encode(['success' => false, 'message' => "Imię i nazwisko są wymagane."]);
            exit;
        }

        // 1. Sprawdź duplikat tradycyjnym SELECT (najbezpieczniej dla JS)
        $check = $pdo->prepare("SELECT full_name FROM employees WHERE LOWER(full_name) = LOWER(?)");
        $check->execute([$name]);
        $existing = $check->fetch();

        if ($existing) {
            echo json_encode([
                'success' => false, 
                'message' => "PRACOWNIK JUŻ ISTNIEJE: Osoba o nazwisku '" . $existing['full_name'] . "' jest już w bazie danych."
            ]);
            exit;
        }

        // 2. Jeśli nie ma duplikatu, spróbuj dodać
        $stmt = $pdo->prepare("INSERT INTO employees (full_name) VALUES (?)");
        $stmt->execute([$name]);
        
        echo json_encode(['success' => true]);
    }
} catch (Exception $e) {
    // Nawet w przypadku błędu bazy, zwróć JSON
    echo json_encode(['success' => false, 'message' => "Błąd serwera: " . $e->getMessage()]);
}