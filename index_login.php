<?php
session_start();
// Jeśli użytkownik jest już zalogowany, przekieruj do dashboardu
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logowanie - KBR Invest</title>
    <style>
        :root {
            --bg-dark: #0f172a;
            --bg-panel: #1e293b;
            --accent: #3b82f6;
            --text-bright: #f1f5f9;
            --text-muted: #94a3b8;
            --header-height: 80px;
            --footer-height: 60px;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--bg-dark);
            color: var(--text-bright);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* --- NAGŁÓWEK --- */
        .top-nav {
            height: var(--header-height);
            width: 100%;
            background-color: #ffffff; /* Białe tło jak na kbrinvest.pl */
            display: flex;
            align-items: center;
            padding: 0 5%;
            box-shadow: 0 2px 10px rgba(0,0,0,0.3);
            position: fixed;
            top: 0;
            z-index: 1000;
        }

        .logo {
            height: 50px; /* Dostosuj do swojego logo */
            display: block;
        }

        .logo img {
            height: 100%;
            width: auto;
        }

        /* --- KONTENER GŁÓWNY (WYŚRODKOWANIE) --- */
        .main-content {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding-top: var(--header-height);
            padding-bottom: var(--footer-height);
        }

        .login-container {
            background: var(--bg-panel);
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            width: 100%;
            max-width: 400px;
            border: 1px solid #334155;
            text-align: center;
        }

        .login-container h2 {
            margin-top: 0;
            margin-bottom: 30px;
            font-size: 24px;
            font-weight: 600;
        }

        .login-container input {
            width: 100%;
            padding: 14px;
            margin-bottom: 15px;
            background: #334155;
            border: 1px solid #475569;
            color: white;
            border-radius: 8px;
            font-size: 16px;
        }

        .login-container input:focus {
            outline: none;
            border-color: var(--accent);
        }

        .login-container button {
            width: 100%;
            padding: 14px;
            background-color: var(--accent);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }

        .login-container button:hover {
            background-color: #2563eb;
        }

        .error-msg {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        /* --- STOPKA --- */
        .footer {
            height: var(--footer-height);
            width: 100%;
            background-color: #0a0f1d;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            color: var(--text-muted);
            border-top: 1px solid #1e293b;
        }

        /* Responsywność dla małych ekranów */
        @media (max-width: 480px) {
            .login-container {
                margin: 20px;
                padding: 25px;
            }
        }
    </style>
</head>
<body>

    <!-- NAGŁÓWEK -->
    <header class="top-nav">
        <a href="https://kbrinvest.pl/" class="logo">
            <!-- Podmień assets/logo.png na faktyczną ścieżkę do logotypu -->
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
        &copy; <?php echo date("Y"); ?> KBR Invest sp. z o.o. | Wszelkie prawa zastrzeżone.
    </footer>

</body>
</html>