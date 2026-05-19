document.addEventListener('DOMContentLoaded', cargarTcliente);

function cargarTcliente() {
    cwoFetch('/almacenes/api/catalogos/tcliente.php?action=listar')
        .then(res => {
            if (!res.ok) { cwoError('tabla-tcliente', res.message); return; }
            renderTablaTcliente(res.data);
        })
        .catch(() => cwoError('tabla-tcliente', 'Error de conexión'));
}

function renderTablaTcliente(rows) {
    const el = document.getElementById('tabla-tcliente');

    if (!rows || !rows.length) {
        el.innerHTML = `<div class="alert alert-warning">
            Sin tipos de cliente registrados. Agrega el primero. ➕</div>`;
        return;
    }

    const filas = rows.map(r => `
        <tr class="${r.activo == 1 ? '' : 'row-inactivo'}">
            <td>${r.id}</td>
            <td>
                <strong>${r.nombre}</strong>
                ${r.descripcion
                    ? `<br><small style="color:#999">${r.descripcion}</small>`
                    : ''}
            </td>
            <td>
                <span class="badge ${r.activo == 1 ? 'badge-ok' : 'badge-sin'}">
                    ${r.activo == 1 ? 'Activo' : 'Inactivo'}
                </span>
            </td>
            <td class="td-acciones">
                <button class="btn-icon" title="Editar"
                        onclick="editarTcliente(${r.id},'${esc(r.nombre)}','${esc(r.descripcion ?? '')}')">
                    ✏️
                </button>
                <button class="btn-icon" title="${r.activo == 1 ? 'Desactivar' : 'Activar'}"
                        onclick="toggleTcliente(${r.id})">
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
function abrirModalTcliente() {
    document.getElementById('modal-tcliente-titulo').textContent = 'Nuevo tipo de cliente';
    document.getElementById('cl-id').value          = '0';
    document.getElementById('cl-nombre').value      = '';
    document.getElementById('cl-descripcion').value = '';
    document.getElementById('modal-tcliente').style.display = 'flex';
    document.getElementById('cl-nombre').focus();
}

function editarTcliente(id, nombre, descripcion) {
    document.getElementById('modal-tcliente-titulo').textContent = 'Editar tipo de cliente';
    document.getElementById('cl-id').value          = id;
    document.getElementById('cl-nombre').value      = nombre;
    document.getElementById('cl-descripcion').value = descripcion;
    document.getElementById('modal-tcliente').style.display = 'flex';
    document.getElementById('cl-nombre').focus();
}

function cerrarModalTcliente() {
    document.getElementById('modal-tcliente').style.display = 'none';
}

document.addEventListener('click', e => {
    if (e.target.id === 'modal-tcliente') cerrarModalTcliente();
});

// ── Guardar ────────────────────────────────────────────────
function guardarTcliente(e) {
    e.preventDefault();
    const body = new FormData();
    body.append('id',          document.getElementById('cl-id').value);
    body.append('nombre',      document.getElementById('cl-nombre').value.trim());
    body.append('descripcion', document.getElementById('cl-descripcion').value.trim());

    cwoFetch('/almacenes/api/catalogos/tcliente.php?action=guardar', {
        method: 'POST', body
    }).then(res => {
        if (!res.ok) { alert('❌ ' + res.message); return; }
        cerrarModalTcliente();
        cargarTcliente();
    }).catch(() => alert('❌ Error de conexión'));
}

// ── Toggle activo ──────────────────────────────────────────
function toggleTcliente(id) {
    if (!confirm('¿Cambiar estatus de este tipo de cliente?')) return;
    const body = new FormData();
    body.append('id', id);

    cwoFetch('/almacenes/api/catalogos/tcliente.php?action=toggle', {
        method: 'POST', body
    }).then(res => {
        if (!res.ok) { alert('❌ ' + res.message); return; }
        cargarTcliente();
    }).catch(() => alert('❌ Error de conexión'));
}

// ── Helper ─────────────────────────────────────────────────
function esc(str) {
    return String(str).replace(/'/g, "\\'");
}