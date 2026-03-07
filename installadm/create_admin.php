<?php
define('APP_INIT', true);                            // 1) zezwalamy na include db.php
require_once __DIR__ . '/../config/db.php';          // 2) poprawna ścieżka do pliku konfiguracyjnego


// install/create_admin.php
// Jednorazowy skrypt do utworzenia pierwszego konta administratora.
// Po udanym utworzeniu KONIECZNIE usuń ten plik (lub cały katalog install).

session_start();

require_once __DIR__ . '/../config/db.php';


$info  = '';
$error = '';

// Sprawdź, czy istnieje już jakikolwiek admin
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
    $existingAdmins = (int)$stmt->fetchColumn();
} catch (PDOException $e) {
    $error = 'Błąd połączenia z bazą danych lub brak tabeli "users". 
Upewnij się, że wykonałeś skrypt SQL tworzący tabelę users.<br>
Szczegóły: ' . htmlspecialchars($e->getMessage());
    $existingAdmins = 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error)) {

    if ($existingAdmins > 0) {
        $error = 'Konto administratora już istnieje. 
Ze względów bezpieczeństwa ten instalator powinien zostać usunięty (katalog "install").';
    } else {
        $username  = trim($_POST['username']  ?? '');
        $password  = $_POST['password']       ?? '';
        $password2 = $_POST['password2']      ?? '';

        // Walidacja podstawowa
        if ($username === '' || $password === '' || $password2 === '') {
            $error = 'Wypełnij wszystkie pola.';
        } elseif ($password !== $password2) {
            $error = 'Hasła nie są identyczne.';
        } elseif (strlen($password) < 8) {
            $error = 'Hasło musi mieć co najmniej 8 znaków.';
        } else {
            // Sprawdź czy użytkownik o takiej nazwie już istnieje
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $userExists = (int)$stmt->fetchColumn();

            if ($userExists > 0) {
                $error = 'Użytkownik o podanej nazwie już istnieje.';
            } else {
                // Hash hasła (bcrypt przez password_hash / PASSWORD_DEFAULT)
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);

                try {
                    $stmt = $pdo->prepare(
                        "INSERT INTO users (username, password_hash, role) 
                         VALUES (?, ?, 'admin')"
                    );
                    $stmt->execute([$username, $passwordHash]);

                    $info = 'Konto administratora zostało utworzone pomyślnie.<br>
Login: <strong>' . htmlspecialchars($username) . '</strong><br><br>
Teraz <strong>KONIECZNIE usuń katalog <code>install</code> lub przynajmniej plik <code>create_admin.php</code></strong>, 
aby nikt niepowołany nie mógł utworzyć kolejnego konta admina.';

                } catch (PDOException $e) {
                    $error = 'Błąd podczas zapisu do bazy danych: ' . htmlspecialchars($e->getMessage());
                }
            }
        }
    }
}

// Jeśli admin już istnieje, a nie było POST – nie pokazujemy formularza
$showForm = ($existingAdmins === 0 && empty($info));
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Instalator - tworzenie konta administratora</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { 
            font-family: Arial, sans-serif; 
            background-color: #f4f4f4; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            min-height: 100vh; 
            margin: 0;
        }
        .box {
            background: #ffffff;
            padding: 25px 30px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            max-width: 420px;
            width: 100%;
        }
        h1 {
            margin-top: 0;
            font-size: 22px;
            text-align: center;
        }
        label {
            display: block;
            margin-top: 15px;
            font-weight: bold;
        }
        input[type="text"],
        input[type="password"] {
            width: 100%;
            box-sizing: border-box;
            padding: 8px 10px;
            margin-top: 5px;
            border-radius: 4px;
            border: 1px solid #ccc;
        }
        button {
            margin-top: 20px;
            width: 100%;
            padding: 10px;
            border: none;
            border-radius: 4px;
            background-color: #007bff;
            color: #ffffff;
            font-size: 15px;
            cursor: pointer;
        }
        button:hover {
            background-color: #0056b3;
        }
        .msg {
            margin-top: 15px;
            padding: 10px;
            border-radius: 4px;
            font-size: 14px;
        }
        .msg.error {
            background-color: #ffe5e5;
            color: #a80000;
        }
        .msg.info {
            background-color: #e5ffe8;
            color: #006b1f;
        }
        .hint {
            font-size: 13px;
            color: #666;
        }
        code { background: #f1f1f1; padding: 2px 4px; border-radius: 3px; }
    </style>
</head>
<body>
<br clear=all>

<div class="box">
    <h1>Instalacja – utwórz konto administratora</h1>

    <?php if (!empty($error)): ?>
        <div class="msg error"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if (!empty($info)): ?>
        <div class="msg info"><?php echo $info; ?></div>
    <?php endif; ?>

    <?php if (!$showForm && $existingAdmins > 0 && empty($info)): ?>
        <div class="msg info">
            W bazie danych istnieje już co najmniej jedno konto z rolą <code>admin</code>.<br><br>
            Ten instalator nie jest już potrzebny.
            <br><br>
            <strong>Zalecenie bezpieczeństwa:</strong><br>
            Usuń katalog <code>install</code> lub przynajmniej plik 
            <code>create_admin.php</code> z serwera.
        </div>
    <?php endif; ?>

    <?php if ($showForm): ?>
        <form method="post" action="">
            <label for="username">Nazwa użytkownika (login admina):</label>
            <input type="text" name="username" id="username" required>

            <label for="password">Hasło:</label>
            <input type="password" name="password" id="password" required>
            <div class="hint">Min. 8 znaków. Użyj małych/DUŻYCH liter, cyfr i znaków specjalnych.</div>

            <label for="password2">Powtórz hasło:</label>
            <input type="password" name="password2" id="password2" required>

            <button type="submit">Utwórz konto administratora</button>
        </form>
    <?php endif; ?>
</div>

</body>
</html>