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
        $stmt = $pdo->prepare("INSERT INTO projects (name) VALUES (?)");
        $stmt->execute([$data['name']]);
        echo json_encode(['success' => true]);
    } elseif ($method === 'PUT') {
        $data = json_decode(file_get_contents("php://input"), true);
        $stmt = $pdo->prepare("UPDATE projects SET name = ? WHERE id = ?");
        $stmt->execute([$data['name'], (int)$data['id']]);
        echo json_encode(['success' => true]);
    } elseif ($method === 'DELETE') {
        $data = json_decode(file_get_contents("php://input"), true);
        $stmt = $pdo->prepare("DELETE FROM projects WHERE id = ?");
        $stmt->execute([(int)$data['id']]);
        echo json_encode(['success' => true]);
    }
} catch (Exception $e) { echo json_encode(['success' => false, 'message' => $e->getMessage()]); }