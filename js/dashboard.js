let allWorkers = [];
let allTasks = [];
let allProjects = [];
let currentProjectId = null;
let assignedWorkerIds = new Set();
let draggedItem = null;

document.addEventListener('DOMContentLoaded', () => {
    loadData();
    setupEventListeners();
});

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

function renderWorkersPool() {
    const container = document.getElementById('workersPool');
    if (!container) return;
    container.innerHTML = '';
    const availableWorkers = allWorkers.filter(w => !assignedWorkerIds.has(w.id));
    availableWorkers.forEach(w => {
        const el = createDraggableElement(w.full_name, 'worker', w.id);
        container.appendChild(el);
    });
    filterWorkers();
}

function filterWorkers() {
    const term = document.getElementById('workerSearch')?.value.toLowerCase() || "";
    document.querySelectorAll('#workersPool .pool-item').forEach(item => {
        item.style.display = item.textContent.toLowerCase().includes(term) ? 'flex' : 'none';
    });
}

function renderTasksPool() {
    const container = document.getElementById('tasksPool');
    if (!container) return;
    container.innerHTML = '';
    allTasks.filter(t => !t.assigned_to_team_id).forEach(t => {
        container.appendChild(createDraggableElement(t.title, 'task', t.id));
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
            const mHtml = team.members?.length ? team.members.map(m => createItemHtml(m.full_name, 'worker', m.id, team.id)).join('') : '<div style="color:#64748b; font-size:12px; padding:8px;">Brak ludzi</div>';
            const tHtml = team.tasks?.length ? team.tasks.map(t => createItemHtml(t.title, 'task', t.id, team.id, true)).join('') : '<div style="color:#64748b; font-size:12px; padding:8px;">Brak zadań</div>';
            card.innerHTML = `
              <div class="team-card-header"><span class="team-card-title">${escapeHtml(team.team_name)}</span><button class="iconBtn" style="color:#ef4444;" onclick="deleteTeam(${team.id})">✕</button></div>
              <div class="team-card-body">
                <div class="team-section"><div class="team-section-label">LUDZIE</div><div class="drop-zone" data-type="worker" data-team-id="${team.id}">${mHtml}</div></div>
                <div class="team-section"><div class="team-section-label">ZADANIA</div><div class="drop-zone" data-type="task" data-team-id="${team.id}">${tHtml}</div></div>
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

function createItemHtml(text, type, id, teamId, isTask = false) {
    const cls = isTask ? 'team-task-item' : 'team-member-item';
    return `<div class="${cls}"><span>${escapeHtml(text)}</span><button class="iconBtn" style="font-size:11px;" onclick="removeFromTeam('${type}', ${id}, ${teamId})">↻</button></div>`;
}

async function handleDrop(e) {
    e.preventDefault();
    const zone = e.currentTarget;
    zone.style.backgroundColor = 'transparent';
    if (!draggedItem || draggedItem.type !== zone.dataset.type) return;
    try {
        const url = draggedItem.type === 'worker' ? 'api/team-members.php' : 'api/tasks.php';
        const method = draggedItem.type === 'worker' ? 'POST' : 'PUT';
        const body = draggedItem.type === 'worker' ? { team_id: zone.dataset.teamId, employee_id: draggedItem.id } : { id: draggedItem.id, assigned_to_team_id: zone.dataset.teamId };
        const res = await fetch(url, { method: method, headers: {'Content-Type': 'application/json'}, body: JSON.stringify(body) });
        if((await res.json()).success) { showToast('Przypisano!'); loadData(); }
    } catch (err) { console.error(err); }
}

async function removeFromTeam(type, itemId, teamId) {
    if(!confirm("Cofnąć do puli?")) return;
    try {
        const url = type === 'worker' ? 'api/team-members.php' : 'api/tasks.php';
        const method = type === 'worker' ? 'DELETE' : 'PUT';
        const body = type === 'worker' ? { team_id: teamId, employee_id: itemId } : { id: itemId, assigned_to_team_id: null };
        const res = await fetch(url, { method: method, headers: {'Content-Type': 'application/json'}, body: JSON.stringify(body) });
        if((await res.json()).success) { showToast('Cofnięto'); loadData(); }
    } catch(e) { console.error(e); }
}

async function saveWorker() {
    const name = document.getElementById('wmName').value.trim();
    if (!name) return;
    const res = await fetch('api/workers.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({full_name:name}) });
    if((await res.json()).success) { closeModal('workerModal'); document.getElementById('wmName').value=''; loadData(); showToast('Dodano'); }
}

async function addTaskFromInput() {
    const val = document.getElementById('taskInput').value.trim();
    if(!val || !currentProjectId) return;
    const res = await fetch('api/tasks.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({title:val, project_id:currentProjectId}) });
    if((await res.json()).success) { document.getElementById('taskInput').value=''; loadData(); showToast('Dodano zadanie'); }
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
    const sel = document.getElementById('buildingSelect');
    const oldName = sel.options[sel.selectedIndex].text;
    const newName = prompt("Nowa nazwa budowy:", oldName);
    if (!newName || newName.trim() === '' || newName === oldName) return;
    const res = await fetch('api/projects.php', { method: 'PUT', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({ id: currentProjectId, name: newName.trim() }) });
    if ((await res.json()).success) { showToast('Zmieniono nazwę'); loadData(); }
}

async function resetProject() {
    if (!currentProjectId || !confirm("CZY NA PEWNO WYCZYŚCIĆ PRZYPISANIA?")) return;
    const res = await fetch('api/teams.php', { method: 'DELETE', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({ action: 'reset_project', project_id: currentProjectId }) });
    if ((await res.json()).success) { showToast('Wyczyszczono'); loadData(); }
}

// Poprawiona funkcja switchView, aby działała bez "event" w HTML
window.switchView = (viewName) => { 
    document.querySelectorAll('.view').forEach(x => x.classList.remove('active')); 
    document.getElementById('view' + viewName.charAt(0).toUpperCase() + viewName.slice(1)).classList.add('active');
    
    document.querySelectorAll('.tabBtn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.view === viewName);
    });
    
    if(viewName === 'people') renderPeopleList();
    if(viewName === 'tasks') renderTasksListFull();
};


window.openModal = (id) => { document.getElementById(id).hidden = false; };
window.closeModal = (id) => { document.getElementById(id).hidden = true; };

let textModalCallback = null;
window.openTextModal = (title, label, cb) => {
    document.getElementById('textModalTitle').textContent = title;
    document.getElementById('textModalLabel').textContent = label;
    document.getElementById('textModalInput').value = '';
    textModalCallback = cb;
    openModal('textModal');
};

function confirmTextModal() {
    const val = document.getElementById('textModalInput').value.trim();
    if(val && textModalCallback) textModalCallback(val); 
    closeModal('textModal');
}

window.showToast = (msg) => { const t=document.getElementById('toast'); t.textContent=msg; t.hidden=false; setTimeout(()=>t.hidden=true, 3000); };
function escapeHtml(t){ if(!t)return t; return t.replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;").replace(/'/g,"&#039;"); }

function setupEventListeners() {
    const bSelect = document.getElementById('buildingSelect');
    if(bSelect) bSelect.onchange = (e) => selectProject(e.target.value);
    
    const btnTeam = document.getElementById('btnAddTeam');
    if(btnTeam) btnTeam.onclick = () => addTeam();
    
    const btnBuilding = document.getElementById('btnAddBuilding');
    if(btnBuilding) {
        btnBuilding.onclick = () => {
            openTextModal('Dodaj budowę', 'Nazwa nowej budowy', (name) => addProject(name));
        };
    }
    
    const btnDeleteB = document.getElementById('btnDeleteBuilding');
    if(btnDeleteB) btnDeleteB.onclick = () => deleteCurrentProject();

    const btnEditB = document.getElementById('btnEditBuilding');
    if(btnEditB) btnEditB.onclick = () => editProjectName();

    const btnResetP = document.getElementById('btnResetProject');
    if(btnResetP) btnResetP.onclick = () => resetProject();
    
    const btnWorker = document.getElementById('btnAddWorker');
    if(btnWorker) btnWorker.onclick = () => openModal('workerModal');

    const btnModalOk = document.getElementById('textModalOk');
    if(btnModalOk) btnModalOk.onclick = () => confirmTextModal();
    
    const wSearch = document.getElementById('workerSearch');
    if(wSearch) wSearch.addEventListener('input', filterWorkers);

    const wmInput = document.getElementById('wmName');
    if(wmInput) wmInput.onkeypress = (e) => { if(e.key === 'Enter') saveWorker(); };

    const tInput = document.getElementById('taskInput');
    if(tInput) tInput.onkeypress = (e) => { if(e.key === 'Enter') addTaskFromInput(); };
}