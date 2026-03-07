<?php
header('Content-Type: application/json');
ini_set('display_errors', 0);
define('APP_INIT', true);
require_once __DIR__ . '/../config/db.php';

$method = $_SERVER['REQUEST_METHOD'];
try {
    if ($method === 'GET') {
        $stmt = $pdo->query("SELECT id, name FROM projects ORDER BY id DESC");
        $res = $stmt->fetchAll();
        foreach($res as &$r) { $r['id'] = (int)$r['id']; }
        echo json_encode($res);
    } elseif ($method === 'POST') {
        $data = json_decode(file_get_contents("php://input"), true);
        $name = trim($data['name'] ?? '');
        if ($name === '') {
            throw new Exception('Brak nazwy budowy');
        }

        // Unikalna nazwa budowy (case-insensitive, globalnie)
        $check = $pdo->prepare("SELECT id FROM projects WHERE LOWER(name) = LOWER(?)");
        $check->execute([$name]);
        if ($check->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Budowa o tej nazwie już istnieje']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO projects (name) VALUES (?)");
        $stmt->execute([$name]);
        echo json_encode(['success' => true]);
    } elseif ($method === 'PUT') {
        $data = json_decode(file_get_contents("php://input"), true);
        $id   = (int)($data['id'] ?? 0);
        $name = trim($data['name'] ?? '');
        if (!$id || $name === '') {
            throw new Exception('Brak ID lub nazwy budowy');
        }

        // Sprawdzenie unikalności przy zmianie nazwy
        $check = $pdo->prepare("SELECT id FROM projects WHERE LOWER(name) = LOWER(?) AND id <> ?");
        $check->execute([$name, $id]);
        if ($check->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Inna budowa o tej nazwie już istnieje']);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE projects SET name = ? WHERE id = ?");
        $stmt->execute([$name, $id]);
        echo json_encode(['success' => true]);
    } elseif ($method === 'DELETE') {
        $data = json_decode(file_get_contents("php://input"), true);
        $stmt = $pdo->prepare("DELETE FROM projects WHERE id = ?");
        $stmt->execute([(int)$data['id']]);
        echo json_encode(['success' => true]);
    }
} catch (Exception $e) { echo json_encode(['success' => false, 'message' => $e->getMessage()]); }