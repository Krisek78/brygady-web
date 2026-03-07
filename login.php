<?php
session_start();
define('APP_INIT', true);
require_once __DIR__ . '/config/db.php';

$ip = $_SERVER['REMOTE_ADDR'];
$usernameInput = trim($_POST['username'] ?? '');
$passwordInput = $_POST['password'] ?? '';

// --- 1. SPRAWDZENIE BLOKADY IP ---
$max_attempts = 5;
$lockout_time = 15; // minuty

$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM login_attempts 
    WHERE ip_address = ? 
    AND is_successful = 0 
    AND attempt_time > DATE_SUB(NOW(), INTERVAL ? MINUTE)
");
$stmt->execute([$ip, $lockout_time]);
$failed_attempts = $stmt->fetchColumn();

if ($failed_attempts >= $max_attempts) {
    header("Location: index_login.php?error=lockout"); // Musisz obs³u¿yæ ten komunikat w index_login.php
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!empty($usernameInput) && !empty($passwordInput)) {
        
        $stmt = $pdo->prepare("SELECT id, username, password_hash, role FROM users WHERE username = ?");
        $stmt->execute([$usernameInput]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($passwordInput, $user['password_hash'])) {
            // --- LOGOWANIE UDANE ---
            
            // Rejestruj sukces w logach
            $log = $pdo->prepare("INSERT INTO login_attempts (ip_address, username, is_successful) VALUES (?, ?, 1)");
            $log->execute([$ip, $usernameInput]);

            // Aktualizuj datê ostatniego logowania u¿ytkownika
            $stmtUpdate = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $stmtUpdate->execute([$user['id']]);

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            
            header("Location: dashboard.php");
            exit;
        } else {
            // --- LOGOWANIE NIEUDANE ---
            
            // Rejestruj pora¿kê w logach
            $log = $pdo->prepare("INSERT INTO login_attempts (ip_address, username, is_successful) VALUES (?, ?, 0)");
            $log->execute([$ip, $usernameInput]);

            header("Location: index_login.php?error=1");
            exit;
        }
    } else {
        header("Location: index_login.php?error=empty");
        exit;
    }
}

header("Location: index_login.php");
exit;