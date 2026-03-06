<?php
session_start();
header('Content-Type: application/json');
ini_set('display_errors', 0);
define('APP_INIT', true);

try {
    require_once __DIR__ . '/../config/db.php';

    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Brak uprawnień.']);
        exit;
    }

    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        $stmt = $pdo->query("SELECT id, username, full_name, role, created_at, last_login FROM users ORDER BY username ASC");
        echo json_encode($stmt->fetchAll());
    } 
    elseif ($method === 'POST') {
        $data = json_decode(file_get_contents("php://input"), true);
        $user = trim($data['username'] ?? '');
        $name = trim($data['full_name'] ?? '');
        $pass = $data['password'] ?? '';
        $role = $data['role'] ?? 'user';

        if (empty($user) || empty($name) || strlen($pass) < 4) {
            throw new Exception("Wszystkie pola są wymagane (hasło min. 4 znaki).");
        }

        $check = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $check->execute([$user]);
        if ($check->fetch()) throw new Exception("Login jest już zajęty.");

        $hash = password_hash($pass, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("INSERT INTO users (username, full_name, password_hash, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user, $name, $hash, $role]);
        echo json_encode(['success' => true]);
    }
    elseif ($method === 'PUT') {
        $data = json_decode(file_get_contents("php://input"), true);
        if (empty($data['id']) || empty($data['password'])) throw new Exception("Brak danych.");

        $hash = password_hash($data['password'], PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        $stmt->execute([$hash, (int)$data['id']]);
        echo json_encode(['success' => true]);
    }
    elseif ($method === 'DELETE') {
        $data = json_decode(file_get_contents("php://input"), true);
        if ($data['id'] == $_SESSION['user_id']) throw new Exception("Nie usuwaj własnego konta!");
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([(int)$data['id']]);
        echo json_encode(['success' => true]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}