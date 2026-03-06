<?php
// api/teams.php
ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

/* session_start(); ... (pomijamy dla testu) */

if (!defined('APP_INIT')) define('APP_INIT', true);
require_once __DIR__ . '/../config/db.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    // --- GET: Pobierz zespoły z ludźmi i zadaniami ---
    if ($method === 'GET') {
        $projectId = $_GET['project_id'] ?? 0;
        if (!$projectId) throw new Exception("Brak project_id");

        $stmt = $pdo->prepare("SELECT * FROM teams WHERE project_id = ? ORDER BY team_name ASC");
        $stmt->execute([$projectId]);
        $teams = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($teams as &$team) {
            // Ludzie
            $stmtM = $pdo->prepare("SELECT e.id, e.full_name FROM employees e JOIN team_members tm ON e.id = tm.employee_id WHERE tm.team_id = ?");
            $stmtM->execute([$team['id']]);
            $team['members'] = $stmtM->fetchAll(PDO::FETCH_ASSOC);

            // Zadania
            $stmtT = $pdo->prepare("SELECT id, title FROM tasks WHERE assigned_to_team_id = ?");
            $stmtT->execute([$team['id']]);
            $team['tasks'] = $stmtT->fetchAll(PDO::FETCH_ASSOC);
        }
        echo json_encode($teams);
    }

    // --- POST: Dodaj zespół ---
    elseif ($method === 'POST') {
        $data = json_decode(file_get_contents("php://input"), true);
        if (empty($data['project_id']) || empty($data['team_name'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Brak danych']);
            exit;
        }
        $stmt = $pdo->prepare("INSERT INTO teams (project_id, team_name) VALUES (?, ?)");
        $stmt->execute([$data['project_id'], $data['team_name']]);
        echo json_encode(['success' => true]);
    }

    // --- DELETE: Usuń zespół LUB wyczyść wszystkie przypisania w projekcie ---
    elseif ($method === 'DELETE') {
        $data = json_decode(file_get_contents("php://input"), true);

        // SCENARIUSZ A: Wyczyść wszystkie przypisania w projekcie (RESET)
        if (isset($data['action']) && $data['action'] === 'reset_project' && !empty($data['project_id'])) {
            $projectId = (int)$data['project_id'];
            
            // 1. Usuń wszystkie powiązania ludzie-zespół dla zespołów tego projektu
            // Używamy JOIN, aby usunąć tylko z team_members należących do tego projektu
            $stmtClearMembers = $pdo->prepare("
                DELETE tm FROM team_members tm
                JOIN teams t ON tm.team_id = t.id
                WHERE t.project_id = ?
            ");
            $stmtClearMembers->execute([$projectId]);

            // 2. Cofnij wszystkie zadania przypisane do zespołów tego projektu (ustaw na NULL)
            $stmtClearTasks = $pdo->prepare("
                UPDATE tasks 
                SET assigned_to_team_id = NULL 
                WHERE project_id = ?
            ");
            $stmtClearTasks->execute([$projectId]);

            echo json_encode(['success' => true, 'message' => 'Projekt wyczyszczony']);
            exit;
        }

        // SCENARIUSZ B: Usuń konkretny zespół
        if (!empty($data['id'])) {
            $stmt = $pdo->prepare("DELETE FROM teams WHERE id = ?");
            $stmt->execute([$data['id']]);
            echo json_encode(['success' => true]);
            exit;
        }

        http_response_code(400);
        echo json_encode(['error' => 'Niepoprawne żądanie DELETE']);
    }

    else {
        http_response_code(405);
        echo json_encode(['error' => 'Metoda niedozwolona']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>