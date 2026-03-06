<?php
header('Content-Type: application/json');
ini_set('display_errors', 0);
define('APP_INIT', true);
require_once __DIR__ . '/../config/db.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $projectId = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
        if (!$projectId) {
            echo json_encode([]);
            exit;
        }

        $stmt = $pdo->prepare("SELECT id, team_name FROM teams WHERE project_id = ? ORDER BY team_name ASC");
        $stmt->execute([$projectId]);
        $teams = $stmt->fetchAll();

        foreach ($teams as &$team) {
            $team['id'] = (int)$team['id'];
            
            // Pobierz ludzi
            $stM = $pdo->prepare("SELECT e.id, e.full_name FROM employees e JOIN team_members tm ON e.id = tm.employee_id WHERE tm.team_id = ?");
            $stM->execute([$team['id']]);
            $team['members'] = $stM->fetchAll();
            foreach($team['members'] as &$m) { $m['id'] = (int)$m['id']; }

            // Pobierz zadania
            $stT = $pdo->prepare("SELECT id, title FROM tasks WHERE assigned_to_team_id = ?");
            $stT->execute([$team['id']]);
            $team['tasks'] = $stT->fetchAll();
            foreach($team['tasks'] as &$t) { $t['id'] = (int)$t['id']; }
        }
        echo json_encode($teams);

    } elseif ($method === 'POST') {
        $data = json_decode(file_get_contents("php://input"), true);
        if (empty($data['project_id']) || empty($data['team_name'])) throw new Exception("Brak danych");
        
        $stmt = $pdo->prepare("INSERT INTO teams (project_id, team_name) VALUES (?, ?)");
        $stmt->execute([(int)$data['project_id'], $data['team_name']]);
        echo json_encode(['success' => true]);

    } elseif ($method === 'PUT') {
        $data = json_decode(file_get_contents("php://input"), true);
        if (empty($data['id']) || empty($data['team_name'])) throw new Exception("Brak ID lub nazwy");
        
        $stmt = $pdo->prepare("UPDATE teams SET team_name = ? WHERE id = ?");
        $stmt->execute([$data['team_name'], (int)$data['id']]);
        echo json_encode(['success' => true]);

    } elseif ($method === 'DELETE') {
        $data = json_decode(file_get_contents("php://input"), true);
        if (isset($data['action']) && $data['action'] === 'reset_project') {
            $st1 = $pdo->prepare("DELETE tm FROM team_members tm JOIN teams t ON tm.team_id = t.id WHERE t.project_id = ?");
            $st1->execute([(int)$data['project_id']]);
            $st2 = $pdo->prepare("UPDATE tasks SET assigned_to_team_id = NULL WHERE project_id = ?");
            $st2->execute([(int)$data['project_id']]);
        } else {
            if (empty($data['id'])) throw new Exception("Brak ID");
            $stmt = $pdo->prepare("DELETE FROM teams WHERE id = ?");
            $stmt->execute([(int)$data['id']]);
        }
        echo json_encode(['success' => true]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}