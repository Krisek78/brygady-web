<?php
// api/team-members.php
// Wymuszamy nag³ówek JSON, nawet przy b³êdzie
header('Content-Type: application/json');

// Ukrywamy b³êdy HTML, aby nie psu³y JSONa
ini_set('display_errors', 0);
error_reporting(E_ALL);

if (!defined('APP_INIT')) define('APP_INIT', true);

try {
    $configPath = __DIR__ . '/../config/db.php';
    if (!file_exists($configPath)) {
        throw new Exception("Brak pliku config/db.php");
    }
    require_once $configPath;

    $method = $_SERVER['REQUEST_METHOD'];

    // 1. Obs³uga GET
    if ($method === 'GET') {
        if (isset($_GET['action']) && $_GET['action'] === 'get_all_assigned') {
            $stmt = $pdo->query("SELECT employee_id FROM team_members");
            $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
            echo json_encode(['assigned_ids' => array_map('intval', $ids)]);
            exit;
        }
        
        if (isset($_GET['action']) && $_GET['action'] === 'get_all_assignments') {
            $sql = "SELECT tm.employee_id, p.name as project_name, t.team_name 
                    FROM team_members tm
                    JOIN teams t ON tm.team_id = t.id
                    JOIN projects p ON t.project_id = p.id";
            $stmt = $pdo->query($sql);
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            exit;
        }
        echo json_encode([]); // Domyœlna odpowiedŸ dla GET
        exit;
    }

    // 2. Obs³uga POST/DELETE
    $input = json_decode(file_get_contents("php://input"), true);

    if ($method === 'POST') {
        if (empty($input['employee_id']) || empty($input['team_id'])) {
            throw new Exception("Brak danych pracownika lub zespo³u");
        }

        $check = $pdo->prepare("SELECT id FROM team_members WHERE employee_id = ?");
        $check->execute([$input['employee_id']]);
        if ($check->rowCount() > 0) {
            echo json_encode(['success' => false, 'message' => 'Pracownik ju¿ przypisany!']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO team_members (team_id, employee_id) VALUES (?, ?)");
        $stmt->execute([$input['team_id'], $input['employee_id']]);
        echo json_encode(['success' => true]);
    } 
    elseif ($method === 'DELETE') {
        if (empty($input['employee_id']) || empty($input['team_id'])) {
            throw new Exception("Brak danych do usuniêcia");
        }
        $stmt = $pdo->prepare("DELETE FROM team_members WHERE team_id = ? AND employee_id = ?");
        $stmt->execute([$input['team_id'], $input['employee_id']]);
        echo json_encode(['success' => true]);
    }

} catch (Exception $e) {
    // Jeœli wyst¹pi b³¹d, zwróæ go jako poprawny JSON, a nie HTML
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}