// --- ZMIENNE GLOBALNE ---
let allWorkers = [];
let allTasks = [];
let allProjects = [];
let currentProjectId = null;
let assignedWorkerIds = new Set();
let draggedItem = null;

// --- INICJALIZACJA ---
document.addEventListener('DOMContentLoaded', () => {
    loadData();
    setupEventListeners();
});

// GŁÓWNA FUNKCJA ŁADUJĄCA DANE
async function loadData() {
    try {
        // 1. Pobieranie wszystkich danych z serwera
        const [wR, tR, pR, aR] = await Promise.all([
            fetch('api/workers.php'), 
            fetch('api/tasks.php'), 
            fetch('api/projects.php'), 
            fetch('api/team-members.php?action=get_all_assigned')
        ]);

        // 2. Parsowanie odpowiedzi na obiekty JSON
        allWorkers = await wR.json(); 
        allTasks = await tR.json(); 
        allProjects = await pR.json();
        const aD = await aR.json(); 
        
        // 3. Przygotowanie zbioru zajętych pracowników
        assignedWorkerIds = new Set((aD.assigned_ids || []).map(id => parseInt(id)));
        
        // --- KLUCZOWY MOMENT: Wywołanie funkcji rysujących interfejs ---
        
        renderWorkersPool();  // Rysuje lewą kolumnę (ludzie)
        renderTasksPool();    // Rysuje dolny panel (zadania)
        renderProjectSelect(); // Rysuje listę wyboru budowy
        
        // 4. Jeśli mamy wybraną budowę, odświeżamy jej zespoły
        if (currentProjectId) {
            selectProject(currentProjectId);
        } else if (allProjects.length === 1) {
            currentProjectId = allProjects[0].id;
            document.getElementById('buildingSelect').value = currentProjectId;
            selectProject(currentProjectId);
        }
    } catch (e) { 
        console.error("Błąd podczas odświeżania danych:", e); 
    }
}

// --- RENDEROWANIE PULI PRACOWNIKÓW ---
function renderWorkersPool() {
    const container = document.getElementById('workersPool');
    if (!container) return;
    container.innerHTML = '';

    const avail = allWorkers.filter(w => !assignedWorkerIds.has(parseInt(w.id)));
    
    if (avail.length === 0) {
        container.innerHTML = '<div style="color:#64748b; text-align:center; margin-top:30px;">Brak wolnych pracowników.</div>';
        return;
    }

    avail.forEach(w => {
        const el = document.createElement('div');
        el.className = 'pool-item';
        el.draggable = true;
        el.textContent = w.full_name;
        el.dataset.id = w.id;
        
        el.addEventListener('dragstart', (e) => {
            draggedItem = { id: parseInt(w.id), type: 'worker' };
            e.dataTransfer.setData('text/plain', JSON.stringify(draggedItem));
            setTimeout(() => el.style.opacity = '0.5', 0);
        });
        el.addEventListener('dragend', () => { el.style.opacity = '1'; draggedItem = null; });
        
        container.appendChild(el);
    });
    filterWorkers();
}

// --- FILTROWANIE LUDZI ---
function filterWorkers() {
    const term = document.getElementById('workerSearch')?.value.toLowerCase() || "";
    document.querySelectorAll('#workersPool .pool-item').forEach(item => {
        item.style.display = item.textContent.toLowerCase().includes(term) ? 'flex' : 'none';
    });
}

// --- RENDEROWANIE PULI ZADAŃ ---
function renderTasksPool() {
    const c = document.getElementById('tasksPool'); 
    if (!c) return; 
    c.innerHTML = '';

    // FILTROWANIE: Pokazujemy tylko zadania, które nie mają przypisanego zespołu
    const unassignedTasks = allTasks.filter(t => {
        return t.assigned_to_team_id === null || t.assigned_to_team_id === 0 || t.assigned_to_team_id === undefined;
    });

    if (unassignedTasks.length === 0) {
        c.innerHTML = '<div style="color:#64748b;text-align:center;margin-top:30px;">Brak wolnych zadań.</div>';
        return;
    }

    unassignedTasks.forEach(t => {
        const el = document.createElement('div'); 
        el.className = 'pool-item'; 
        el.draggable = true; 
        el.textContent = t.title;
        el.dataset.id = t.id;
        el.dataset.type = 'task';

        el.addEventListener('dragstart', (e) => { 
            draggedItem = { id: parseInt(t.id), type: 'task' }; 
            e.dataTransfer.setData('text/plain', JSON.stringify(draggedItem)); 
        });
        c.appendChild(el);
    });
}

