<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

session_start();
if (!isset($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['error'=>'Auth']); exit; }
if (!defined('APP_INIT')) define('APP_INIT', true);
require_once __DIR__ . '/../config/db.php';

$method = $_SERVER['REQUEST_METHOD'];

// GET: Pobierz zespoły dla projektu ID
if ($method === 'GET') {
    $projectId = $_GET['project_id'] ?? 0;
    
    // Główne zapytanie pobierające zespoły
    $stmt = $pdo->prepare("SELECT * FROM teams WHERE project_id = ? ORDER BY team_name ASC");
    $stmt->execute([$projectId]);
    $teams = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Dla każdego zespołu pobierz ludzi i zadania
    foreach ($teams as &$team) {
        // Ludzie w zespole
        $stmtMembers = $pdo->prepare("
            SELECT e.full_name 
            FROM employees e
            JOIN team_members tm ON e.id = tm.employee_id
            WHERE tm.team_id = ?
        ");
        $stmtMembers->execute([$team['id']]);
        $team['members'] = $stmtMembers->fetchAll(PDO::FETCH_ASSOC);

        // Zadania przypisane do zespołu
        $stmtTasks = $pdo->prepare("SELECT title FROM tasks WHERE assigned_to_team_id = ?");
        $stmtTasks->execute([$team['id']]);
        $team['tasks'] = $stmtTasks->fetchAll(PDO::FETCH_ASSOC);
    }

    echo json_encode($teams);
}
elseif ($method === 'POST') {
    // Dodaj nowy zespół
    $data = json_decode(file_get_contents("php://input"), true);
    if (!isset($data['project_id']) || !isset($data['team_name'])) {
        http_response_code(400); echo json_encode(['success'=>false, 'message'=>'Brak danych']); exit;
    }
    
    $stmt = $pdo->prepare("INSERT INTO teams (project_id, team_name) VALUES (?, ?)");
    $stmt->execute([$data['project_id'], $data['team_name']]);
    echo json_encode(['success'=>true]);
}
elseif ($method === 'DELETE') {
    // Usuń zespół
    $data = json_decode(file_get_contents("php://input"), true);
    if (!isset($data['id'])) { http_response_code(400); exit; }
    $stmt = $pdo->prepare("DELETE FROM teams WHERE id = ?");
    $stmt->execute([$data['id']]);
    echo json_encode(['success'=>true]);
}
?>