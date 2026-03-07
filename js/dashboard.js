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
        el.dataset.id = w.id;
        
        // Dodajemy treść z przyciskami (ikona ołówka i kosza)
        el.innerHTML = `
            <span>${escapeHtml(w.full_name)}</span>
            <div class="pool-item-actions" style="display:flex; gap:5px;">
                <button class="iconBtn" onclick="event.stopPropagation(); editWorkerName(${w.id}, '${escapeHtml(w.full_name)}')" title="Edytuj">✎</button>
                <button class="iconBtn" style="color:var(--danger);" onclick="event.stopPropagation(); deleteWorker(${w.id})" title="Usuń trwale">🗑</button>
            </div>
        `;
        
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
    const container = document.getElementById('tasksPool');
    if (!container) return;
    container.innerHTML = '';

    // Filtrowanie - tylko nieprzypisane zadania
    const unassigned = allTasks.filter(t => !t.assigned_to_team_id);
    
    if (unassigned.length === 0) {
        container.innerHTML = '<div style="color:#64748b; text-align:center; grid-column: 1/-1; margin-top:20px;">Brak wolnych zadań w puli.</div>';
        return;
    }

    // Generowanie siatki (grid) z zadaniami
    unassigned.forEach(t => {
        const el = document.createElement('div');
        el.className = 'pool-item';
        el.draggable = true;
        el.dataset.id = t.id;
        el.dataset.type = 'task';
        
        // Struktura z przyciskami akcji
        el.innerHTML = `
            <span style="flex:1; margin-right:15px; line-height:1.4;">${escapeHtml(t.title)}</span>
            <div class="task-actions" style="display:flex; gap:8px; opacity:0; transition:opacity 0.2s;">
                <button class="iconBtn" onclick="event.stopPropagation(); editTask(${t.id}, '${escapeHtml(t.title)}')" title="Edytuj treść">✎</button>
                <button class="iconBtn" style="color:var(--danger);" onclick="event.stopPropagation(); deleteTask(${t.id})" title="Usuń trwale">🗑</button>
            </div>
        `;
        
        // Pokazywanie przycisków na hover
        el.addEventListener('mouseenter', () => el.querySelector('.task-actions').style.opacity = '1');
        el.addEventListener('mouseleave', () => el.querySelector('.task-actions').style.opacity = '0');
        
        el.addEventListener('dragstart', (e) => {
            draggedItem = { id: parseInt(t.id), type: 'task' };
            e.dataTransfer.setData('text/plain', JSON.stringify(draggedItem));
            setTimeout(() => el.style.opacity = '0.5', 0);
        });
        el.addEventListener('dragend', () => { el.style.opacity = '1'; draggedItem = null; });
        
        container.appendChild(el);
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

// ZADANIA - TASK	
	const activeTab = document.querySelector('.tabBtn.active');
    if (activeTab && activeTab.dataset.view === 'tasks') {
        renderTasksListFull();
    }
// KONIEC ZADANIA	
	
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

    // WALIDACJA DŁUGOŚCI
    if (n.length > 100) {
        alert("⚠️ Imię i nazwisko są zbyt długie!\n\nMaksymalna długość to 100 znaków.\nAktualnie wpisano: " + n.length + " znaków.\n\nSkróć tekst i spróbuj ponownie.");
        return;
    }
	
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

/* O L D 
	async function addTaskFromInput() {
    const v = document.getElementById('taskInput').value.trim();
    if(!v || !currentProjectId) return alert("Wybierz budowę!");
    const res = await fetch('api/tasks.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({title:v, project_id:currentProjectId}) });
    if((await res.json()).success) { document.getElementById('taskInput').value=''; loadData(); }
} */

/* N E W */
async function addTaskFromInput() {
    const input = document.getElementById('taskInput');
    const title = input.value.trim(); 
    if(!title || !currentProjectId) return alert("Wybierz budowę i wpisz treść!");

    // WALIDACJA DŁUGOŚCI
    if (title.length > 255) {
        alert("⚠️ Treść zadania jest zbyt długa!\n\nMaksymalna długość to 255 znaków.\nAktualnie wpisano: " + title.length + " znaków.\n\nSkróć opis lub podziel zadanie na mniejsze części.");
        return;
    }

    // 2. Sprawdź czy wybrano budowę (Zadanie musi należeć do jakiegoś projektu)
    if (!currentProjectId) {
        alert("Najpierw wybierz budowę z listy, aby móc dodać do niej zadanie!");
        return;
    }

    console.log("Dodawanie zadania:", title, "do projektu ID:", currentProjectId);

    try {
        const res = await fetch('api/tasks.php', { 
            method: 'POST', 
            headers: { 'Content-Type': 'application/json' }, 
            body: JSON.stringify({ 
                title: title, 
                project_id: currentProjectId 
            }) 
        });

        const r = await res.json();

        if (r.success) {
            input.value = ''; // Wyczyść pole tekstowe
            showToast('Dodano zadanie do puli');
            await loadData(); // Odśwież listy, aby zadanie się pojawiło
        } else {
            alert('Błąd serwera: ' + (r.message || 'Nie udało się zapisać zadania.'));
        }
    } catch (e) {
        console.error("Błąd sieci przy dodawaniu zadania:", e);
        alert("Błąd połączenia z serwerem.");
    }
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

/*
async function addProject(n) {
    const res = await fetch('api/projects.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({name:n}) });
    if((await res.json()).success) loadData();
}
*/

async function addProject(n) {
    if (!n) return;
    
    // WALIDACJA: Nazwa budowy max 100 znaków
    if (n.length > 100) {
        alert("⚠️ Nazwa budowy jest zbyt długa!\n\nMaksymalna długość to 100 znaków.\nAktualnie wpisano: " + n.length + " znaków.\n\nUżyj krótszej nazwy.");
        return;
    }

    const res = await fetch('api/projects.php', { 
        method:'POST', 
        headers:{'Content-Type':'application/json'}, 
        body:JSON.stringify({name:n}) 
    });
    
    if((await res.json()).success) {
        loadData();
        showToast('Dodano budowę');
    }
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
	
	const btnAddUser = document.getElementById('btnAddUser');
	if (btnAddUser) btnAddUser.onclick = () => openModal('userModal');

	const btnSaveUser = document.getElementById('btnSaveUser');
	if (btnSaveUser) btnSaveUser.onclick = () => saveUser();
	
	// Obsługa kliknięcia przycisku "Dodaj" przy zadaniach
const btnAddTask = document.getElementById('btnAddTask');
if (btnAddTask) {
    btnAddTask.onclick = () => addTaskFromInput();
}

// Obsługa klawisza Enter w polu zadania
const taskInput = document.getElementById('taskInput');
if (taskInput) {
    taskInput.onkeypress = (e) => { 
        if (e.key === 'Enter') addTaskFromInput(); 
    };
}
    document.getElementById('btnFontUp').onclick = () => changeFontSize(1);
    document.getElementById('btnFontDown').onclick = () => changeFontSize(-1);
    
    // Inicjalizacja fontu przy starcie
    applyFontSize();
	
// Edycja zadania okno modal
const btnSaveTaskEdit = document.getElementById('btnSaveTaskEdit');
if(btnSaveTaskEdit) btnSaveTaskEdit.onclick = () => saveTaskEdit();

// Dodatkowo - zamykanie modala po kliknięciu X lub "Anuluj" (jeśli nie masz globalnej obsługi)
document.querySelectorAll('#editTaskModal .modalClose').forEach(btn => {
    btn.onclick = () => {
        closeModal('editTaskModal');
        currentEditingTaskId = null;
    };
});

	
}

window.switchView = (v) => {
    document.querySelectorAll('.view').forEach(x => x.classList.remove('active'));
    document.getElementById('view' + v.charAt(0).toUpperCase() + v.slice(1)).classList.add('active');
    document.querySelectorAll('.tabBtn').forEach(b => b.classList.toggle('active', b.dataset.view === v));
	if (v === 'users') renderUsersList();

    if(v === 'people') renderPeopleList();

    if (v === 'tasks') renderTasksListFull(); // To wywołanie dla TASK
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

let peopleSortCol = 'name'; 
let peopleSortDir = 1;      

async function renderPeopleList() {
    const list = document.getElementById('peopleViewList');
    if (!list) return;

    list.innerHTML = '<div style="text-align:center; padding:50px;">Ładowanie listy...</div>';

    try {
        const response = await fetch('api/team-members.php?action=get_all_assignments');
        const assignments = await response.json();

        // Przygotowanie danych
        let displayData = allWorkers.map(w => {
            const a = assignments.find(assign => parseInt(assign.employee_id) === parseInt(w.id));
            return {
                id: w.id,
                name: w.full_name,
                location: a ? `${a.project_name} / ${a.team_name}` : "--- WOLNY ---",
                isAssigned: !!a
            };
        });

        // Logika sortowania
        displayData.sort((a, b) => {
            let valA = String(a[peopleSortCol]).toLowerCase();
            let valB = String(b[peopleSortCol]).toLowerCase();
            return valA.localeCompare(valB, 'pl') * peopleSortDir;
        });

        // Generowanie HTML
        let html = `
            <div class="people-table-container">
                <table class="people-table">
                    <thead>
                        <tr>
                            <th onclick="sortPeople('name')">
                                Pracownik 
                                <span class="sort-icon">${peopleSortCol === 'name' ? (peopleSortDir === 1 ? '▲' : '▼') : '↕'}</span>
                            </th>
                            <th onclick="sortPeople('location')">
                                Lokalizacja (Budowa / Zespół) 
                                <span class="sort-icon">${peopleSortCol === 'location' ? (peopleSortDir === 1 ? '▲' : '▼') : '↕'}</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
        `;

        displayData.forEach(row => {
            html += `
                <tr>
                    <td>${escapeHtml(row.name)}</td>
                    <td class="${row.isAssigned ? 'status-assigned' : 'status-free'}">
                        ${escapeHtml(row.location)}
                    </td>
                </tr>
            `;
        });

        html += `</tbody></table></div>`;
        list.innerHTML = html;

    } catch (e) {
        console.error("Błąd listy ludzi:", e);
        list.innerHTML = '<div style="color:red; text-align:center; padding:50px;">Błąd pobierania danych.</div>';
    }
}

// Globalna funkcja sortowania
window.sortPeople = (column) => {
    if (peopleSortCol === column) {
        peopleSortDir *= -1;
    } else {
        peopleSortCol = column;
        peopleSortDir = 1;
    }
    renderPeopleList();
};

// --- ZMIANA ROZMIARU CZCIONKI ---
let currentFontSize = parseInt(localStorage.getItem('preferredFontSize')) || 14;

function applyFontSize() {
    // Zmieniamy font-size na poziomie :root (html), co wpływa na wszystkie jednostki 'rem'
    document.documentElement.style.fontSize = currentFontSize + 'px';
    localStorage.setItem('preferredFontSize', currentFontSize);
}

function changeFontSize(delta) {
    currentFontSize += delta;
    if (currentFontSize < 10) currentFontSize = 10;
    if (currentFontSize > 22) currentFontSize = 22;
    applyFontSize();
}

async function renderTasksListFull() {
    const container = document.getElementById('tasksViewList');
    if (!container) return;

    if (!currentProjectId) {
        container.innerHTML = `
            <div style="text-align:center; padding:100px; color: var(--text-main);">
                <h3 style="font-size: 1.5rem;">Nie wybrano budowy</h3>
                <p>Wybierz budowę z listy na górze, aby wyświetlić zadania zespołów.</p>
            </div>`;
        return;
    }

    container.innerHTML = '<div style="text-align:center; padding:50px;">Ładowanie danych...</div>';

    try {
        const res = await fetch(`api/teams.php?project_id=${currentProjectId}`);
        const teams = await res.json();
        
        if (teams.length === 0) {
            container.innerHTML = '<div class="no-tasks-info" style="margin-top:50px;">Brak zdefiniowanych zespołów dla tej budowy.</div>';
            return;
        }

        // Tworzymy kontener typu GRID
        let html = '<div class="tasks-view-grid">';

        teams.forEach(team => {
            html += `
                <div class="task-group-card">
                    <div class="task-group-header">
                        <span>${escapeHtml(team.team_name)}</span>
                        <span style="font-size: 0.8rem; background: var(--bg-dark); padding: 4px 10px; border-radius: 20px; opacity: 0.8;">
                            Zadań: ${team.tasks ? team.tasks.length : 0}
                        </span>
                    </div>
            `;

            if (team.tasks && team.tasks.length > 0) {
                html += '<table class="task-list-table"><tbody>';
                team.tasks.forEach(task => {
                    html += `
                        <tr>
                            <td>${escapeHtml(task.title)}</td>
                        </tr>
                    `;
                });
                html += '</tbody></table>';
            } else {
                html += '<div class="no-tasks-info">Brak przypisanych zadań</div>';
            }

            html += '</div>'; // Koniec karty zespołu
        });

        html += '</div>'; // Koniec gridu
        container.innerHTML = html;

    } catch (e) {
        console.error("Błąd ładowania widoku zadań:", e);
        container.innerHTML = '<div style="color:var(--danger); text-align:center; padding:50px;">Błąd pobierania danych.</div>';
    }
}

async function renderUsersList() {
    const container = document.getElementById('usersViewList');
    if (!container) return;
    try {
        const res = await fetch('api/users.php');
        const users = await res.json();

        let html = `
            <div class="people-table-container">
            <table class="people-table">
                <thead>
                    <tr>
                        <th>Login</th>
                        <th>Imię i Nazwisko</th>
                        <th>Rola</th>
                        <th>Utworzono</th>
                        <th>Ostatnie logowanie</th>
                        <th>Akcje</th>
                    </tr>
                </thead>
                <tbody>
        `;

        users.forEach(u => {
            const lastLogin = u.last_login ? u.last_login : '---';
            html += `
                <tr>
                    <td>${escapeHtml(u.username)}</td>
                    <td>${escapeHtml(u.full_name || '')}</td>
                    <td>${u.role}</td>
                    <td>${u.created_at}</td>
                    <td>${lastLogin}</td>
                    <td>
                        <button class="iconBtn" title="Zmień hasło" onclick="changeUserPassword(${u.id})">🔑</button>
                        <button class="iconBtn" style="color:var(--danger);" title="Usuń" onclick="deleteUser(${u.id})">🗑</button>
                    </td>
                </tr>
            `;
        });
        html += '</tbody></table></div>';
        container.innerHTML = html;
    } catch (e) { container.innerHTML = "Błąd ładowania."; }
}

async function saveUser() {
    const username = document.getElementById('uUsername').value.trim();
    const full_name = document.getElementById('uFullName').value.trim();
    const password = document.getElementById('uPassword').value;
    const role = document.getElementById('uRole').value;

    if(!username || !full_name || !password) return alert("Wszystkie pola są wymagane!");

    const res = await fetch('api/users.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ username, full_name, password, role })
    });
    const r = await res.json();
    if(r.success) {
        closeModal('userModal');
        renderUsersList();
        showToast("Użytkownik dodany");
    } else alert(r.message);
}

// NOWA FUNKCJA ZMIANY HASŁA
window.changeUserPassword = async (id) => {
    const newPass = prompt("Wpisz nowe hasło dla użytkownika (min. 4 znaki):");
    if (!newPass) return;
    if (newPass.length < 4) return alert("Hasło za krótkie!");

    const res = await fetch('api/users.php', {
        method: 'PUT',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ id, password: newPass })
    });
    const r = await res.json();
    if(r.success) showToast("Hasło zmienione pomyślnie");
    else alert(r.message);
}

async function deleteUser(id) {
    if(!confirm("Czy na pewno usunąć tego użytkownika?")) return;
    const res = await fetch('api/users.php', {
        method: 'DELETE',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ id })
    });
    const r = await res.json();
    if(r.success) renderUsersList();
    else alert(r.message);
}