function renderProjectSelect() {
    const sel = document.getElementById('buildingSelect');
    if (!sel) return;
    const currentVal = sel.value; 
    sel.innerHTML = '<option value="">-- Wybierz budowę --</option>';
    allProjects.forEach(p => {
        const opt = document.createElement('option');
        opt.value = p.id;
        opt.textContent = p.name;
        sel.appendChild(opt);
    });
    if (currentVal) sel.value = currentVal;
}

// --- LOGIKA WIDOKU PLANU (ZESPOŁY) ---
async function selectProject(id) {
    id = parseInt(id);
    const area = document.getElementById('teamsArea');
    if (!area) return;

    if (!id || isNaN(id)) {
        currentProjectId = null;
        area.innerHTML = '<div style="color:#64748b; width:100%; text-align:center; margin-top:100px;"><p>Wybierz budowę...</p></div>';
        return;
    }

    currentProjectId = id;
    area.innerHTML = '<div style="color:#64748b; text-align:center; margin-top:50px;">Ładowanie zespołów...</div>';

    try {
        const res = await fetch(`api/teams.php?project_id=${id}`);
        const teams = await res.json();
        area.innerHTML = '';

        if (teams.length === 0) {
            area.innerHTML = `<div style="text-align:center; margin-top:50px;"><button class="primary" onclick="addTeam()">+ Dodaj pierwszy zespół</button></div>`;
            return;
        }

        teams.forEach(team => {
            const card = document.createElement('div');
            card.className = 'team-card';
            
            const mHtml = team.members?.length ? 
                team.members.map(m => `<div class="team-member-item"><span>${escapeHtml(m.full_name)}</span><button class="iconBtn" onclick="removeFromTeam('worker', ${m.id}, ${team.id})">↻</button></div>`).join('') : 
                '<div style="color:#64748b; font-size:12px; padding:8px;">Brak ludzi</div>';
            
            const tHtml = team.tasks?.length ? 
                team.tasks.map(t => `<div class="team-task-item"><span>${escapeHtml(t.title)}</span><button class="iconBtn" onclick="removeFromTeam('task', ${t.id}, ${team.id})">↻</button></div>`).join('') : 
                '<div style="color:#64748b; font-size:12px; padding:8px;">Brak zadań</div>';

            card.innerHTML = `
              <div class="team-card-header">
                <span class="team-card-title">${escapeHtml(team.team_name)}</span>
                <div style="display:flex; gap:4px;">
                    <button class="iconBtn" onclick="editTeamName(${team.id}, '${escapeHtml(team.team_name)}')" title="Edytuj nazwę">✎</button>
                    <button class="iconBtn" style="color:#ef4444;" onclick="deleteTeam(${team.id})">✕</button>
                </div>
              </div>
              <div class="team-card-body">
                <div class="team-section">
                    <div class="team-section-label">LUDZIE</div>
                    <div class="drop-zone" data-type="worker" data-team-id="${team.id}">${mHtml}</div>
                </div>
                <div class="team-section">
                    <div class="team-section-label">ZADANIA</div>
                    <div class="drop-zone" data-type="task" data-team-id="${team.id}">${tHtml}</div>
                </div>
              </div>`;

            card.querySelectorAll('.drop-zone').forEach(z => {
                z.addEventListener('dragover', e => { e.preventDefault(); z.style.backgroundColor = '#334155'; });
                z.addEventListener('dragleave', () => z.style.backgroundColor = 'transparent');
                z.addEventListener('drop', handleDrop);
            });
            area.appendChild(card);
        });
    } catch (e) { 
        console.error("Błąd w selectProject:", e);
        area.innerHTML = '<div style="color:red">Błąd podczas ładowania zespołów.</div>';
    }
}

// Dodaj też samą funkcję editTeamName, jeśli jej nie masz:
async function editTeamName(teamId, oldName) {
    const newName = prompt("Nowa nazwa zespołu:", oldName);
    if (!newName || newName.trim() === '' || newName.trim() === oldName) return;

    const res = await fetch('api/teams.php', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: teamId, team_name: newName.trim() })
    });
    const r = await res.json();
    if (r.success) {
        showToast('Zmieniono nazwę zespołu');
        selectProject(currentProjectId);
    }
}


