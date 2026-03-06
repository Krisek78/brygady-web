<?php
// api/tasks.php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Tymczasowo pomijamy sesjê dla testu (zgodnie z poprzedni¹ strategi¹)
/*
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}
*/

if (!defined('APP_INIT')) define('APP_INIT', true);

$configPath = __DIR__ . '/../config/db.php';
if (!file_exists($configPath)) {
    http_response_code(500);
    echo json_encode(['error' => 'Config file not found']);
    exit;
}

require_once $configPath;

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        // Pobierz wszystkie zadania
        $stmt = $pdo->query("SELECT id, title, description, assigned_to_team_id, project_id, status FROM tasks ORDER BY created_at DESC");
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($tasks);
        
    } elseif ($method === 'POST') {
        // Dodaj nowe zadanie
        $input = file_get_contents("php://input");
        $data = json_decode($input, true);

        if (empty($data['title']) || empty($data['project_id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Brak tytu³u lub ID projektu']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO tasks (title, description, project_id, status, assigned_to_team_id) VALUES (?, ?, ?, 'pending', NULL)");
        $stmt->execute([
            $data['title'],
            $data['description'] ?? '',
            $data['project_id']
        ]);

        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);

    } elseif ($method === 'DELETE') {
        // Usuñ zadanie (opcjonalne)
        $input = file_get_contents("php://input");
        $data = json_decode($input, true);
        
        if (empty($data['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Brak ID']);
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ?");
        $stmt->execute([$data['id']]);
        echo json_encode(['success' => true]);
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>