async function resetUserPassword(id) {
    const newPass = prompt("Wpisz nowe hasło dla tego użytkownika (min. 4 znaki):");
    if (!newPass || newPass.length < 4) return;

    const res = await fetch('api/users.php', {
        method: 'PUT',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ id: id, password: newPass })
    });
    const r = await res.json();
    if (r.success) showToast("Hasło zostało zmienione");
    else alert(r.message);
}

async function editWorkerName(id, oldName) {
    const newName = prompt("Zmień imię i nazwisko pracownika:", oldName);
    if (!newName || newName.trim() === '' || newName.trim() === oldName) return;

    try {
        const res = await fetch('api/workers.php', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id, full_name: newName.trim() })
        });
        const r = await res.json();
        if (r.success) {
            showToast('Zaktualizowano dane pracownika');
            await loadData(); // Odśwież listy
        } else {
            alert(r.message || "Błąd podczas edycji.");
        }
    } catch (e) { console.error(e); }
}

async function deleteWorker(id) {
    if (!confirm("CZY NA PEWNO? Pracownik zostanie trwale usunięty z bazy danych!")) return;

    try {
        const res = await fetch('api/workers.php', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id })
        });
        const r = await res.json();
        if (r.success) {
            showToast('Pracownik usunięty z bazy');
            await loadData();
        } else {
            alert(r.message || "Nie można usunąć pracownika.");
        }
    } catch (e) { console.error(e); }
}

