<?php
session_start();
if (!defined('APP_INIT')) define('APP_INIT', true);
require_once __DIR__ . '/config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index_login.php");
    exit;
}

$userName = htmlspecialchars($_SESSION['username']);
$userRole = $_SESSION['role']; // <--- TEJ LINII BRAKOWAŁO
?>



<!doctype html>
<html lang="pl">
<head>
  <meta charset="utf-8" />
  <title>Plan Brygad</title>
  <link rel="stylesheet" href="css/dashboard.css?v=<?php echo filemtime('css/dashboard.css'); ?>" />
  <link rel="stylesheet" href="css/dashboard.css" />
</head>
<body>
  <header class="topbar">
    <div class="title-group"><div class="appname">PLAN BRYGAD</div><div class="ver">v0.5.0</div></div>
	<!-- DODAJ TO TUTAJ -->
    <div class="font-controls">
      <button id="btnFontDown" title="Zmniejsz czcionkę">-A</button>
      <button id="btnFontUp" title="Zwiększ czcionkę">+A</button>
    </div>
	
    <div class="actions">
		<div class="tabs">
			<button class="tabBtn active" data-view="plan">Plan</button>
			<button class="tabBtn" data-view="people">Ludzie</button>
			<button class="tabBtn" data-view="tasks">Zadania</button>
				<?php if ($userRole === 'admin'): ?>
					<button class="tabBtn" data-view="users">Użytkownicy</button>
				<?php endif; ?>
		</div>
      <button class="primary">DRUKUJ (PDF)</button>
	  <button id="btnAbout" class="ghost" title="Informacje o programie">ℹ️ O programie</button>
      <button onclick="location.href='logout.php'" class="ghost" style="border-color:#ef4444;color:#ef4444;">Wyloguj</button>
    </div>
  </header>
  <main class="layout">
    <aside class="panel left">
      <div class="panelHeader">
        <div class="panelTitle">LUDZIE (pula)</div>
        <div class="row">
          <input id="workerSearch" class="search" placeholder="Szukaj..." />
          <button id="btnAddWorker" class="primary">+ Dodaj</button>
        </div>
      </div>
      <div id="workersPool" class="pool"></div>
    </aside>
    <section class="center">
      <div class="toolbar">
        <div class="field-group">
          <span class="field-label">BUDOWA</span>
          <select id="buildingSelect"></select>
          <button id="btnEditBuilding" class="iconBtn">✎</button>
          <button id="btnDeleteBuilding" class="iconBtn" style="color:#ef4444;">🗑</button>
        </div>
        <div class="toolbar-spacer"></div>
        <button id="btnResetProject" class="ghost" style="border-color:#f59e0b;color:#f59e0b;">🗑 Wyczyść</button>
        <button id="btnAddTeam" class="ghost">+ Zespół</button>
        <button id="btnAddBuilding" class="ghost">+ Budowę</button>
      </div>
      <div id="viewPlan" class="view active"><div class="teams-container"><div id="teamsArea" class="teams-grid"></div></div></div>
      <div id="viewPeople" class="view"><div class="view-content"><div id="peopleViewList" class="list"></div></div></div>
      <div id="viewTasks" class="view"><div class="view-content"><div id="tasksViewList" class="list"></div></div></div>
	  <!-- ADMIN -->
		<div id="viewUsers" class="view">
			<div class="view-content">
				<div class="view-header" style="display:flex; justify-content:space-between; align-items:center;">
					<div class="view-title">Zarządzanie Użytkownikami Systemu</div>
					<button class="primary" id="btnAddUser">+ Dodaj Użytkownika</button>
				</div>
				<div id="usersViewList"></div>
			</div>
		</div>
	  <!-- END ADMIN -->
    </section>
    <aside class="panel bottom">
      <div class="panelHeader">
        <div class="panelTitle">ZADANIA (pula)</div>
        <div class="row">
          <input id="taskInput" class="search" placeholder="Zadanie..." />
          <button id="btnAddTask" class="primary">Dodaj</button>
        </div>
      </div>
      <div id="tasksPool" class="pool"></div>
    </aside>
  </main>
  <div id="workerModal" class="modal" hidden>
    <div class="modal-card">
      <div class="modal-header"><h3>Pracownik</h3><button class="modalClose" data-modal="workerModal">✕</button></div>
      <input id="wmName" placeholder="Imię i nazwisko" />
      <div class="modal-actions"><button class="modalClose" data-modal="workerModal">Anuluj</button><button id="btnSaveWorker" class="primary">Zapisz</button></div>
    </div>
  </div>
  <div id="textModal" class="modal" hidden>
    <div class="modal-card">
      <div class="modal-header"><h3 id="textModalTitle">Wpisz</h3><button class="modalClose" data-modal="textModal">✕</button></div>
      <label id="textModalLabel"></label><input id="textModalInput" />
      <div class="modal-actions"><button class="modalClose" data-modal="textModal">Anuluj</button><button id="textModalOk" class="primary">OK</button></div>
    </div>
  </div>
