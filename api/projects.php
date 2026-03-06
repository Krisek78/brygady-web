<?php
// api/projects.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Dla prostoty na początek
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Obsługa preflight request (OPTIONS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Bezpieczeństwo: Sprawdź czy użytkownik jest zalogowany (sesja)
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Nieautoryzowany dostęp']);
    exit;
}

// Dołącz bazę danych
if (!defined('APP_INIT')) define('APP_INIT', true);
require_once __DIR__ . '/../config/db.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        // --- POBIERANIE PROJEKTÓW ---
        $stmt = $pdo->query("SELECT * FROM projects ORDER BY created_at DESC");
        $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($projects);

    } elseif ($method === 'POST') {
        // --- DODAWANIE NOWEGO PROJEKTU ---
        $data = json_decode(file_get_contents("php://input"), true);

        if (!isset($data['name']) || empty($data['name'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Nazwa projektu jest wymagana']);
            exit;
        }

        $name = trim($data['name']);
        $status = isset($data['status']) ? $data['status'] : 'active';

        $stmt = $pdo->prepare("INSERT INTO projects (name, status) VALUES (?, ?)");
        $stmt->execute([$name, $status]);

        // Zwróć ID nowo utworzonego projektu
        $newId = $pdo->lastInsertId();
        echo json_encode(['success' => true, 'id' => $newId, 'message' => 'Projekt dodany']);

    } 
	
	//
		elseif ($method === 'PUT') {
        $data = json_decode(file_get_contents("php://input"), true);
        if (empty($data['id']) || empty($data['name'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Brak ID lub nazwy']);
            exit;
        }
        $stmt = $pdo->prepare("UPDATE projects SET name = ? WHERE id = ?");
        $stmt->execute([$data['name'], $data['id']]);
        echo json_encode(['success' => true]);
        exit;
    }
	//
		elseif ($method === 'DELETE') {
        // --- USUWANIE PROJEKTU (opcjonalnie na później) ---
        parse_str(file_get_contents("php://input"), $data);
        if (isset($data['id'])) {
            $stmt = $pdo->prepare("DELETE FROM projects WHERE id = ?");
            $stmt->execute([$data['id']]);
            echo json_encode(['success' => true]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Brak ID']);
        }
    //
		elseif ($method === 'PUT') {
		$data = json_decode(file_get_contents("php://input"), true);
    
		if (empty($data['id']) || empty($data['name'])) {
			http_response_code(400);
			echo json_encode(['success' => false, 'message' => 'Brak ID lub nazwy']);
			exit;
		}
    
		$stmt = $pdo->prepare("UPDATE projects SET name = ? WHERE id = ?");
		$stmt->execute([$data['name'], $data['id']]);
    
		echo json_encode(['success' => true]);
}
	//
	
	//
	} else {
        http_response_code(405);
        echo json_encode(['error' => 'Metoda niedozwolona']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Błąd serwera: ' . $e->getMessage()]);
}
?>