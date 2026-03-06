<?php
// api/team-members.php

// Ustawienia PHP
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Nag³ówki CORS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Obs³uga preflight request (OPTIONS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Sprawdzenie sesji (odkomentuj w produkcji)
/*
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Nieautoryzowany dostêp']);
    exit;
}
*/

// £¹czenie z baz¹ danych
if (!defined('APP_INIT')) define('APP_INIT', true);
require_once __DIR__ . '/../config/db.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    // --- GET: Pobieranie danych o przypisaniach ---
    if ($method === 'GET') {
        // Akcja 1: Pobierz ID wszystkich pracowników przypisanych do jakiegokolwiek zespo³u globalnie
        if (isset($_GET['action']) && $_GET['action'] === 'get_all_assigned') {
            $stmt = $pdo->query("SELECT DISTINCT employee_id FROM team_members");
            $assignedIds = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
            echo json_encode(['assigned_ids' => $assignedIds]);
            exit;
        }

        // Akcja 2: Pobierz pe³ne informacje o przypisaniach (pracownik -> projekt)
        if (isset($_GET['action']) && $_GET['action'] === 'get_all_assignments') {
            $stmt = $pdo->query("
                SELECT tm.employee_id, p.name as project_name, t.team_name
                FROM team_members tm
                JOIN teams t ON tm.team_id = t.id
                JOIN projects p ON t.project_id = p.id
                GROUP BY tm.employee_id
            ");
            $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($assignments);
            exit;
        }

        // Domyœlne zachowanie GET (jeœli chcesz pobraæ wszystkie przypisania)
        $stmt = $pdo->query("SELECT * FROM team_members");
        $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($assignments);
    }

    // --- POST: Dodaj pracownika do zespo³u ---
    elseif ($method === 'POST') {
        $input = file_get_contents("php://input");
        $data = json_decode($input, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Nieprawid³owy format JSON: " . json_last_error_msg());
        }

        if (empty($data['team_id']) || empty($data['employee_id'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'message' => 'Brak wymaganych pól: team_id lub employee_id'
            ]);
            exit;
        }

        // SprawdŸ, czy pracownik ju¿ jest przypisany do jakiegokolwiek zespo³u globalnie
        $checkGlobal = $pdo->prepare("SELECT id FROM team_members WHERE employee_id = ?");
        $checkGlobal->execute([$data['employee_id']]);
        
        if ($checkGlobal->rowCount() > 0) {
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'message' => 'Ten pracownik jest ju¿ przypisany do innej budowy! Aby go przenieœæ, najpierw usuñ go z poprzedniej.'
            ]);
            exit;
        }

        // SprawdŸ, czy pracownik ju¿ jest w tym zespole (dodatkowa walidacja)
        $checkLocal = $pdo->prepare("SELECT id FROM team_members WHERE team_id = ? AND employee_id = ?");
        $checkLocal->execute([$data['team_id'], $data['employee_id']]);
        
        if ($checkLocal->rowCount() > 0) {
            echo json_encode([
                'success' => false, 
                'message' => 'Ten pracownik jest ju¿ w tym zespole!'
            ]);
            exit;
        }

        // Dodaj pracownika do zespo³u
        $stmt = $pdo->prepare("INSERT INTO team_members (team_id, employee_id) VALUES (?, ?)");
        $stmt->execute([$data['team_id'], $data['employee_id']]);

        echo json_encode(['success' => true]);
    }

    // --- DELETE: Usuñ pracownika z zespo³u ---
    elseif ($method === 'DELETE') {
        $input = file_get_contents("php://input");
        $data = json_decode($input, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Nieprawid³owy format JSON: " . json_last_error_msg());
        }

        if (empty($data['team_id']) || empty($data['employee_id'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'message' => 'Brak wymaganych pól: team_id lub employee_id'
            ]);
            exit;
        }

        // Usuñ pracownika z zespo³u
        $stmt = $pdo->prepare("DELETE FROM team_members WHERE team_id = ? AND employee_id = ?");
        $stmt->execute([$data['team_id'], $data['employee_id']]);

        // SprawdŸ, czy coœ zosta³o usuniête
        if ($stmt->rowCount() === 0) {
            echo json_encode([
                'success' => true, 
                'message' => 'Rekord nie istnia³ lub ju¿ zosta³ usuniêty'
            ]);
        } else {
            echo json_encode(['success' => true]);
        }
    }

    // --- Metoda niedozwolona ---
    else {
        http_response_code(405);
        echo json_encode(['error' => 'Metoda niedozwolona']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>