<?php
//if (!defined('APP_INIT')) die('403');
//
//$env_file = __DIR__ . '/../.env';
//
//echo "Szukam pliku .env w: " . $env_file . "<br>";
//echo "Plik istnieje? " . (file_exists($env_file) ? 'TAK' : 'NIE') . "<br><br>";
//
//if (file_exists($env_file)) {
//    $env = parse_ini_file($env_file);
//    echo "<pre>";
//   print_r($env);
//    echo "</pre>";
//}

//exit; // ZATRZYMAJ tutaj żeby zobaczyć wyniki


/**
 * Konfiguracja połączenia z bazą danych
 * Wymaga pliku .env w katalogu nadrzędnym
 */

// Zabezpieczenie przed bezpośrednim dostępem
if (!defined('APP_INIT')) {
    http_response_code(403);
    exit('Dostęp zabroniony');
}

// Ścieżka do pliku .env
$env_file = dirname(__DIR__) . '/.env';

// Sprawdź czy plik .env istnieje
if (!file_exists($env_file)) {
    die("BŁĄD: Plik .env nie został znaleziony w lokalizacji: {$env_file}");
}

// Wczytaj zmienne środowiskowe
$env = parse_ini_file($env_file);

if ($env === false) {
    die("BŁĄD: Nie można wczytać pliku .env. Sprawdź jego format.");
}

// Sprawdź czy wszystkie wymagane klucze istnieją
$required_keys = ['DB_HOST', 'DB_USER', 'DB_PASS', 'DB_NAME'];
foreach ($required_keys as $key) {
    if (!isset($env[$key])) {
        die("BŁĄD: Brak klucza {$key} w pliku .env");
    }
}

// Przypisz wartości do zmiennych
$db_host = $env['DB_HOST'];
$db_name = $env['DB_NAME'];
$db_user = $env['DB_USER'];
$db_pass = $env['DB_PASS'];

// Połączenie z bazą danych
try {
    $dsn = "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4";
    
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ]);
    
    // DEBUG - usuń po przetestowaniu!
    // echo "Połączono z bazą danych pomyślnie!";
    
} catch (PDOException $e) {
    // Loguj błąd (nie pokazuj użytkownikowi!)
    error_log("Database connection error: " . $e->getMessage());
    
    // W trybie produkcyjnym pokaż ogólny komunikat
    if (ini_get('display_errors')) {
        die("Błąd połączenia z bazą danych: " . $e->getMessage());
    } else {
        die("Błąd połączenia z bazą danych. Skontaktuj się z administratorem.");
    }
}