function escapeHtml(t){ return t ? String(t).replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;") : ''; }

// --- DRAG & DROP ---
async function handleDrop(e) {
    e.preventDefault();
    const zone = e.currentTarget;
    zone.style.backgroundColor = 'transparent';

    // 1. Walidacja: Czy ciągniemy właściwy typ do właściwej strefy (worker do worker, task do task)
    if (!draggedItem || draggedItem.type !== zone.dataset.type) {
        showToast("Nie można tu upuścić tego elementu!");
        return;
    }

    try {
        let success = false;
        const teamId = parseInt(zone.dataset.teamId);

        // 2. Obsługa PRZYPISYWANIA PRACOWNIKA
        if (draggedItem.type === 'worker') {
            const res = await fetch('api/team-members.php', { 
                method: 'POST', 
                headers: {'Content-Type': 'application/json'}, 
                body: JSON.stringify({ 
                    team_id: teamId, 
                    employee_id: draggedItem.id 
                }) 
            });
            const r = await res.json();
            if(r.success) success = true;
            else showToast(r.message); // Wyświetla np. "Pracownik już przypisany!"

        // 3. Obsługa PRZYPISYWANIA ZADANIA
        } else if (draggedItem.type === 'task') {
            const res = await fetch('api/tasks.php', { 
                method: 'PUT', 
                headers: {'Content-Type': 'application/json'}, 
                body: JSON.stringify({ 
                    id: draggedItem.id, 
                    assigned_to_team_id: teamId 
                }) 
            });
            const r = await res.json();
            if(r.success) success = true;
        }

        // 4. Jeśli operacja w bazie się udała, odświeżamy dane
        if (success) {
            showToast('Przypisano pomyślnie!');
            
            // To wywołanie jest kluczowe - pobiera nowe dane z bazy (z nowymi assigned_to_team_id)
            // i automatycznie uruchamia renderowanie list, co ukrywa przypisane elementy.
            await loadData(); 
        }

    } catch (err) {
        console.error("Błąd podczas dropu:", err);
        showToast("Wystąpił błąd połączenia.");
    }
}

async function removeFromTeam(type, itemId, teamId) {
    if(!confirm("Cofnąć do puli?")) return;
    try {
        const url = type === 'worker' ? 'api/team-members.php' : 'api/tasks.php';
        const body = type === 'worker' ? 
            { team_id: parseInt(teamId), employee_id: parseInt(itemId) } : 
            { id: parseInt(itemId), assigned_to_team_id: null };

        const res = await fetch(url, { 
            method: type === 'worker' ? 'DELETE' : 'PUT', 
            headers: {'Content-Type': 'application/json'}, 
            body: JSON.stringify(body) 
        });
        
        if((await res.json()).success) {
            showToast('Cofnięto');
            loadData();
        }
    } catch(e) { console.error(e); }
}

// --- CRUD ---
async function saveWorker() {
    const nameInput = document.getElementById('wmName');
    const n = nameInput.value.trim(); 
    if (!n) return;

    try {
        const res = await fetch('api/workers.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({full_name:n}) });
        const r = await res.json();
        if(r.success) { 
            closeModal('workerModal'); 
            nameInput.value=''; 
            loadData(); 
            showToast('Dodano pracownika');
        } else {
            alert(r.message); // Komunikat o duplikacie z PHP
        }
    } catch (e) { alert("Błąd połączenia."); }
}

async function addTaskFromInput() {
    const v = document.getElementById('taskInput').value.trim();
    if(!v || !currentProjectId) return alert("Wybierz budowę!");
    const res = await fetch('api/tasks.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({title:v, project_id:currentProjectId}) });
    if((await res.json()).success) { document.getElementById('taskInput').value=''; loadData(); }
}

async function addTeam() {
    if(!currentProjectId) return alert("Wybierz budowę!");
    const n = prompt("Nazwa zespołu:");
    if(!n) return;
    const res = await fetch('api/teams.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({project_id:currentProjectId, team_name:n}) });
    if((await res.json()).success) selectProject(currentProjectId);
}

async function deleteTeam(id) { 
    if(confirm('Usunąć zespół?')) { 
        await fetch('api/teams.php',{method:'DELETE',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:id})}); 
        selectProject(currentProjectId); 
    } 
}

async function addProject(n) {
    const res = await fetch('api/projects.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({name:n}) });
    if((await res.json()).success) loadData();
}

async function deleteCurrentProject() {
    if(!currentProjectId || !confirm('Usunąć budowę? Wszystkie przypisania zostaną utracone.')) return;
    await fetch('api/projects.php', { method:'DELETE', headers:{'Content-Type':'application/json'}, body:JSON.stringify({id:currentProjectId}) });
    currentProjectId = null;
    loadData();
}

async function editProjectName() {
    if (!currentProjectId) return;
    const sel = document.getElementById('buildingSelect');
    const n = prompt("Nowa nazwa:", sel.options[sel.selectedIndex].text);
    if (!n) return;
    await fetch('api/projects.php', { method: 'PUT', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({ id: currentProjectId, name: n }) });
    loadData();
}

async function resetProject() {
    if (!currentProjectId || !confirm("Czy na pewno wyczyścić wszystkie przypisania w tej budowie?")) return;
    await fetch('api/teams.php', { method: 'DELETE', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({ action: 'reset_project', project_id: currentProjectId }) });
    loadData();
}
async function editTeamName(teamId, oldName) {
    const newName = prompt("Nowa nazwa zespołu:", oldName);
    if (!newName || newName.trim() === '' || newName.trim() === oldName) return;

    try {
        const res = await fetch('api/teams.php', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                id: teamId, 
                team_name: newName.trim() 
            })
        });
        
        const r = await res.json();
        if (r.success) {
            showToast('Nazwa zespołu zmieniona');
            selectProject(currentProjectId); // Odśwież widok zespołów
        } else {
            alert(r.message || "Błąd podczas zmiany nazwy.");
        }
    } catch (e) {
        console.error(e);
        alert("Błąd połączenia.");
    }
}


