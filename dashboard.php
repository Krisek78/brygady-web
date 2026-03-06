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
  <style>
    :root {
      --bg-dark: #0f172a;
      --bg-panel: #1e293b;
      --bg-header: #0f172a;
      --bg-card: #1e293b;
      --bg-input: #334155;
      --text-main: #94a3b8;
      --text-bright: #f1f5f9;
      --accent: #3b82f6;
      --accent-hover: #2563eb;
      --border: #334155;
      --danger: #ef4444;
      --success: #10b981;
    }
    
    * { box-sizing: border-box; outline: none; }
    
    body {
      margin: 0; padding: 0;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background-color: var(--bg-dark);
      color: var(--text-main);
      height: 100vh;
      display: flex;
      flex-direction: column;
      overflow: hidden;
    }

    /* --- TOPBAR --- */
    .topbar {
      height: 50px;
      background-color: var(--bg-header);
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 20px;
      border-bottom: 1px solid var(--border);
      flex-shrink: 0;
    }
    
    .title-group { display: flex; align-items: center; gap: 15px; }
    .appname { font-weight: bold; color: var(--text-bright); font-size: 16px; letter-spacing: 0.5px; }
    .ver { font-size: 11px; color: #64748b; background: #334155; padding: 2px 6px; border-radius: 3px; }
    
    .actions { display: flex; gap: 8px; align-items: center; }
    
    .tabs { display: flex; background: var(--bg-input); border-radius: 6px; padding: 2px; }
    .tabBtn {
      background: transparent; border: none; color: #64748b;
      padding: 6px 12px; cursor: pointer; font-size: 13px; font-weight: 500;
      transition: 0.2s; border-radius: 4px;
    }
    .tabBtn.active { background: var(--accent); color: white; }
    .tabBtn:hover:not(.active) { color: var(--text-bright); }

    button { cursor: pointer; font-family: inherit; border: none; }
    
    .primary { 
      background: var(--accent); color: white; 
      padding: 6px 16px; border-radius: 6px; 
      font-weight: 600; font-size: 13px;
      transition: background 0.2s;
    }
    .primary:hover { background: var(--accent-hover); }
    
    .ghost { 
      background: transparent; border: 1px solid var(--border); 
      color: var(--text-main); padding: 5px 10px; 
      border-radius: 6px; font-size: 12px;
      transition: all 0.2s;
    }
    .ghost:hover { border-color: #475569; color: var(--text-bright); background: var(--bg-input); }
    
    .iconBtn { 
      background: none; border: none; color: var(--text-main); 
      font-size: 14px; cursor: pointer; padding: 4px; 
      border-radius: 4px; transition: 0.2s;
    }
    .iconBtn:hover { background: var(--bg-input); color: var(--text-bright); }

    /* --- MAIN LAYOUT --- */
    .layout {
      display: grid;
      grid-template-columns: 280px 1fr;
      grid-template-rows: 1fr 200px;
      gap: 1px;
      background: var(--border);
      flex: 1;
      overflow: hidden;
    }

    /* --- PANELS --- */
    .panel {
      background-color: var(--bg-panel);
      display: flex;
      flex-direction: column;
      overflow: hidden;
    }
    
    .panel.left {
      grid-row: 1 / 2;
      grid-column: 1 / 2;
      border-right: 1px solid var(--border);
    }
    
    .panel.bottom {
      grid-row: 2 / 3;
      grid-column: 1 / 3;
      border-top: 1px solid var(--border);
    }

    .panelHeader {
      padding: 15px;
      border-bottom: 1px solid var(--border);
      background: var(--bg-panel);
    }
    
    .panelTitle { 
      font-weight: 700; color: var(--text-bright); 
      margin-bottom: 10px; font-size: 13px; 
      text-transform: uppercase; letter-spacing: 0.5px;
    }
    
    .row { display: flex; gap: 8px; }
    
    .search {
      flex: 1; background: var(--bg-input); border: 1px solid var(--bg-input);
      color: var(--text-bright); padding: 8px 12px; 
      border-radius: 6px; font-size: 13px;
      transition: border-color 0.2s;
    }
    .search:focus { border-color: var(--accent); }

    .pool {
      flex: 1; overflow-y: auto; padding: 15px;
      display: flex; flex-direction: column; gap: 8px;
    }
    
    .pool-item {
      background: var(--bg-input); padding: 10px 12px; 
      border-radius: 6px; border: 1px solid transparent;
      cursor: grab; font-size: 13px; color: var(--text-bright);
      display: flex; justify-content: space-between; align-items: center;
      transition: all 0.2s;
    }
    .pool-item:hover { border-color: var(--accent); background: #475569; }
    .pool-item-actions { display: flex; gap: 4px; opacity: 0; transition: opacity 0.2s; }
    .pool-item:hover .pool-item-actions { opacity: 1; }

    /* --- CENTER SECTION --- */
    .center {
      grid-row: 1 / 2;
      grid-column: 2 / 3;
      background: var(--bg-dark);
      display: flex;
      flex-direction: column;
      overflow: hidden;
    }

    .toolbar {
      height: 60px;
      background: var(--bg-panel);
      border-bottom: 1px solid var(--border);
      display: flex;
      align-items: center;
      padding: 0 20px;
      gap: 15px;
      flex-shrink: 0;
    }

    .field-group {
      display: flex;
      align-items: center;
      gap: 8px;
      background: var(--bg-input);
      padding: 6px 12px;
      border-radius: 6px;
    }
    
    .field-label {
      font-size: 11px;
      color: #64748b;
      text-transform: uppercase;
      font-weight: 600;
      letter-spacing: 0.5px;
    }
    
    select {
      background: transparent; color: var(--text-bright); 
      border: none; padding: 4px 8px; 
      font-size: 13px; font-weight: 500;
      cursor: pointer; min-width: 150px;
    }
    select option { background: var(--bg-input); color: var(--text-bright); }

    .toolbar-spacer { flex: 1; }

    .zoom-controls {
      display: flex;
      align-items: center;
      gap: 8px;
      background: var(--bg-input);
      padding: 6px 10px;
      border-radius: 6px;
    }
    
    .zoom-label {
      font-size: 12px;
      color: var(--text-bright);
      min-width: 40px;
      text-align: center;
      font-weight: 600;
    }

    /* Teams Area */
    .teams-container {
      flex: 1;
      overflow: auto;
      padding: 20px;
      background: var(--bg-dark);
    }
    
    .teams-grid {
      display: flex;
      gap: 20px;
      min-width: max-content;
      padding-bottom: 20px;
    }

    .team-card {
      width: 320px;
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: 8px;
      display: flex;
      flex-direction: column;
      overflow: hidden;
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }

    .team-card-header {
      padding: 12px 15px;
      background: var(--bg-input);
      border-bottom: 1px solid var(--border);
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .team-card-title {
      font-weight: 700;
      color: var(--text-bright);
      font-size: 14px;
    }

    .team-card-body {
      padding: 15px;
      display: flex;
      flex-direction: column;
      gap: 15px;
      min-height: 200px;
    }

    .team-section {
      display: flex;
      flex-direction: column;
      gap: 8px;
    }

    .team-section-label {
      font-size: 11px;
      color: #64748b;
      text-transform: uppercase;
      font-weight: 600;
      letter-spacing: 0.5px;
      margin-bottom: 4px;
    }

    .team-member-item, .team-task-item {
      background: var(--bg-input);
      padding: 8px 12px;
      border-radius: 6px;
      font-size: 13px;
      color: var(--text-bright);
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-left: 3px solid var(--accent);
    }

    .team-task-item {
      border-left-color: var(--success);
    }

    /* Views */
    .view { display: none; width: 100%; height: 100%; overflow: auto; }
    .view.active { display: block; }
    
    .view-content {
      padding: 30px;
      background: var(--bg-panel);
      margin: 20px;
      border-radius: 8px;
      border: 1px solid var(--border);
    }

    .view-title {
      font-size: 20px;
      font-weight: 700;
      color: var(--text-bright);
      margin-bottom: 20px;
      padding-bottom: 10px;
      border-bottom: 2px solid var(--accent);
    }

    /* Modals */
    .modal {
      position: fixed; top: 0; left: 0; width: 100%; height: 100%;
      background: rgba(0,0,0,0.7); z-index: 1000;
      display: flex; justify-content: center; align-items: center;
      backdrop-filter: blur(4px);
    }
    .modal[hidden] { display: none; }
    
    .modal-card {
      background: var(--bg-panel); width: 400px; padding: 25px;
      border-radius: 12px; border: 1px solid var(--border);
      box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
    }
    
    .modal-header {
      display: flex; justify-content: space-between; align-items: center;
      margin-bottom: 20px;
    }
    
    .modal-title {
      font-weight: 700; color: var(--text-bright); font-size: 16px;
    }
    
    .modal input {
      width: 100%; padding: 10px 12px; margin-bottom: 15px;
      background: var(--bg-input); border: 1px solid var(--border);
      color: var(--text-bright); border-radius: 6px; font-size: 14px;
    }
    .modal input:focus { border-color: var(--accent); }
    
    .modal-actions {
      display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;
    }

    /* Toast */
    .toast {
      position: fixed; bottom: 220px; right: 20px;
      background: var(--accent); color: white;
      padding: 12px 20px; border-radius: 8px;
      box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
      font-weight: 600; font-size: 13px;
      animation: slideIn 0.3s ease-out;
      z-index: 2000;
    }
    @keyframes slideIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }

    /* Scrollbar styling */
    ::-webkit-scrollbar { width: 8px; height: 8px; }
    ::-webkit-scrollbar-track { background: var(--bg-dark); }
    ::-webkit-scrollbar-thumb { background: var(--bg-input); border-radius: 4px; }
    ::-webkit-scrollbar-thumb:hover { background: #475569; }
  </style>
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
      <button class="primary">DRUKUJ (PDF) – wszystkie budowy</button>
      <button onclick="location.href='logout.php'" class="ghost" style="border-color:#ef4444; color:#ef4444;">Wyloguj</button>
    </div>
  </header>

  <!-- MAIN LAYOUT -->
  <main class="layout">
    
    <!-- LEFT PANEL: PEOPLE POOL -->
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

    <!-- CENTER SECTION -->
    <section class="center">
      
      <!-- TOOLBAR -->
      <div class="toolbar">
        <div class="field-group">
          <span class="field-label">BUDOWA</span>
          <select id="buildingSelect">
            <option value="">-- Wybierz budowę --</option>
          </select>
          <button id="btnEditBuilding" class="iconBtn" title="Edytuj">✎</button>
          <button id="btnDeleteBuilding" class="iconBtn" title="Usuń" style="color:#ef4444;">🗑</button>
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

      <!-- VIEW: PLAN -->
      <div id="viewPlan" class="view active">
        <div class="teams-container">
          <div id="teamsArea" class="teams-grid">
            <div style="color:#64748b; width:100%; text-align:center; margin-top:100px;">
              <p style="font-size: 16px; margin-bottom: 10px;">Wybierz budowę z listy powyżej</p>
              <p style="font-size: 13px;">lub dodaj nową, aby rozpocząć planowanie</p>
            </div>
          </div>
        </div>
      </div>

      <!-- VIEW: PEOPLE LIST -->
      <div id="viewPeople" class="view">
        <div class="view-content">
          <div class="view-title">Ludzie – pełna lista i przypisania</div>
          <div id="peopleViewList" class="list"></div>
        </div>
      </div>

      <!-- VIEW: TASKS LIST -->
      <div id="viewTasks" class="view">
        <div class="view-content">
          <div class="view-title">Zadania – przypisane do zespołów</div>
          <div id="tasksViewList" class="list"></div>
        </div>
      </div>
    </section>

    <!-- BOTTOM PANEL: TASKS POOL -->
    <aside class="panel bottom">
      <div class="panelHeader">
        <div class="panelTitle">ZADANIA (pula)</div>
        <div class="row">
          <input id="taskInput" class="search" placeholder="Wpisz zadanie i Enter..." />
          <button id="btnAddTask" class="primary" onclick="addTaskFromInput()" style="padding: 6px 16px; font-size: 12px;">Dodaj</button>
        </div>
      </div>
      <div id="tasksPool" class="pool">
        <div style="color:#64748b; text-align:center; margin-top:30px;">Ładowanie...</div>
      </div>
    </aside>
  </main>

  <!-- MODALS -->
  <div id="workerModal" class="modal" hidden>
    <div class="modal-card">
      <div class="modal-header">
        <div class="modal-title">Dodaj pracownika</div>
        <button class="iconBtn" onclick="closeModal('workerModal')">✕</button>
      </div>
      <label style="display:block; margin-bottom:8px; font-size:13px; color:#94a3b8;">Imię i nazwisko</label>
      <input id="wmName" placeholder="np. Jan Kowalski" />
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
      <label id="textModalLabel" style="display:block; margin-bottom:8px; font-size:13px; color:#94a3b8;">Wartość</label>
      <input id="textModalInput" />
      <div class="modal-actions">
        <button class="ghost" onclick="closeModal('textModal')">Anuluj</button>
        <button class="primary" id="textModalOk">OK</button>
      </div>
    </div>
  </div>

  <div id="toast" class="toast" hidden></div>

  <!-- JAVASCRIPT CODE (ten sam co wcześniej, tylko dostosowany do nowego HTML) -->
<script>
    let allWorkers = [];
    let allTasks = [];
    let allProjects = [];
    let currentProjectId = null;
    
    // Zmienne do Drag & Drop
    let draggedItem = null;
    let draggedType = null; // 'worker' lub 'task'

    document.addEventListener('DOMContentLoaded', () => {
        loadData();
        setupEventListeners();
    });

    async function loadData() {
        try {
            const [workersRes, tasksRes, projectsRes] = await Promise.all([
                fetch('api/workers.php'),
                fetch('api/tasks.php'),
                fetch('api/projects.php')
            ]);

            allWorkers = await workersRes.json();
            allTasks = await tasksRes.json();
            allProjects = await projectsRes.json();

            renderWorkersPool();
            renderTasksPool();
            renderProjectSelect();
            
            if (currentProjectId) selectProject(currentProjectId);
            else if (allProjects.length === 1) selectProject(allProjects[0].id);
        } catch (error) {
            console.error("Błąd ładowania:", error);
            showToast("Błąd łączenia z bazą danych!");
        }
    }

    // --- RENDEROWANIE Z OBSŁUGĄ DRAG START ---
    function renderWorkersPool() {
        const container = document.getElementById('workersPool');
        container.innerHTML = '';
        if (allWorkers.length === 0) {
            container.innerHTML = '<div style="color:#64748b; text-align:center; margin-top:30px;">Brak pracowników.</div>';
            return;
        }
        allWorkers.forEach(w => {
            const el = createDraggableElement(w.full_name, 'worker', w.id);
            container.appendChild(el);
        });
    }

    function renderTasksPool() {
        const container = document.getElementById('tasksPool');
        container.innerHTML = '';
        const unassigned = allTasks.filter(t => !t.assigned_to_team_id);
        
        if (unassigned.length === 0) {
            container.innerHTML = '<div style="color:#64748b; text-align:center; margin-top:30px;">Brak zadań w puli.</div>';
            return;
        }
        unassigned.forEach(t => {
            const el = createDraggableElement(t.title, 'task', t.id);
            container.appendChild(el);
        });
    }

    // Helper do tworzenia elementów do przeciągania
    function createDraggableElement(text, type, id) {
        const el = document.createElement('div');
        el.className = 'pool-item';
        el.draggable = true;
        el.textContent = text;
        el.dataset.id = id;
        el.dataset.type = type;

        // ZDARZENIE: Rozpoczęcie przeciągania
        el.addEventListener('dragstart', (e) => {
            draggedItem = { id: id, type: type };
            e.dataTransfer.setData('text/plain', JSON.stringify(draggedItem));
            e.dataTransfer.effectAllowed = 'copy';
            setTimeout(() => el.style.opacity = '0.5', 0);
        });

        el.addEventListener('dragend', () => {
            el.style.opacity = '1';
            draggedItem = null;
        });

        return el;
    }

    function renderProjectSelect() {
        const sel = document.getElementById('buildingSelect');
        const currentVal = sel.value; 
        sel.innerHTML = '<option value="">-- Wybierz budowę --</option>';
        allProjects.forEach(p => {
            const opt = document.createElement('option');
            opt.value = p.id;
            opt.textContent = p.name;
            sel.appendChild(opt);
        });
        if (currentVal && allProjects.find(p => p.id == currentVal)) sel.value = currentVal;
    }

    // --- RENDEROWANIE ZESPOŁÓW Z OBSŁUGĄ DROP ---
    async function selectProject(projectId) {
        currentProjectId = projectId;
        const area = document.getElementById('teamsArea');
        
        if (!projectId) {
            area.innerHTML = '<div style="color:#64748b; width:100%; text-align:center; margin-top:100px;"><p>Wybierz budowę...</p></div>';
            return;
        }

        area.innerHTML = '<div style="color:#64748b; text-align:center; margin-top:50px;">Ładowanie...</div>';

        try {
            const res = await fetch(`api/teams.php?project_id=${projectId}`);
            const teams = await res.json();
            area.innerHTML = '';

            if (teams.length === 0) {
                area.innerHTML = `<div style="text-align:center; margin-top:50px;"><button class="primary" onclick="addTeam()">+ Dodaj zespół</button></div>`;
                return;
            }

            teams.forEach(team => {
                const card = document.createElement('div');
                card.className = 'team-card';
                card.dataset.teamId = team.id;

                // Sekcja Ludzie
                let membersHtml = '';
                if (team.members && team.members.length > 0) {
                    membersHtml = team.members.map(m => createTeamItemHtml(m.full_name, 'worker', m.id, team.id)).join('');
                } else {
                    membersHtml = '<div style="color:#64748b; font-size:12px; padding:8px;">Brak ludzi</div>';
                }

                // Sekcja Zadania
                let tasksHtml = '';
                if (team.tasks && team.tasks.length > 0) {
                    tasksHtml = team.tasks.map(t => createTeamItemHtml(t.title, 'task', t.id, team.id, true)).join('');
                }

                card.innerHTML = `
                  <div class="team-card-header">
                    <span class="team-card-title">${escapeHtml(team.team_name)}</span>
                    <button class="iconBtn" style="color:#ef4444;" onclick="deleteTeam(${team.id})">✕</button>
                  </div>
                  <div class="team-card-body">
                    <div class="team-section">
                        <div class="team-section-label">LUDZIE (przeciągnij tutaj)</div>
                        <div class="drop-zone" data-type="worker" data-team-id="${team.id}">
                            ${membersHtml}
                        </div>
                    </div>
                    <div class="team-section">
                        <div class="team-section-label">ZADANIA (przeciągnij tutaj)</div>
                        <div class="drop-zone" data-type="task" data-team-id="${team.id}">
                            ${tasksHtml}
                        </div>
                    </div>
                  </div>
                `;
                
                // Podpięcie zdarzeń Drop na strefy
                const dropZones = card.querySelectorAll('.drop-zone');
                dropZones.forEach(zone => {
                    zone.addEventListener('dragover', handleDragOver);
                    zone.addEventListener('dragleave', handleDragLeave);
                    zone.addEventListener('drop', handleDrop);
                });

                area.appendChild(card);
            });
        } catch (e) {
            console.error(e);
            area.innerHTML = '<div style="color:red">Błąd</div>';
        }
    }

    // Helper HTML dla elementu w zespole
    function createTeamItemHtml(text, type, id, teamId, isTask = false) {
        const borderClass = isTask ? 'team-task-item' : 'team-member-item';
        const actionLabel = isTask ? '↻' : '↻'; // Ikona cofnięcia
        return `
            <div class="${borderClass}">
                <span>${escapeHtml(text)}</span>
                <button class="iconBtn" style="font-size:11px;" 
                    onclick="removeFromTeam('${type}', ${id}, ${teamId})" 
                    title="Cofnij do puli">${actionLabel}</button>
            </div>
        `;
    }

    // --- LOGIKA DRAG & DROP ---

    function handleDragOver(e) {
        e.preventDefault(); // Konieczne, aby pozwolić na drop
        e.currentTarget.style.backgroundColor = '#334155'; // Podświetlenie
        e.dataTransfer.dropEffect = 'copy';
    }

    function handleDragLeave(e) {
        e.currentTarget.style.backgroundColor = 'transparent';
    }

    async function handleDrop(e) {
        e.preventDefault();
        e.currentTarget.style.backgroundColor = 'transparent';
        
        const zone = e.currentTarget;
        const targetType = zone.dataset.type; // 'worker' lub 'task'
        const targetTeamId = zone.dataset.teamId;

        if (!draggedItem) return;

        // Walidacja typu (nie wrzucaj zadania do ludzi i odwrotnie)
        if (draggedItem.type !== targetType) {
            showToast("Nie można tu upuścić tego elementu!");
            return;
        }

        try {
            let success = false;

            if (draggedItem.type === 'worker') {
                // Dodaj pracownika do zespołu
                const res = await fetch('api/team-members.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ team_id: targetTeamId, employee_id: draggedItem.id })
                });
                const result = await res.json();
                if(result.success) success = true;
                else if(result.message) showToast(result.message);

            } else if (draggedItem.type === 'task') {
                // Przypisz zadanie do zespołu
                const res = await fetch('api/tasks.php', {
                    method: 'PUT',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ id: draggedItem.id, assigned_to_team_id: targetTeamId })
                });
                const result = await res.json();
                if(result.success) success = true;
            }

            if (success) {
                showToast('Przypisano!');
                selectProject(currentProjectId); // Odśwież widok
            }

        } catch (err) {
            console.error(err);
            showToast('Błąd podczas przypisywania');
        }
    }

    // --- FUNKCJE COFANIA (USUWANIA Z ZESPOŁU) ---
    
    async function removeFromTeam(type, itemId, teamId) {
        if(!confirm("Cofnąć ten element do puli?")) return;

        try {
            let success = false;
            if (type === 'worker') {
                const res = await fetch('api/team-members.php', {
                    method: 'DELETE',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ team_id: teamId, employee_id: itemId })
                });
                const r = await res.json();
                if(r.success) success = true;
            } else if (type === 'task') {
                const res = await fetch('api/tasks.php', {
                    method: 'PUT',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ id: itemId, assigned_to_team_id: null })
                });
                const r = await res.json();
                if(r.success) success = true;
            }

            if(success) {
                showToast('Cofnięto do puli');
                selectProject(currentProjectId);
            }
        } catch(e) { alert('Błąd'); }
    }

    // --- POZOSTAŁE FUNKCJE CRUD (bez zmian) ---
    async function saveWorker() { /* ... (takie same jak wcześniej) ... */ 
        const nameInput = document.getElementById('wmName');
        const name = nameInput.value.trim();
        if (!name) return alert('Podaj imię');
        const res = await fetch('api/workers.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({full_name:name}) });
        const r = await res.json();
        if(r.success) { closeModal('workerModal'); nameInput.value=''; loadData(); showToast('Dodano'); }
        else alert(r.message);
    }

    async function addTaskFromInput() { /* ... */ 
        const input = document.getElementById('taskInput');
        const title = input.value.trim();
        if(!title || !currentProjectId) return alert('Wybierz budowę i wpisz zadanie');
        const res = await fetch('api/tasks.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({title:title, project_id:currentProjectId}) });
        const r = await res.json();
        if(r.success) { input.value=''; loadData(); showToast('Dodano zadanie'); }
    }

    async function addTeam() { /* ... */ 
        if(!currentProjectId) return alert('Wybierz budowę');
        const name = prompt("Nazwa zespołu:");
        if(!name) return;
        const res = await fetch('api/teams.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({project_id:currentProjectId, team_name:name}) });
        const r = await res.json();
        if(r.success) { selectProject(currentProjectId); showToast('Dodano zespół'); }
    }

    async function deleteTeam(id) { if(confirm('Usunąć zespół?')) { await fetch('api/teams.php',{method:'DELETE',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:id})}); selectProject(currentProjectId); } }
    
    async function addProject(name) { /* ... */ 
        const res = await fetch('api/projects.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({name:name, status:'active'}) });
        const r = await res.json();
        if(r.success) { loadData(); setTimeout(()=>{ if(allProjects.length){ document.getElementById('buildingSelect').value=allProjects[allProjects.length-1].id; selectProject(allProjects[allProjects.length-1].id); } }, 100); showToast('Dodano budowę'); }
    }
    
    async function deleteCurrentProject() { /* ... */ 
        if(!currentProjectId) return;
        if(!confirm('Usunąć budowę?')) return;
        await fetch('api/projects.php', { method:'DELETE', headers:{'Content-Type':'application/json'}, body:JSON.stringify({id:currentProjectId}) });
        currentProjectId=null; document.getElementById('buildingSelect').value=""; loadData();
    }

    // UI Helpers
    window.switchView = (v) => { document.querySelectorAll('.view').forEach(x=>x.classList.remove('active')); document.getElementById('view'+v.charAt(0).toUpperCase()+v.slice(1)).classList.add('active'); };
    window.openModal = (id) => document.getElementById(id).hidden=false;
    window.closeModal = (id) => document.getElementById(id).hidden=false; // Poprawka: powinno być true, ale sprawdzono wyżej
    
    // Fix for closeModal logic in previous block
    window.closeModal = function(id) { document.getElementById(id).hidden = true; };

    let textModalCallback = null;
    window.openTextModal = (title, label, cb) => {
        document.getElementById('textModalTitle').textContent = title;
        document.getElementById('textModalLabel').textContent = label;
        document.getElementById('textModalInput').value = '';
        textModalCallback = cb;
        document.getElementById('textModalOk').onclick = () => { if(textModalCallback) textModalCallback(document.getElementById('textModalInput').value.trim()); closeModal('textModal'); };
        openModal('textModal');
    };

    window.showToast = (msg) => { const t=document.getElementById('toast'); t.textContent=msg; t.hidden=false; setTimeout(()=>t.hidden=true, 3000); };
    
    function escapeHtml(t){ if(!t)return t; return t.replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;").replace(/'/g,"&#039;"); }

    function setupEventListeners() {
        document.getElementById('buildingSelect').onchange = (e) => selectProject(e.target.value);
        document.getElementById('btnAddTeam').onclick = addTeam;
        document.getElementById('btnAddBuilding').onclick = () => openTextModal('Dodaj budowę', 'Nazwa', addProject);
        document.getElementById('btnDeleteBuilding').onclick = deleteCurrentProject;
        document.getElementById('wmName').onkeypress = (e) => { if(e.key==='Enter') saveWorker(); };
        document.getElementById('taskInput').onkeypress = (e) => { if(e.key==='Enter') addTaskFromInput(); };
    }
</script>
</body>
</html>