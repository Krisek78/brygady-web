<?php
session_start();
if (!defined('APP_INIT')) define('APP_INIT', true);
require_once __DIR__ . '/config/db.php';
if (!isset($_SESSION['user_id'])) { header("Location: index_login.php"); exit; }
?>
<!doctype html>
<html lang="pl">
<head>
  <meta charset="utf-8" />
  <title>Plan Brygad</title>
  <link rel="stylesheet" href="css/dashboard.css" />
</head>
<body>
  <header class="topbar">
    <div class="title-group"><div class="appname">PLAN BRYGAD</div><div class="ver">v0.5.0</div></div>
    <div class="actions">
      <div class="tabs">
        <button class="tabBtn active" data-view="plan">Plan</button>
        <button class="tabBtn" data-view="people">Ludzie</button>
        <button class="tabBtn" data-view="tasks">Zadania</button>
      </div>
      <button class="primary">DRUKUJ (PDF)</button>
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
      <div id="viewPeople" class="view"><div class="view-content"><div class="view-title">Ludzie</div><div id="peopleViewList" class="list"></div></div></div>
      <div id="viewTasks" class="view"><div class="view-content"><div class="view-title">Zadania</div><div id="tasksViewList" class="list"></div></div></div>
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
  <div id="toast" class="toast" hidden></div>
  <script src="js/dashboard.js"></script>
</body>
</html>