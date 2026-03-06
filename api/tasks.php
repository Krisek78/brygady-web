<?php
header('Content-Type: application/json');
ini_set('display_errors', 0);
define('APP_INIT', true);
require_once __DIR__ . '/../config/db.php';

$method = $_SERVER['REQUEST_METHOD'];
try {
    if ($method === 'GET') {
        $stmt = $pdo->query("SELECT id, title, assigned_to_team_id FROM tasks");
        $res = $stmt->fetchAll();
        foreach($res as &$r) { 
            $r['id'] = (int)$r['id']; 
            $r['assigned_to_team_id'] = $r['assigned_to_team_id'] ? (int)$r['assigned_to_team_id'] : null;
        }
        echo json_encode($res);
    } elseif ($method === 'POST') {
        $data = json_decode(file_get_contents("php://input"), true);
        $stmt = $pdo->prepare("INSERT INTO tasks (title, project_id) VALUES (?, ?)");
        $stmt->execute([$data['title'], (int)$data['project_id']]);
        echo json_encode(['success' => true]);
    } elseif ($method === 'PUT') {
        $data = json_decode(file_get_contents("php://input"), true);
        $teamId = !empty($data['assigned_to_team_id']) ? (int)$data['assigned_to_team_id'] : null;
        $stmt = $pdo->prepare("UPDATE tasks SET assigned_to_team_id = ? WHERE id = ?");
        $stmt->execute([$teamId, (int)$data['id']]);
        echo json_encode(['success' => true]);
    }
} catch (Exception $e) { echo json_encode(['success' => false, 'message' => $e->getMessage()]); }