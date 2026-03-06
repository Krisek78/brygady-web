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
    /* --- RESET I BAZA --- */
    :root {
      --bg-dark: #1e1e1e;
      --bg-panel: #252526;
      --bg-header: #333333;
      --text-main: #cccccc;
      --text-bright: #ffffff;
      --accent: #007acc;
      --accent-hover: #005f9e;
      --border: #3e3e42;
      --danger: #ce4747;
      --success: #4ec9b0;
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
      height: 60px;
      background-color: var(--bg-header);
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 15px;
      border-bottom: 1px solid var(--border);
      flex-shrink: 0;
    }
    .title { display: flex; flex-direction: column; }
    .appname { font-weight: bold; color: var(--text-bright); font-size: 18px; }
    .ver { font-size: 12px; color: #888; margin-left: 5px; }
    .subtitle { font-size: 11px; color: #aaa; margin-top: 2px; }
    
    .actions { display: flex; gap: 10px; align-items: center; }
    .tabs { display: flex; background: #111; border-radius: 4px; padding: 2px; }
    .tabBtn {
      background: transparent; border: none; color: #888;
      padding: 5px 15px; cursor: pointer; font-size: 13px;
      transition: 0.2s;
    }
    .tabBtn.active { background: var(--bg-panel); color: var(--text-bright); border-radius: 3px; }
    .tabBtn:hover:not(.active) { color: #fff; }

    button { cursor: pointer; font-family: inherit; }
    .primary { background: var(--accent); color: white; border: none; padding: 6px 12px; border-radius: 3px; font-weight: 500; }
    .primary:hover { background: var(--accent-hover); }
    .ghost { background: transparent; border: 1px solid var(--border); color: var(--text-main); padding: 5px 10px; border-radius: 3px; }
    .ghost:hover { border-color: #666; color: #fff; }
    .smallBtn { padding: 4px 8px; font-size: 12px; }
    .iconBtn { background: none; border: none; color: inherit; font-size: 16px; cursor: pointer; }

    /* --- LAYOUT GŁÓWNY --- */
    .layout {
      display: flex;
      flex: 1;
      height: calc(100vh - 60px);
      overflow: hidden;
    }

    /* --- PANELE BOCZNE --- */
    .panel {
      background-color: var(--bg-panel);
      border: 1px solid var(--border);
      display: flex;
      flex-direction: column;
      min-width: 250px;
    }
    .panel.left { width: 280px; border-right: 1px solid var(--border); }
    .panel.bottom { height: 200px; border-top: 1px solid var(--border); width: 100%; position: absolute; bottom: 0; left: 0; z-index: 10; }
    
    .panelHeader {
      padding: 10px;
      border-bottom: 1px solid var(--border);
      background: #2d2d30;
    }
    .panelTitle { font-weight: bold; color: var(--text-bright); margin-bottom: 8px; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px; }
    .row { display: flex; gap: 5px; }
    .search {
      flex: 1; background: #3c3c3c; border: 1px solid #3c3c3c;
      color: #fff; padding: 5px 8px; border-radius: 3px; font-size: 13px;
    }
    .search:focus { border-color: var(--accent); }

    .pool {
      flex: 1; overflow-y: auto; padding: 10px;
      display: flex; flex-direction: column; gap: 8px;
    }
    /* Elementy w puli (Ludzie/Zadania) */
    .pool-item {
      background: #333; padding: 8px 12px; border-radius: 4px;
      border: 1px solid #444; cursor: grab; font-size: 13px;
      display: flex; justify-content: space-between; align-items: center;
    }
    .pool-item:hover { background: #3e3e42; }
    .pool-item.dragging { opacity: 0.5; }

    /* --- CENTRUM --- */
    .center {
      flex: 1;
      display: flex;
      flex-direction: column;
      background: #1e1e1e;
      position: relative;
      overflow: hidden;
    }
    .toolbar {
      height: 50px;
      background: #252526;
      border-bottom: 1px solid var(--border);
      display: flex;
      align-items: center;
      padding: 0 15px;
      gap: 15px;
    }
    .field { display: flex; align-items: center; gap: 5px; font-size: 13px; }
    .field label { color: #888; font-size: 11px; text-transform: uppercase; }
    select {
      background: #3c3c3c; color: #fff; border: 1px solid #555;
      padding: 4px 8px; border-radius: 3px; min-width: 150px;
    }
    .spacer { flex: 1; }
    .zoom { display: flex; align-items: center; gap: 5px; background: #333; padding: 2px 5px; border-radius: 3px; }
    .zoomLabel { font-size: 11px; width: 35px; text-align: center; }

    /* Obszar zespołów */
    .teamsScroll {
      flex: 1; overflow: auto; padding: 20px;
      display: flex; gap: 20px; align-items: flex-start;
    }
    .teams { display: flex; gap: 15px; min-width: 100%; }
    
    /* Karta Zespołu */
    .team-card {
      width: 280px;
      background: #2d2d30;
      border: 1px solid var(--border);
      border-radius: 6px;
      display: flex; flex-direction: column;
      box-shadow: 0 4px 6px rgba(0,0,0,0.3);
    }
    .team-header {
      padding: 10px; background: #3e3e42;
      border-bottom: 1px solid var(--border);
      border-radius: 6px 6px 0 0;
      font-weight: bold; color: #fff;
      display: flex; justify-content: space-between;
    }
    .team-body {
      padding: 10px; min-height: 150px;
      display: flex; flex-direction: column; gap: 5px;
    }
    .team-member {
      background: #3c3c3c; padding: 6px; border-radius: 3px;
      font-size: 12px; border-left: 3px solid var(--accent);
      cursor: pointer;
    }
    .team-task {
      background: #444; padding: 6px; border-radius: 3px;
      font-size: 12px; border-left: 3px solid var(--success);
      margin-top: 5px;
    }

    /* Widoki alternatywne (Plan/Ludzie/Zadania) */
    .view { display: none; width: 100%; height: 100%; overflow: auto; padding: 20px; }
    .view.active { display: block; }
    .viewCard { background: var(--bg-panel); padding: 20px; border-radius: 8px; }
    .viewTitle { font-size: 20px; font-weight: bold; margin-bottom: 20px; color: #fff; border-bottom: 2px solid var(--accent); display: inline-block; padding-bottom: 5px;}
    .list { display: flex; flex-direction: column; gap: 10px; }

    /* Resizer */
    .hResizer {
      height: 5px; background: var(--bg-header);
      cursor: ns-resize; width: 100%;
    }

    /* Modale */
    .modal {
      position: fixed; top: 0; left: 0; width: 100%; height: 100%;
      background: rgba(0,0,0,0.7); z-index: 1000;
      display: flex; justify-content: center; align-items: center;
    }
    .modal[hidden] { display: none; }
    .modalCard {
      background: var(--bg-panel); width: 400px; padding: 20px;
      border-radius: 6px; border: 1px solid var(--border);
      box-shadow: 0 10px 25px rgba(0,0,0,0.5);
    }
    .modalTop { display: flex; justify-content: space-between; margin-bottom: 15px; }
    .modalTitle { font-weight: bold; color: #fff; font-size: 16px; }
    .modalRow { margin-top: 20px; display: flex; justify-content: flex-end; gap: 10px; }
    .modal input { width: 100%; padding: 8px; margin-bottom: 10px; background: #333; border: 1px solid #555; color: #fff; border-radius: 3px; }
    
    /* Toast */
    .toast {
      position: fixed; bottom: 220px; right: 20px;
      background: var(--accent); color: white;
      padding: 10px 20px; border-radius: 4px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.3);
      animation: fadeIn 0.3s;
    }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

  </style>
</head>
<body>

  <!-- HEADER -->
  <header class="topbar">
    <div class="title">
      <div class="appname">PLAN BRYGAD <span class="ver">v0.5.0-WEB</span></div>
      <div class="subtitle">DnD: ludzie → zespoły, zadania → zespoły. Druk: 1 budowa = 1 strona PDF.</div>
    </div>
    <div class="actions">
      <div id="viewTabs" class="tabs">
        <button class="tabBtn active" onclick="switchView('plan')">Plan</button>
        <button class="tabBtn" onclick="switchView('people')">Ludzie</button>
        <button class="tabBtn" onclick="switchView('tasks')">Zadania</button>
      </div>
      <button id="btnHealth" class="ghost" title="Sprawdź obsadę"><span>ℹ️</span></button>
      <button id="btnExportAll" class="primary">DRUKUJ (PDF)</button>
      <button onclick="location.href='logout.php'" class="ghost" style="border-color:#ce4747; color:#ce4747;">Wyloguj</button>
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
          <button id="btnAddWorker" class="primary smallBtn" onclick="openModal('workerModal')">+ Dodaj</button>
        </div>
      </div>
      <div id="workersPool" class="pool" aria-label="Pula ludzi">
        <!-- Tutaj JS wstawi ludzi -->
        <div style="color:#666; text-align:center; margin-top:20px;">Ładowanie...</div>
      </div>
    </aside>

    <!-- CENTER SECTION -->
    <section class="center">
      <div class="toolbar">
        <div class="field">
          <label>BUDOWA</label>
          <select id="buildingSelect">
            <option value="">-- Wybierz budowę --</option>
          </select>
          <button id="btnEditBuilding" class="ghost smallBtn">✎</button>
          <button id="btnDeleteBuilding" class="ghost smallBtn">🗑</button>
        </div>
        <div class="spacer"></div>
        <button id="btnTogglePeople" class="ghost">Ludzie ◀/▶</button>
        <div class="zoom">
          <button id="zoomOut" class="ghost">−</button>
          <div id="zoomLabel" class="zoomLabel">100%</div>
          <button id="zoomIn" class="ghost">+</button>
        </div>
        <button id="btnAddTeam" class="ghost">+ Dodaj zespół</button>
        <button id="btnAddBuilding" class="ghost" onclick="openTextModal('Dodaj nową budowę', 'Nazwa budowy')">+ Dodaj budowę</button>
      </div>

      <!-- VIEW: PLAN -->
      <div id="viewPlan" class="view active">
        <div id="teamsScroll" class="teamsScroll">
          <div id="teamsArea" class="teams">
            <!-- Tutaj JS wstawi zespoły dla wybranej budowy -->
            <div style="color:#666; width:100%; text-align:center; margin-top:50px;">Wybierz budowę z listy, aby zobaczyć zespoły.</div>
          </div>
        </div>
      </div>

      <!-- VIEW: PEOPLE LIST -->
      <div id="viewPeople" class="view">
        <div class="viewCard">
          <div class="viewTitle">Ludzie – pełna lista i przypisania</div>
          <div id="peopleViewList" class="list"></div>
        </div>
      </div>

      <!-- VIEW: TASKS LIST -->
      <div id="viewTasks" class="view">
        <div class="viewCard">
          <div class="viewTitle">Zadania – przypisane do zespołów</div>
          <div id="tasksViewList" class="list"></div>
        </div>
      </div>
    </section>

    <!-- RESIZER -->
    <div id="hResizer" class="hResizer"></div>

    <!-- BOTTOM PANEL: TASKS POOL -->
    <aside class="panel bottom">
      <div class="panelHeader">
        <div class="panelTitle">ZADANIA (pula)</div>
        <div class="row">
          <input id="taskInput" class="search" placeholder="Wpisz zadanie i Enter..." />
          <button id="btnAddTask" class="primary smallBtn" onclick="addTaskFromInput()">Dodaj</button>
        </div>
      </div>
      <div id="tasksPool" class="pool tasks" aria-label="Pula zadań">
        <!-- Tutaj JS wstawi zadania -->
        <div style="color:#666; text-align:center; margin-top:20px;">Ładowanie...</div>
      </div>
    </aside>
  </main>

  <!-- MODALS -->
  <div id="workerModal" class="modal" hidden>
    <div class="modalCard">
      <div class="modalTop"><div class="modalTitle">Pracownik</div><button class="iconBtn modalClose" onclick="closeModal('workerModal')">✕</button></div>
      <label>Imię i nazwisko</label>
      <input id="wmName" class="search" placeholder="np. Jan Kowalski" />
      <div class="row modalRow">
        <button class="primary" onclick="saveWorker()">Zapisz</button>
        <button class="ghost" onclick="closeModal('workerModal')">Anuluj</button>
      </div>
    </div>
  </div>

  <div id="textModal" class="modal" hidden>
    <div class="modalCard">
      <div class="modalTop"><div class="modalTitle" id="textModalTitle">Wpisz</div><button class="iconBtn modalClose" onclick="closeModal('textModal')">✕</button></div>
      <label id="textModalLabel">Wartość</label>
      <input id="textModalInput" class="search" />
      <div class="row modalRow">
        <button class="primary" id="textModalOk" onclick="confirmTextModal()">OK</button>
        <button class="ghost" onclick="closeModal('textModal')">Anuluj</button>
      </div>
    </div>
  </div>

  <div id="toast" class="toast" hidden></div>

 <script>
    // --- ZMIENNE GLOBALNE ---
    let allWorkers = [];
    let allTasks = [];
    let allProjects = [];
    let currentProjectId = null;

    // --- INICJALIZACJA ---
    document.addEventListener('DOMContentLoaded', () => {
        console.log("Inicjalizacja aplikacji...");
        loadData();
        setupEventListeners();
    });

    // GŁÓWNA FUNKCJA ŁADUJĄCA DANE
    async function loadData() {
        console.log("Pobieranie danych z serwera...");
        try {
            const [workersRes, tasksRes, projectsRes] = await Promise.all([
                fetch('api/workers.php'),
                fetch('api/tasks.php'),
                fetch('api/projects.php')
            ]);

            if (!workersRes.ok || !tasksRes.ok || !projectsRes.ok) {
                throw new Error("Błąd połączenia z API (sprawdź konsolę Network)");
            }

            allWorkers = await workersRes.json();
            allTasks = await tasksRes.json();
            allProjects = await projectsRes.json();

            console.log("Dane pobrane:", { workers: allWorkers.length, tasks: allTasks.length, projects: allProjects.length });

            renderWorkersPool();
            renderTasksPool();
            renderProjectSelect();
            
            // Jeśli mamy wybraną budowę, odśwież jej widok
            if (currentProjectId) {
                selectProject(currentProjectId);
            } else if (allProjects.length === 1) {
                // Automatycznie wybierz jedyną budowę
                selectProject(allProjects[0].id);
            } else if (allProjects.length > 1) {
                // Jeśli jest więcej, zostaw wybór użytkownikowi lub wybierz pierwszą
                // selectProject(allProjects[0].id); 
            }

        } catch (error) {
            console.error("Krytyczny błąd ładowania danych:", error);
            showToast("Błąd łączenia z bazą danych! Sprawdź pliki API.");
        }
    }

    // --- RENDEROWANIE PULI PRACOWNIKÓW ---
    function renderWorkersPool() {
        const container = document.getElementById('workersPool');
        container.innerHTML = '';
        if (allWorkers.length === 0) {
            container.innerHTML = '<div style="color:#666; text-align:center; margin-top:20px;">Brak pracowników.</div>';
            return;
        }
        allWorkers.forEach(w => {
            const el = document.createElement('div');
            el.className = 'pool-item';
            el.draggable = true;
            el.textContent = w.full_name;
            el.dataset.id = w.id;
            container.appendChild(el);
        });
    }

    // --- RENDEROWANIE PULI ZADAŃ ---
    function renderTasksPool() {
        const container = document.getElementById('tasksPool');
        container.innerHTML = '';
        const unassignedTasks = allTasks.filter(t => !t.assigned_to_team_id);
        
        if (unassignedTasks.length === 0) {
            container.innerHTML = '<div style="color:#666; text-align:center; margin-top:20px;">Brak zadań w puli.</div>';
            return;
        }
        unassignedTasks.forEach(t => {
            const el = document.createElement('div');
            el.className = 'pool-item';
            el.draggable = true;
            el.textContent = t.title;
            el.dataset.id = t.id;
            container.appendChild(el);
        });
    }

    // --- RENDEROWANIE LISTY BUDÓW (SELECT) ---
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

        // Przywróć wybór
        if (currentVal && allProjects.find(p => p.id == currentVal)) {
            sel.value = currentVal;
        }
    }

    // --- LOGIKA WIDOKU PLANU (ZESPOŁY) ---
    async function selectProject(projectId) {
        currentProjectId = projectId;
        const area = document.getElementById('teamsArea');
        
        if (!projectId) {
            area.innerHTML = '<div style="color:#666; width:100%; text-align:center; margin-top:50px;">Wybierz budowę z listy, aby zobaczyć zespoły.</div>';
            return;
        }

        area.innerHTML = '<div style="color:#aaa; text-align:center; margin-top:50px;">Ładowanie zespołów...</div>';

        try {
            const res = await fetch(`api/teams.php?project_id=${projectId}`);
            if (!res.ok) throw new Error("Błąd pobierania zespołów");
            
            const teams = await res.json();
            area.innerHTML = '';

            if (teams.length === 0) {
                area.innerHTML = `
                    <div style="color:#666; width:100%; text-align:center; margin-top:50px;">
                        <p>Brak zespołów dla tej budowy.</p>
                        <button class="primary" onclick="addTeam()">+ Dodaj pierwszy zespół</button>
                    </div>`;
                return;
            }

            teams.forEach(team => {
                const card = document.createElement('div');
                card.className = 'team-card';
                
                // Członkowie
                let membersHtml = '';
                if (team.members && team.members.length > 0) {
                    membersHtml = team.members.map(m => `<div class="team-member">${escapeHtml(m.full_name)}</div>`).join('');
                } else {
                    membersHtml = '<div style="color:#666; font-size:11px; font-style:italic; padding:5px;">Brak ludzi</div>';
                }

                // Zadania
                let tasksHtml = '';
                if (team.tasks && team.tasks.length > 0) {
                    tasksHtml = team.tasks.map(t => `<div class="team-task">${escapeHtml(t.title)}</div>`).join('');
                }

                card.innerHTML = `
                  <div class="team-header">
                    <span>${escapeHtml(team.team_name)}</span>
                    <button class="iconBtn" style="font-size:12px;" onclick="deleteTeam(${team.id})" title="Usuń zespół">🗑</button>
                  </div>
                  <div class="team-body">
                    <div style="font-size:11px; color:#888; margin-bottom:5px; text-transform:uppercase;">Ludzie:</div>
                    ${membersHtml}
                    <div style="height:10px;"></div>
                    <div style="font-size:11px; color:#888; margin-bottom:5px; text-transform:uppercase;">Zadania:</div>
                    ${tasksHtml}
                  </div>
                `;
                area.appendChild(card);
            });
        } catch (e) {
            console.error(e);
            area.innerHTML = '<div style="color:red">Błąd ładowania zespołów.</div>';
        }
    }

    // --- AKCJE CRUD ---

    // 1. ZAPIS PRACOWNIKA
    async function saveWorker() {
        const nameInput = document.getElementById('wmName');
        const name = nameInput.value.trim();
        if (!name) return alert('Podaj imię i nazwisko');

        try {
            const res = await fetch('api/workers.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ full_name: name })
            });
            const result = await res.json();
            if (result.success) {
                closeModal('workerModal');
                nameInput.value = '';
                await loadData();
                showToast('Dodano pracownika');
            } else {
                alert('Błąd: ' + (result.message || 'Nieznany'));
            }
        } catch (e) { alert('Błąd sieci'); }
    }

    // 2. DODAWANIE ZADANIA
    async function addTaskFromInput() {
        const input = document.getElementById('taskInput');
        const title = input.value.trim();
        if (!title) return;
        if (!currentProjectId) return alert('Najpierw wybierz budowę!');

        try {
            const res = await fetch('api/tasks.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ title: title, project_id: currentProjectId })
            });
            const result = await res.json();
            if (result.success) {
                input.value = '';
                await loadData();
                showToast('Dodano zadanie');
            } else {
                alert('Błąd: ' + result.message);
            }
        } catch (e) { alert('Błąd sieci'); }
    }

    // 3. DODAWANIE ZESPOŁU
    async function addTeam() {
        if (!currentProjectId) return alert('Wybierz budowę');
        const name = prompt("Nazwa nowego zespołu (np. Zespół A):");
        if (!name) return;

        try {
            const res = await fetch('api/teams.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ project_id: currentProjectId, team_name: name })
            });
            const result = await res.json();
            if (result.success) {
                selectProject(currentProjectId);
                showToast('Dodano zespół');
            } else {
                alert('Błąd: ' + result.message);
            }
        } catch (e) { alert('Błąd sieci'); }
    }

    // 4. USUWANIE ZESPOŁU
    async function deleteTeam(teamId) {
        if(!confirm("Czy na pewno usunąć ten zespół?")) return;
        try {
            const res = await fetch('api/teams.php', {
                method: 'DELETE',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ id: teamId })
            });
            if (res.ok) {
                selectProject(currentProjectId);
                showToast('Zespół usunięty');
            }
        } catch (e) { alert('Błąd usuwania'); }
    }

    // 5. DODAWANIE BUDOWY (PROJEKTU)
    async function addProject(name) {
        if (!name) return;
        try {
            const res = await fetch('api/projects.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ name: name, status: 'active' })
            });
            const result = await res.json();
            if (result.success) {
                await loadData(); // Odśwież listę select
                // Automatycznie wybierz nowo dodaną budowę
                const newProj = allProjects.find(p => p.name === name); // Uwaga: allProjects jeszcze nie zaktualizowane w tym momencie w zmiennej, ale loadData to zrobi
                // Lepiej poczekać aż loadData skończy i wtedy wybrać ostatni element lub po ID
                setTimeout(() => {
                    if(allProjects.length > 0) {
                        const lastId = allProjects[allProjects.length-1].id;
                        document.getElementById('buildingSelect').value = lastId;
                        selectProject(lastId);
                    }
                }, 100);
                showToast('Dodano budowę');
            } else {
                alert('Błąd: ' + result.message);
            }
        } catch (e) { alert('Błąd sieci'); }
    }

    // 6. USUWANIE BUDOWY (PROJEKTU)
    async function deleteCurrentProject() {
        if (!currentProjectId) return alert('Nie wybrano żadnej budowy do usunięcia.');
        if (!confirm(`Czy na pewno usunąć budowę "${document.getElementById('buildingSelect').options[document.getElementById('buildingSelect').selectedIndex].text}"? Wszystkie zespoły i zadania zostaną utracone!`)) return;

        try {
            const res = await fetch('api/projects.php', {
                method: 'DELETE',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ id: currentProjectId })
            });
            const result = await res.json();
            if (result.success) {
                currentProjectId = null;
                document.getElementById('buildingSelect').value = "";
                await loadData();
                showToast('Budowa usunięta');
            } else {
                alert('Błąd: ' + result.message);
            }
        } catch (e) { alert('Błąd sieci'); }
    }

    // --- NARZĘDZIA I EVENTY ---

    window.switchView = function(viewName) {
        document.querySelectorAll('.view').forEach(v => v.classList.remove('active'));
        document.querySelectorAll('.tabBtn').forEach(b => b.classList.remove('active'));
        
        const viewMap = { 'plan': 'viewPlan', 'people': 'viewPeople', 'tasks': 'viewTasks' };
        const targetId = viewMap[viewName];
        if(targetId) document.getElementById(targetId).classList.add('active');
        
        const buttons = document.querySelectorAll('.tabBtn');
        if(viewName === 'plan') buttons[0].classList.add('active');
        if(viewName === 'people') { buttons[1].classList.add('active'); renderPeopleList(); }
        if(viewName === 'tasks') { buttons[2].classList.add('active'); renderTasksListFull(); }
    };

    window.openModal = function(id) { document.getElementById(id).hidden = false; };
    window.closeModal = function(id) { document.getElementById(id).hidden = true; };
    
    // Obsługa modala tekstowego (uniwersalny do budów/zespołów)
    let textModalCallback = null;

    window.openTextModal = function(title, label, callback) {
        document.getElementById('textModalTitle').textContent = title;
        document.getElementById('textModalLabel').textContent = label;
        document.getElementById('textModalInput').value = '';
        
        textModalCallback = callback;
        
        document.getElementById('textModalOk').onclick = function() {
            const val = document.getElementById('textModalInput').value.trim();
            if(val && textModalCallback) {
                textModalCallback(val);
                closeModal('textModal');
            }
        };
        openModal('textModal');
    };

    window.showToast = function(msg) {
        const t = document.getElementById('toast');
        t.textContent = msg;
        t.hidden = false;
        setTimeout(() => t.hidden = true, 3000);
    };

    function renderPeopleList() {
        const list = document.getElementById('peopleViewList');
        if(!list) return;
        list.innerHTML = allWorkers.map(w => `<div class="pool-item">${escapeHtml(w.full_name)}</div>`).join('');
    }
    
    function renderTasksListFull() {
        const list = document.getElementById('tasksViewList');
        if(!list) return;
        list.innerHTML = allTasks.map(t => `<div class="pool-item">${escapeHtml(t.title)} <small>(${t.status})</small></div>`).join('');
    }

    function escapeHtml(text) {
        if (!text) return text;
        return text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
    }

    function setupEventListeners() {
        // Zmiana budowy w select
        document.getElementById('buildingSelect').addEventListener('change', (e) => {
            selectProject(e.target.value);
        });

        // Przyciski akcji
        document.getElementById('btnAddTeam').onclick = addTeam;
        
        // Dodawanie budowy przez modal
        document.getElementById('btnAddBuilding').onclick = () => {
            openTextModal('Dodaj nową budowę', 'Nazwa budowy', (name) => {
                addProject(name);
            });
        };

        // Usuwanie budowy
        document.getElementById('btnDeleteBuilding').onclick = deleteCurrentProject;

        // Edycja budowy (na razie prosty prompt, można rozwinąć)
        document.getElementById('btnEditBuilding').onclick = () => {
            if(!currentProjectId) return alert('Wybierz budowę do edycji');
            const newName = prompt("Nowa nazwa budowy:", document.getElementById('buildingSelect').options[document.getElementById('buildingSelect').selectedIndex].text);
            if(newName && newName.trim() !== "") {
                // Tutaj trzeba by dodać endpoint PUT w projects.php, na razie tylko alert
                alert("Funkcja edycji wymaga aktualizacji API (PUT). Na razie użyj usuń+dodaj.");
            }
        };

        // Formularze
        document.getElementById('wmName').addEventListener('keypress', (e) => { if(e.key === 'Enter') saveWorker(); });
        document.getElementById('taskInput').addEventListener('keypress', (e) => { if(e.key === 'Enter') addTaskFromInput(); });
    }
</script>
</body>
</html>