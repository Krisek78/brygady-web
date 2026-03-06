<?php
header('Content-Type: application/json');
if (!defined('APP_INIT')) define('APP_INIT', true);
require_once __DIR__ . '/../config/db.php';

$method = $_SERVER['REQUEST_METHOD'];

// Pobranie przypisañ dla lewej puli (filtrowanie)
/*if ($method === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_all_assigned') {
    $stmt = $pdo->query("SELECT employee_id FROM team_members");
    $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo json_encode(['assigned_ids' => array_map('intval', $ids)]);
    exit;
}
*/

if ($method === 'GET') {
    if (isset($_GET['action']) && $_GET['action'] === 'get_all_assigned') {
        $stmt = $pdo->query("SELECT employee_id FROM team_members");
        $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo json_encode(['assigned_ids' => array_map('intval', $ids)]);
        exit;
    }
}

// Dane dla widoku "Ludzie"
if ($method === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_all_assignments') {
    $sql = "SELECT tm.employee_id, p.name as project_name, t.team_name 
            FROM team_members tm
            JOIN teams t ON tm.team_id = t.id
            JOIN projects p ON t.project_id = p.id";
    $stmt = $pdo->query($sql);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents("php://input"), true);
    // BLOKADA: SprawdŸ czy pracownik jest ju¿ zajêty
    $check = $pdo->prepare("SELECT id FROM team_members WHERE employee_id = ?");
    $check->execute([$input['employee_id']]);
    if ($check->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'Pracownik jest ju¿ na innej budowie!']);
        exit;
    }
    $stmt = $pdo->prepare("INSERT INTO team_members (team_id, employee_id) VALUES (?, ?)");
    $stmt->execute([$input['team_id'], $input['employee_id']]);
    echo json_encode(['success' => true]);
} 
elseif ($method === 'DELETE') {
    $input = json_decode(file_get_contents("php://input"), true);
    $stmt = $pdo->prepare("DELETE FROM team_members WHERE team_id = ? AND employee_id = ?");
    $stmt->execute([$input['team_id'], $input['employee_id']]);
    echo json_encode(['success' => true]);
}