// --- UI LISTENERS ---
function setupEventListeners() {
    document.getElementById('buildingSelect').onchange = (e) => selectProject(e.target.value);
    document.querySelectorAll('.tabBtn').forEach(b => b.onclick = () => switchView(b.dataset.view));
    
    document.getElementById('btnAddTeam').onclick = () => addTeam();
    document.getElementById('btnAddBuilding').onclick = () => openTextModal('Dodaj budowę', 'Nazwa nowej budowy', (n) => addProject(n));
    document.getElementById('btnDeleteBuilding').onclick = () => deleteCurrentProject();
    document.getElementById('btnEditBuilding').onclick = () => editProjectName();
    document.getElementById('btnResetProject').onclick = () => resetProject();
    
    document.getElementById('btnAddWorker').onclick = () => openModal('workerModal');
    document.getElementById('btnSaveWorker').onclick = () => saveWorker();
    document.getElementById('textModalOk').onclick = () => confirmTextModal();
    
    document.getElementById('workerSearch').oninput = () => filterWorkers();
    document.querySelectorAll('.modalClose').forEach(b => b.onclick = () => closeModal(b.dataset.modal));
}

window.switchView = (v) => {
    document.querySelectorAll('.view').forEach(x => x.classList.remove('active'));
    document.getElementById('view' + v.charAt(0).toUpperCase() + v.slice(1)).classList.add('active');
    document.querySelectorAll('.tabBtn').forEach(b => b.classList.toggle('active', b.dataset.view === v));
    if(v === 'people') renderPeopleList();
};

window.openModal = (id) => document.getElementById(id).hidden = false;
window.closeModal = (id) => document.getElementById(id).hidden = true;

let textModalCallback = null;
window.openTextModal = (title, label, cb) => {
    document.getElementById('textModalTitle').textContent = title;
    document.getElementById('textModalLabel').textContent = label;
    document.getElementById('textModalInput').value = '';
    textModalCallback = cb; 
    openModal('textModal');
};

function confirmTextModal() { 
    const v = document.getElementById('textModalInput').value.trim(); 
    if(v && textModalCallback) textModalCallback(v); 
    closeModal('textModal'); 
}

window.showToast = (m) => { 
    const t = document.getElementById('toast'); 
    t.textContent = m; t.hidden = false; 
    setTimeout(() => t.hidden = true, 3000); 
};

function renderPeopleList() {
    const list = document.getElementById('peopleViewList');
    fetch('api/team-members.php?action=get_all_assignments').then(r => r.json()).then(data => {
        list.innerHTML = allWorkers.map(w => {
            const a = data.find(assign => parseInt(assign.employee_id) === parseInt(w.id));
            return `<div class="pool-item">${escapeHtml(w.full_name)} ${a ? `<small style="color:#64748b;">(${escapeHtml(a.project_name)} / ${escapeHtml(a.team_name)})</small>` : ''}</div>`;
        }).join('');
    });
}