<?php
header('Content-Type: application/json');
ini_set('display_errors', 0);
define('APP_INIT', true);
require_once __DIR__ . '/../config/db.php';

$method = $_SERVER['REQUEST_METHOD'];
try {
    if ($method === 'GET') {
        $stmt = $pdo->query("SELECT id, full_name FROM employees ORDER BY full_name ASC");
        $res = $stmt->fetchAll();
        foreach($res as &$r) { $r['id'] = (int)$r['id']; }
        echo json_encode($res);
    } 
    elseif ($method === 'POST') {
        $data = json_decode(file_get_contents("php://input"), true);
        $name = trim($data['full_name'] ?? '');
        if (empty($name)) throw new Exception("Brak nazwy");

        // Sprawdź duplikat
        $check = $pdo->prepare("SELECT id FROM employees WHERE LOWER(full_name) = LOWER(?)");
        $check->execute([$name]);
        if ($check->fetch()) {
            echo json_encode(['success' => false, 'message' => "Pracownik o tym nazwisku już istnieje!"]);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO employees (full_name) VALUES (?)");
        $stmt->execute([$name]);
        echo json_encode(['success' => true]);
    }
    elseif ($method === 'PUT') {
        $data = json_decode(file_get_contents("php://input"), true);
        if (empty($data['id']) || empty($data['full_name'])) throw new Exception("Brak danych");

        $stmt = $pdo->prepare("UPDATE employees SET full_name = ? WHERE id = ?");
        $stmt->execute([trim($data['full_name']), (int)$data['id']]);
        echo json_encode(['success' => true]);
    }
    elseif ($method === 'DELETE') {
        $data = json_decode(file_get_contents("php://input"), true);
        if (empty($data['id'])) throw new Exception("Brak ID");

        // SQL automatycznie usunie przypisania z team_members jeśli masz ustawione ON DELETE CASCADE
        $stmt = $pdo->prepare("DELETE FROM employees WHERE id = ?");
        $stmt->execute([(int)$data['id']]);
        echo json_encode(['success' => true]);
    }
} catch (Exception $e) { echo json_encode(['success' => false, 'message' => $e->getMessage()]); }