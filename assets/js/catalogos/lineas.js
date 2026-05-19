document.addEventListener('DOMContentLoaded', cargarLineas);

function cargarLineas() {
    cwoFetch('/almacenes/api/catalogos/lineas.php?action=listar')
        .then(res => {
            if (!res.ok) { cwoError('tabla-lineas', res.message); return; }
            renderTablaLineas(res.data);
        })
        .catch(() => cwoError('tabla-lineas', 'Error de conexión'));
}

function renderTablaLineas(rows) {
    const el = document.getElementById('tabla-lineas');

    if (!rows || !rows.length) {
        el.innerHTML = `<div class="alert alert-warning">
            Sin líneas registradas. Agrega la primera. ➕</div>`;
        return;
    }

    const filas = rows.map(r => `
        <tr class="${r.activo == 1 ? '' : 'row-inactivo'}">
            <td>${r.id}</td>
            <td><strong>${r.nombre}</strong></td>
            <td>
                <span class="badge ${r.activo == 1 ? 'badge-ok' : 'badge-sin'}">
                    ${r.activo == 1 ? 'Activa' : 'Inactiva'}
                </span>
            </td>
            <td class="td-acciones">
                <button class="btn-icon" title="Editar"
                        onclick="editarLinea(${r.id},'${esc(r.nombre)}')">
                    ✏️
                </button>
                <button class="btn-icon" title="${r.activo == 1 ? 'Desactivar' : 'Activar'}"
                        onclick="toggleLinea(${r.id})">
                    ${r.activo == 1 ? '🔴' : '🟢'}
                </button>
            </td>
        </tr>`).join('');

    el.innerHTML = `
        <table class="dashboard-table">
            <thead><tr>
                <th>#</th>
                <th>Nombre</th>
                <th>Estatus</th>
                <th>Acciones</th>
            </tr></thead>
            <tbody>${filas}</tbody>
        </table>`;
}

// ── Modal ──────────────────────────────────────────────────
function abrirModalLinea() {
    document.getElementById('modal-linea-titulo').textContent = 'Nueva línea';
    document.getElementById('l-id').value     = '0';
    document.getElementById('l-nombre').value = '';
    document.getElementById('modal-linea').style.display = 'flex';
    document.getElementById('l-nombre').focus();
}

function editarLinea(id, nombre) {
    document.getElementById('modal-linea-titulo').textContent = 'Editar línea';
    document.getElementById('l-id').value     = id;
    document.getElementById('l-nombre').value = nombre;
    document.getElementById('modal-linea').style.display = 'flex';
    document.getElementById('l-nombre').focus();
}

function cerrarModalLinea() {
    document.getElementById('modal-linea').style.display = 'none';
}

document.addEventListener('click', e => {
    if (e.target.id === 'modal-linea') cerrarModalLinea();
});

// ── Guardar ────────────────────────────────────────────────
function guardarLinea(e) {
    e.preventDefault();
    const body = new FormData();
    body.append('id',     document.getElementById('l-id').value);
    body.append('nombre', document.getElementById('l-nombre').value.trim());

    cwoFetch('/almacenes/api/catalogos/lineas.php?action=guardar', {
        method: 'POST', body
    }).then(res => {
        if (!res.ok) { alert('❌ ' + res.message); return; }
        cerrarModalLinea();
        cargarLineas();
    }).catch(() => alert('❌ Error de conexión'));
}

// ── Toggle activo ──────────────────────────────────────────
function toggleLinea(id) {
    if (!confirm('¿Cambiar estatus de esta línea?')) return;
    const body = new FormData();
    body.append('id', id);

    cwoFetch('/almacenes/api/catalogos/lineas.php?action=toggle', {
        method: 'POST', body
    }).then(res => {
        if (!res.ok) { alert('❌ ' + res.message); return; }
        cargarLineas();
    }).catch(() => alert('❌ Error de conexión'));
}

// ── Helper ─────────────────────────────────────────────────
function esc(str) {
    return String(str).replace(/'/g, "\\'");
}