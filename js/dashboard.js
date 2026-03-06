// --- ZMIENNE GLOBALNE ---
let allWorkers = [];
let allTasks = [];
let allProjects = [];
let currentProjectId = null;
let assignedWorkerIds = new Set();
let draggedItem = null;

// --- INICJALIZACJA ---
document.addEventListener('DOMContentLoaded', () => {
    console.log("Inicjalizacja aplikacji...");
    loadData();
    setupEventListeners();
});

// GŁÓWNA FUNKCJA ŁADUJĄCA DANE
async function loadData() {
    try {
        const [workersRes, tasksRes, projectsRes, assignedRes] = await Promise.all([
            fetch('api/workers.php'),
            fetch('api/tasks.php'),
            fetch('api/projects.php'),
            fetch('api/team-members.php?action=get_all_assigned')
        ]);

        allWorkers = await workersRes.json();
        allTasks = await tasksRes.json();
        allProjects = await projectsRes.json();
        
        const assignedData = await assignedRes.json();
        assignedWorkerIds = new Set(assignedData.assigned_ids || []);

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

// --- RENDEROWANIE PULI PRACOWNIKÓW ---
function renderWorkersPool() {
    const container = document.getElementById('workersPool');
    if (!container) return;
    container.innerHTML = '';

    if (allWorkers.length === 0) {
        container.innerHTML = '<div style="color:#64748b; text-align:center; margin-top:30px;">Brak pracowników.</div>';
        return;
    }

    const availableWorkers = allWorkers.filter(w => !assignedWorkerIds.has(w.id));
    
    availableWorkers.forEach(w => {
        const el = createDraggableElement(w.full_name, 'worker', w.id);
        container.appendChild(el);
    });
    
    // Po wyrenderowaniu zastosuj filtr, jeśli coś jest wpisane w szukajkę
    filterWorkers();
}

// --- FILTROWANIE ---
function filterWorkers() {
    const searchInput = document.getElementById('workerSearch');
    if (!searchInput) return;
    const term = searchInput.value.toLowerCase();
    const items = document.querySelectorAll('#workersPool .pool-item');
    items.forEach(item => {
        const text = item.textContent.toLowerCase();
        item.style.display = text.includes(term) ? 'flex' : 'none';
    });
}

function renderTasksPool() {
    const container = document.getElementById('tasksPool');
    if (!container) return;
    container.innerHTML = '';
    const unassigned = allTasks.filter(t => !t.assigned_to_team_id);
    unassigned.forEach(t => {
        const el = createDraggableElement(t.title, 'task', t.id);
        container.appendChild(el);
    });
}

function createDraggableElement(text, type, id) {
    const el = document.createElement('div');
    el.className = 'pool-item';
    el.draggable = true;
    el.textContent = text;
    el.dataset.id = id;
    el.dataset.type = type;

    el.addEventListener('dragstart', (e) => {
        draggedItem = { id: id, type: type };
        e.dataTransfer.setData('text/plain', JSON.stringify(draggedItem));
        setTimeout(() => el.style.opacity = '0.5', 0);
    });
    el.addEventListener('dragend', () => { el.style.opacity = '1'; draggedItem = null; });
    return el;
}

function renderProjectSelect() {
    const sel = document.getElementById('buildingSelect');
    if (!sel) return;
    const currentVal = sel.value; 
    sel.innerHTML = '<option value="">-- Wybierz budowę --</option>';
    allProjects.forEach(p => {
        const opt = document.createElement('option');
        opt.value = p.id; opt.textContent = p.name;
        sel.appendChild(opt);
    });
    if (currentVal && allProjects.find(p => p.id == currentVal)) sel.value = currentVal;
}

// --- ZESPOŁY ---
async function selectProject(projectId) {
    currentProjectId = projectId;
    const area = document.getElementById('teamsArea');
    if (!area) return;
    
    if (!projectId) {
        area.innerHTML = '<div style="color:#64748b; width:100%; text-align:center; margin-top:100px;"><p>Wybierz budowę...</p></div>';
        return;
    }

    try {
        const res = await fetch(`api/teams.php?project_id=${projectId}`);
        const teams = await res.json();
        area.innerHTML = '';

        teams.forEach(team => {
            const card = document.createElement('div');
            card.className = 'team-card';
            card.dataset.teamId = team.id;

            let membersHtml = team.members && team.members.length > 0 ? 
                team.members.map(m => createTeamItemHtml(m.full_name, 'worker', m.id, team.id)).join('') : 
                '<div style="color:#64748b; font-size:12px; padding:8px;">Brak ludzi</div>';

            let tasksHtml = team.tasks && team.tasks.length > 0 ? 
                team.tasks.map(t => createTeamItemHtml(t.title, 'task', t.id, team.id, true)).join('') : 
                '<div style="color:#64748b; font-size:12px; padding:8px;">Brak zadań</div>';

            card.innerHTML = `
              <div class="team-card-header">
                <span class="team-card-title">${escapeHtml(team.team_name)}</span>
                <button class="iconBtn" style="color:#ef4444;" onclick="deleteTeam(${team.id})">✕</button>
              </div>
              <div class="team-card-body">
                <div class="team-section">
                    <div class="team-section-label">LUDZIE</div>
                    <div class="drop-zone" data-type="worker" data-team-id="${team.id}">${membersHtml}</div>
                </div>
                <div class="team-section">
                    <div class="team-section-label">ZADANIA</div>
                    <div class="drop-zone" data-type="task" data-team-id="${team.id}">${tasksHtml}</div>
                </div>
              </div>`;
            
            card.querySelectorAll('.drop-zone').forEach(zone => {
                zone.addEventListener('dragover', e => { e.preventDefault(); zone.style.backgroundColor = '#334155'; });
                zone.addEventListener('dragleave', () => zone.style.backgroundColor = 'transparent');
                zone.addEventListener('drop', handleDrop);
            });
            area.appendChild(card);
        });
    } catch (e) { console.error(e); }
}

function createTeamItemHtml(text, type, id, teamId, isTask = false) {
    const borderClass = isTask ? 'team-task-item' : 'team-member-item';
    return `<div class="${borderClass}"><span>${escapeHtml(text)}</span><button class="iconBtn" style="font-size:11px;" onclick="removeFromTeam('${type}', ${id}, ${teamId})">↻</button></div>`;
}

// --- DRAG & DROP ---
async function handleDrop(e) {
    e.preventDefault();
    const zone = e.currentTarget;
    zone.style.backgroundColor = 'transparent';
    if (!draggedItem || draggedItem.type !== zone.dataset.type) return;

    try {
        let success = false;
        if (draggedItem.type === 'worker') {
            const res = await fetch('api/team-members.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({ team_id: zone.dataset.teamId, employee_id: draggedItem.id }) });
            if((await res.json()).success) success = true;
        } else {
            const res = await fetch('api/tasks.php', { method: 'PUT', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({ id: draggedItem.id, assigned_to_team_id: zone.dataset.teamId }) });
            if((await res.json()).success) success = true;
        }
        if (success) { showToast('Przypisano!'); loadData(); }
    } catch (err) { console.error(err); }
}

async function removeFromTeam(type, itemId, teamId) {
    if(!confirm("Cofnąć do puli?")) return;
    try {
        let success = false;
        if (type === 'worker') {
            const res = await fetch('api/team-members.php', { method: 'DELETE', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({ team_id: teamId, employee_id: itemId }) });
            if((await res.json()).success) success = true;
        } else {
            const res = await fetch('api/tasks.php', { method: 'PUT', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({ id: itemId, assigned_to_team_id: null }) });
            if((await res.json()).success) success = true;
        }
        if(success) { showToast('Cofnięto'); loadData(); }
    } catch(e) { console.error(e); }
}

// --- CRUD ---
async function saveWorker() {
    const nameInput = document.getElementById('wmName');
    const name = nameInput.value.trim();
    if (!name) return;
    const res = await fetch('api/workers.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({full_name:name}) });
    if((await res.json()).success) { closeModal('workerModal'); nameInput.value=''; loadData(); showToast('Dodano'); }
}

async function addTaskFromInput() {
    const input = document.getElementById('taskInput');
    if(!input.value.trim() || !currentProjectId) return;
    const res = await fetch('api/tasks.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({title:input.value.trim(), project_id:currentProjectId}) });
    if((await res.json()).success) { input.value=''; loadData(); showToast('Dodano zadanie'); }
}

async function addTeam() {
    if(!currentProjectId) return;
    const name = prompt("Nazwa zespołu:");
    if(!name) return;
    const res = await fetch('api/teams.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({project_id:currentProjectId, team_name:name}) });
    if((await res.json()).success) { selectProject(currentProjectId); showToast('Dodano zespół'); }
}

async function deleteTeam(id) { 
    if(confirm('Usunąć zespół?')) { 
        await fetch('api/teams.php',{method:'DELETE',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:id})}); 
        selectProject(currentProjectId); 
    } 
}

async function addProject(name) {
    const res = await fetch('api/projects.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({name:name, status:'active'}) });
    if((await res.json()).success) { loadData(); showToast('Dodano budowę'); }
}

async function deleteCurrentProject() {
    if(!currentProjectId || !confirm('Usunąć budowę?')) return;
    await fetch('api/projects.php', { method:'DELETE', headers:{'Content-Type':'application/json'}, body:JSON.stringify({id:currentProjectId}) });
    currentProjectId=null; document.getElementById('buildingSelect').value=""; loadData();
}

async function editProjectName() {
    if (!currentProjectId) return;
    const select = document.getElementById('buildingSelect');
    const currentName = select.options[select.selectedIndex].text;
    const newName = prompt("Nowa nazwa budowy:", currentName);
    if (!newName || newName.trim() === '' || newName === currentName) return;

    const res = await fetch('api/projects.php', { method: 'PUT', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({ id: currentProjectId, name: newName.trim() }) });
    if ((await res.json()).success) { showToast('Zmieniono nazwę'); loadData(); }
}

async function resetProject() {
    if (!currentProjectId || !confirm("CZY NA PEWNO WYCZYŚCIĆ PRZYPISANIA?")) return;
    const res = await fetch('api/teams.php', { method: 'DELETE', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({ action: 'reset_project', project_id: currentProjectId }) });
    if ((await res.json()).success) { showToast('Wyczyszczono'); loadData(); }
}

// --- UI ---
window.switchView = (v) => { 
    document.querySelectorAll('.view').forEach(x=>x.classList.remove('active')); 
    document.getElementById('view' + v.charAt(0).toUpperCase() + v.slice(1)).classList.add('active');
    document.querySelectorAll('.tabBtn').forEach(btn => btn.classList.remove('active'));
    // Ten fragment aktywuje odpowiedni przycisk zakładki
    event.currentTarget.classList.add('active');
};
window.openModal = (id) => { document.getElementById(id).hidden = false; };
window.closeModal = (id) => { document.getElementById(id).hidden = true; };

// FUNKCJA MODALA - POPRAWIONA
let textModalCallback = null;
window.openTextModal = (title, label, cb) => {
    document.getElementById('textModalTitle').textContent = title;
    document.getElementById('textModalLabel').textContent = label;
    document.getElementById('textModalInput').value = '';
    textModalCallback = cb;
    
    const okBtn = document.getElementById('textModalOk');
    okBtn.onclick = null; // Czyścimy stare przypisanie
    okBtn.onclick = () => { 
        if(textModalCallback) textModalCallback(document.getElementById('textModalInput').value.trim()); 
        closeModal('textModal'); 
    };
    openModal('textModal');
};

window.showToast = (msg) => { const t=document.getElementById('toast'); t.textContent=msg; t.hidden=false; setTimeout(()=>t.hidden=true, 3000); };
function escapeHtml(t){ if(!t)return t; return t.replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;").replace(/'/g,"&#039;"); }

// --- LISTENERY ---
function setupEventListeners() {
    document.getElementById('buildingSelect').onchange = (e) => selectProject(e.target.value);
    
    // Używamy funkcji anonimowych, aby zapobiec natychmiemu wywołaniu
    document.getElementById('btnAddTeam').onclick = () => addTeam();
    
    document.getElementById('btnAddBuilding').onclick = () => {
        openTextModal('Dodaj budowę', 'Nazwa nowej budowy', (name) => addProject(name));
    };
    
    document.getElementById('btnDeleteBuilding').onclick = () => deleteCurrentProject();
    document.getElementById('btnEditBuilding').onclick = () => editProjectName();
    document.getElementById('btnResetProject').onclick = () => resetProject();
    
    document.getElementById('workerSearch').addEventListener('input', filterWorkers);
    
    document.getElementById('wmName').onkeypress = (e) => { if(e.key === 'Enter') saveWorker(); };
    document.getElementById('taskInput').onkeypress = (e) => { if(e.key === 'Enter') addTaskFromInput(); };
}