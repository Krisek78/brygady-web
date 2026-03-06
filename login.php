<?php
session_start();                             // 1. start sesji jako pierwsze
define('APP_INIT', true);                   // 2. zezwolenie na include db.php
require_once __DIR__ . '/config/db.php';    // 3. wczytanie po³¹czenia z baz¹

$errorMsg = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usernameInput = $_POST['username'] ?? '';
    $passwordInput = $_POST['password'] ?? '';

    if (!empty($usernameInput) && !empty($passwordInput)) {
        $stmt = $pdo->prepare("SELECT id, username, password_hash, role FROM users WHERE username = ?");
        $stmt->execute([$usernameInput]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($passwordInput, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            header("Location: dashboard.php");
            exit;
        } else {
            $errorMsg = "Nieprawid³owy login lub has³o.";
        }
    } else {
        $errorMsg = "Wype³nij wszystkie pola.";
    }
}
header("Location: index_login.php?error=1");
exit;
?>