<!-- W dashboard.php - User Modal -->
<div id="userModal" class="modal" hidden>
    <div class="modal-card">
        <div class="modal-header"><h3>Nowy Użytkownik</h3></div>
        <input id="uUsername" placeholder="Login / Nazwa użytkownika" />
        <input id="uFullName" placeholder="Imię i Nazwisko" /> <!-- DODANE -->
        <input id="uPassword" type="password" placeholder="Hasło" />
        <select id="uRole">
            <option value="user">Użytkownik</option>
            <option value="admin">Administrator</option>
        </select>
        <div class="modal-actions">
            <button class="modalClose ghost" data-modal="userModal">Anuluj</button>
            <button id="btnSaveUser" class="primary">Zapisz</button>
        </div>
    </div>
</div>
<!-- Modal edycji zadania -->
<div id="editTaskModal" class="modal" hidden>
    <div class="modal-card" style="max-width: 600px;">
        <div class="modal-header">
            <h3>Edycja zadania</h3>
            <button class="modalClose iconBtn" data-modal="editTaskModal">✕</button>
        </div>
        <textarea id="editTaskText" rows="5" style="width:100%; padding:12px; background:var(--bg-input); border:1px solid var(--border); color:white; border-radius:6px; font-size:1.1rem; resize:vertical; font-family:inherit;"></textarea>
        <div class="modal-actions">
            <button class="modalClose ghost" data-modal="editTaskModal">Anuluj</button>
            <button id="btnSaveTaskEdit" class="primary">Zapisz zmiany</button>
        </div>
    </div>
</div>

<!-- Modal O Programie -->
<div id="aboutModal" class="modal" hidden>
    <div class="modal-card" style="max-width: 450px; text-align: center;">
        <div class="modal-header" style="justify-content: center;">
            <h2>Plan Brygad - System Zarządzania</h2>
        </div>
        <div style="padding: 20px 0;">
            <div style="font-size: 3rem; margin-bottom: 10px;">🏗️</div>
            <p style="font-size: 1.3rem; color: var(--text-bright); margin: 5px 0;">
                Wersja: <strong id="aboutVersion">-</strong>
            </p>
            <p style="font-size: 0.9rem; color: var(--text-main); margin: 5px 0;">
                Data kompilacji: <span id="aboutBuild">-</span>
            </p>
            <p style="font-size: 0.8rem; color: var(--text-main); margin-top: 15px;">
                <!-- WAŻNE: id="copyrightYear" musi być obecne -->
                © <span id="copyrightYear">2024</span> KBRInvest sp. z o.o.<br>
                Wszelkie prawa zastrzeżone.<br><br>
                <span style="color: #64748b;">Pomoc techniczna:</span><br>
                <a href="mailto:helpdesk@kbrinvest.pl" style="color: var(--accent); text-decoration: none; font-weight: 600;">helpdesk@kbrinvest.pl</a>
            </p>
        </div>
        <div class="modal-actions" style="justify-content: center;">
            <button class="modalClose primary" data-modal="aboutModal">Zamknij</button>
        </div>
    </div>
</div>

  <div id="toast" class="toast" hidden></div>
  <script src="js/dashboard.js"></script>
</body>
</html>