// Zmienna do przechowywania ID edytowanego zadania
let currentEditingTaskId = null;

function editTask(id, oldTitle) {
    currentEditingTaskId = id;
    const textarea = document.getElementById('editTaskText');
    textarea.value = oldTitle; // Wstawiamy obecną treść
    openModal('editTaskModal');
    textarea.focus(); // Auto-focus na pole
}

// Funkcja zapisywania zmian z modala
async function saveTaskEdit() {
    if (!currentEditingTaskId) return;
    
    const newTitle = document.getElementById('editTaskText').value.trim();
    if (!newTitle) {
        alert("Treść zadania nie może być pusta!");
        return;
    }

    try {
        const res = await fetch('api/tasks.php', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: currentEditingTaskId, title: newTitle })
        });
        const r = await res.json();
        if(r.success) {
            closeModal('editTaskModal');
            currentEditingTaskId = null;
            showToast('Zadanie zaktualizowane');
            await loadData();
        } else {
            alert(r.message || "Błąd zapisu");
        }
    } catch (e) { 
        console.error(e); 
        alert("Błąd połączenia");
    }
}

async function deleteTask(id) {
    if (!confirm("Czy na pewno chcesz trwale usunąć to zadanie?")) return;

    try {
        const res = await fetch('api/tasks.php', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id })
        });
        const r = await res.json();
        if(r.success) {
            showToast('Zadanie usunięte');
            await loadData();
        }
    } catch (e) { console.error(e); }
}


