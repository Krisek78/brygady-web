<?php
session_start();
if (!defined('APP_INIT')) define('APP_INIT', true);
require_once __DIR__ . '/config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index_login.php");
    exit;
}
$userName = htmlspecialchars($_SESSION['username']);
$userRole = $_SESSION['role'];
?>
<!doctype html>
<html lang="pl">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Plan Brygad - Web</title>
  <!-- DOŁĄCZENIE STYLI -->
  <link rel="stylesheet" href="css/style.css" />
</head>
<body>

  <!-- HEADER -->
  <header class="topbar">
    <div class="title-group">
      <div class="appname">PLAN BRYGAD</div>
      <div class="ver">v0.5.0</div>
    </div>
    <div class="actions">
      <div class="tabs">
        <button class="tabBtn active" onclick="switchView('plan')">Plan</button>
        <button class="tabBtn" onclick="switchView('people')">Ludzie</button>
        <button class="tabBtn" onclick="switchView('tasks')">Zadania</button>
      </div>
      <button class="ghost" title="Sprawdź obsadę"><span style="font-size: 16px;">✓</span></button>
      <button class="primary">DRUKUJ (PDF)</button>
      <button onclick="location.href='logout.php'" class="ghost" style="border-color:#ef4444; color:#ef4444;">Wyloguj</button>
    </div>
  </header>

  <main class="layout">
    <aside class="panel left">
      <div class="panelHeader">
        <div class="panelTitle">LUDZIE (pula)</div>
        <div class="row">
          <input id="workerSearch" class="search" placeholder="Szukaj..." />
          <button id="btnAddWorker" class="primary" onclick="openModal('workerModal')" style="padding: 6px 12px; font-size: 12px;">+ Dodaj</button>
        </div>
      </div>
      <div id="workersPool" class="pool">
        <div style="color:#64748b; text-align:center; margin-top:30px;">Ładowanie...</div>
      </div>
    </aside>

    <section class="center">
      <div class="toolbar">
        <div class="field-group">
          <span class="field-label">BUDOWA</span>
          <select id="buildingSelect">
            <option value="">-- Wybierz budowę --</option>
          </select>
          <button id="btnResetProject" class="ghost" style="border-color:#f59e0b; color:#f59e0b;" title="Wyczyść przypisania">
            🗑 Wyczyść przypisania
          </button>
          <button id="btnEditBuilding" class="iconBtn" title="Edytuj nazwę">✎</button>
          <button id="btnDeleteBuilding" class="iconBtn" title="Usuń budowę" style="color:#ef4444;">🗑</button>
        </div>
        <div class="toolbar-spacer"></div>
        <button class="ghost">Ludzie ◀▶</button>
        <div class="zoom-controls">
          <button class="iconBtn">−</button>
          <span class="zoom-label">100%</span>
          <button class="iconBtn">+</button>
        </div>
        <button id="btnAddTeam" class="ghost">+ Dodaj zespół</button>
        <button id="btnAddBuilding" class="ghost">+ Dodaj budowę</button>
      </div>

      <div id="viewPlan" class="view active">
        <div class="teams-container">
          <div id="teamsArea" class="teams-grid">
            <div style="color:#64748b; width:100%; text-align:center; margin-top:100px;">Wybierz budowę z listy...</div>
          </div>
        </div>
      </div>

      <div id="viewPeople" class="view">
        <div class="view-content">
          <div class="view-title">Ludzie – przypisania globalne</div>
          <div id="peopleViewList" class="list"></div>
        </div>
      </div>

      <div id="viewTasks" class="view">
        <div class="view-content">
          <div class="view-title">Zadania – wszystkie</div>
          <div id="tasksViewList" class="list"></div>
        </div>
      </div>
    </section>

    <aside class="panel bottom">
      <div class="panelHeader">
        <div class="panelTitle">ZADANIA (pula)</div>
        <div class="row">
          <input id="taskInput" class="search" placeholder="Wpisz zadanie i Enter..." />
          <button id="btnAddTask" class="primary" onclick="addTaskFromInput()" style="padding: 6px 16px; font-size: 12px;">Dodaj</button>
        </div>
      </div>
      <div id="tasksPool" class="pool"></div>
    </aside>
  </main>

  <!-- MODALE -->
  <div id="workerModal" class="modal" hidden>
    <div class="modal-card">
      <div class="modal-header">
        <div class="modal-title">Dodaj pracownika</div>
        <button class="iconBtn" onclick="closeModal('workerModal')">✕</button>
      </div>
      <input id="wmName" placeholder="Imię i nazwisko" />
      <div class="modal-actions">
        <button class="ghost" onclick="closeModal('workerModal')">Anuluj</button>
        <button class="primary" onclick="saveWorker()">Zapisz</button>
      </div>
    </div>
  </div>

  <div id="textModal" class="modal" hidden>
    <div class="modal-card">
      <div class="modal-header">
        <div class="modal-title" id="textModalTitle">Wpisz</div>
        <button class="iconBtn" onclick="closeModal('textModal')">✕</button>
      </div>
      <input id="textModalInput" />
      <div class="modal-actions">
        <button class="ghost" onclick="closeModal('textModal')">Anuluj</button>
        <button class="primary" id="textModalOk">OK</button>
      </div>
    </div>
  </div>

  <div id="toast" class="toast" hidden></div>

  <!-- DOŁĄCZENIE LOGIKI JAVASCRIPT -->
  <script src="js/dashboard.js"></script>
</body>
</html>