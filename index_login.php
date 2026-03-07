<?php
session_start();

// Sprawdzenie czy użytkownik już zalogowany
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

// Pobranie wersji z bazy dla porównania
define('APP_INIT', true);
require_once __DIR__ . '/config/db.php';

$currentVersion = '1.0.0';
$buildDate = date("Y-m-d H:i:s");
try {
    $stmt = $pdo->query("SELECT version_number, build_date FROM system_version ORDER BY id DESC LIMIT 1");
    $ver = $stmt->fetch();
    if ($ver) {
        $currentVersion = $ver['version_number'];
        $buildDate = $ver['build_date'];
    }
} catch (Exception $e) {
    // Ignoruj błąd, użyj domyślnych wartości
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logowanie - KBR Invest</title>
    
    <!-- CSS z parametrem wersji (cache busting) -->
    <link rel="stylesheet" href="css/login.css?v=<?php echo $currentVersion; ?>">
    
    <!-- SKRYPT SPRAWDZAJĄCY WERSJĘ (PRZED ZAŁADOWANIEM) -->
    <script>
    (function() {
        // Sprawdź czy przeglądarka ma aktualną wersję
        fetch('api/version.php', { cache: 'no-store' })
            .then(r => r.json())
            .then(data => {
                const serverVersion = data.version_number;
                const localVersion = localStorage.getItem('app_version');
                const serverBuild = data.build_date;
                
                // Jeśli wersja się zmieniła
                if (serverVersion !== localVersion) {
                    // Zapisz nową wersję
                    localStorage.setItem('app_version', serverVersion);
                    localStorage.setItem('build_date', serverBuild);
                    
                    // Przeładuj stronę z nowym parametrem w URL (wymusza nowe pliki)
                    if (!window.location.search.includes('v=' + serverVersion)) {
                        window.location.href = 'index_login.php?v=' + serverVersion + '&ts=' + Date.now();
                    }
                }
            })
            .catch(err => console.log('Version check:', err));
    })();
    </script>
</head>
<body>

    <!-- NAGŁÓWEK -->
    <header class="top-nav">
        <a href="https://kbrinvest.pl/" class="logo">
            <img src="pic/kbr-invest-logo.png" alt="KBR Invest sp. z o.o.">
        </a>
    </header>

    <!-- FORMULARZ LOGOWANIA -->
    <main class="main-content">
        <div class="login-container">
            <h2>Panel Planowania</h2>

            <?php if(isset($_GET['error'])): ?>
                <div class="error-msg">
                    <?php 
                        if($_GET['error'] == 'lockout') echo 'Zbyt wiele prób. IP zablokowane na 15 min.';
                        else if($_GET['error'] == 'empty') echo 'Proszę wypełnić wszystkie pola.';
                        else echo 'Nieprawidłowy login lub hasło.';
                    ?>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST">
                <input type="text" name="username" placeholder="Login" required autofocus>
                <input type="password" name="password" placeholder="Hasło" required>
                <button type="submit">Zaloguj się</button>
            </form>
        </div>
    </main>

    <!-- STOPKA -->
    <footer class="footer">
        <span>&copy; <?php echo date("Y"); ?> KBR Invest sp. z o.o.</span>
        <span class="version-info" id="versionDisplay">Wersja: <?php echo $currentVersion; ?></span>
    </footer>

    <!-- Skrypt wyświetlający wersję z localStorage (jeśli różna od serwera) -->
    <script>
        const localVer = localStorage.getItem('app_version');
        const serverVer = '<?php echo $currentVersion; ?>';
        if (localVer && localVer !== serverVer) {
            document.getElementById('versionDisplay').textContent = 'Wersja: ' + localVer + ' (aktualizacja...)';
        }
    </script>
	
	  <script>
        // Pobierz wersję z localStorage (ustawioną przez skrypt na górze)
        const appVer = localStorage.getItem('app_version') || '<?php echo $currentVersion; ?>';
        
        // Dynamicznie dodaj skrypty z wersją w URL (wymusza pobranie nowych)
        const scripts = [
            'js/dashboard.js?v=' + appVer,
            'js/login.js?v=' + appVer  // jeśli masz osobny plik logowania
        ];
        
        scripts.forEach(src => {
            const s = document.createElement('script');
            s.src = src;
            document.body.appendChild(s);
        });
    </script>
	
	
	

</body>
</html>