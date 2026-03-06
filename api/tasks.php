<?php
header('Content-Type: application/json');
ini_set('display_errors', 0);
define('APP_INIT', true);
require_once __DIR__ . '/../config/db.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    // --- 1. POBIERANIE WSZYSTKICH ZADAŃ ---
    if ($method === 'GET') {
        $stmt = $pdo->query("SELECT id, title, assigned_to_team_id, project_id FROM tasks ORDER BY created_at DESC");
        $res = $stmt->fetchAll();
        
        foreach($res as &$r) { 
            $r['id'] = (int)$r['id']; 
            $r['project_id'] = (int)$r['project_id'];
            $r['assigned_to_team_id'] = $r['assigned_to_team_id'] ? (int)$r['assigned_to_team_id'] : null;
        }
        echo json_encode($res);
    } 

    // --- 2. DODAWANIE NOWEGO ZADANIA ---
    elseif ($method === 'POST') {
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (empty($data['title']) || empty($data['project_id'])) {
            echo json_encode(['success' => false, 'message' => 'Brak tytułu zadania lub ID projektu!']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO tasks (title, project_id, status) VALUES (?, ?, 'pending')");
        $stmt->execute([
            trim($data['title']), 
            (int)$data['project_id']
        ]);
        
        echo json_encode(['success' => true]);
    } 

    // --- 3. PRZYPISYWANIE ZADANIA DO ZESPOŁU (LUB COFANIE) ---
    elseif ($method === 'PUT') {
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (empty($data['id'])) {
            echo json_encode(['success' => false, 'message' => 'Brak ID zadania!']);
            exit;
        }

        // Jeśli assigned_to_team_id jest pusty/null, zadanie wróci do puli
        $teamId = !empty($data['assigned_to_team_id']) ? (int)$data['assigned_to_team_id'] : null;

        $stmt = $pdo->prepare("UPDATE tasks SET assigned_to_team_id = ? WHERE id = ?");
        $stmt->execute([$teamId, (int)$data['id']]);
        
        echo json_encode(['success' => true]);
    }

    // --- 4. USUWANIE ZADANIA (OPCJONALNIE) ---
    elseif ($method === 'DELETE') {
        $data = json_decode(file_get_contents("php://input"), true);
        if (empty($data['id'])) throw new Exception("Brak ID");
        
        $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ?");
        $stmt->execute([(int)$data['id']]);
        
        echo json_encode(['success' => true]);
    }

} catch (Exception $e) { 
    echo json_encode(['success' => false, 'message' => "Błąd serwera: " . $e->getMessage()]); 
}