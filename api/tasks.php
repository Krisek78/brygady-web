<?php
// api/tasks.php

// 1. UKRYJ B£ÊDY PHP, ABY NIE PSU£Y FORMATU JSON
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Obs³uga ¿¹dañ wstêpnych (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// 2. SESJA (Tymczasowo wy³¹czona dla testów API, odkomentuj w produkcji)
/*
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Nieautoryzowany dostêp']);
    exit;
}
*/

// 3. PO£¥CZENIE Z BAZ¥ DANYCH
if (!defined('APP_INIT')) define('APP_INIT', true);

$configPath = __DIR__ . '/../config/db.php';
if (!file_exists($configPath)) {
    http_response_code(500);
    echo json_encode(['error' => 'Nie znaleziono pliku konfiguracyjnego db.php']);
    exit;
}

require_once $configPath;

$method = $_SERVER['REQUEST_METHOD'];

try {
    // --- GET: Pobierz wszystkie zadania ---
    if ($method === 'GET') {
        $stmt = $pdo->query("SELECT id, title, description, assigned_to_team_id, project_id, status, created_at FROM tasks ORDER BY created_at DESC");
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($tasks);
    }

    // --- POST: Dodaj nowe zadanie ---
    elseif ($method === 'POST') {
        $input = file_get_contents("php://input");
        $data = json_decode($input, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Nieprawid³owy format JSON");
        }

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
    }

    // --- PUT: Aktualizuj zadanie (Przypisz do zespo³u lub cofnij) ---
    // To jest kluczowe dla dzia³ania Drag & Drop!
    elseif ($method === 'PUT') {
        $input = file_get_contents("php://input");
        $data = json_decode($input, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Nieprawid³owy format JSON");
        }

        if (empty($data['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Brak ID zadania']);
            exit;
        }

        // assigned_to_team_id mo¿e byæ liczb¹ (ID zespo³u) lub null (cofniêcie do puli)
        $teamId = isset($data['assigned_to_team_id']) ? $data['assigned_to_team_id'] : null;

        $stmt = $pdo->prepare("UPDATE tasks SET assigned_to_team_id = ? WHERE id = ?");
        $stmt->execute([$teamId, $data['id']]);

        echo json_encode(['success' => true]);
    }

    // --- DELETE: Usuñ zadanie ---
    elseif ($method === 'DELETE') {
        $input = file_get_contents("php://input");
        $data = json_decode($input, true);

        if (empty($data['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Brak ID zadania']);
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ?");
        $stmt->execute([$data['id']]);

        echo json_encode(['success' => true]);
    }

    else {
        http_response_code(405);
        echo json_encode(['error' => 'Metoda niedozwolona']);
    }

} catch (Exception $e) {
    http_response_code(500);
    // Zwracamy b³¹d jako czysty JSON, a nie HTML
    echo json_encode(['error' => $e->getMessage()]);
}
?>