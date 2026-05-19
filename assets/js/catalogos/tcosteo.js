document.addEventListener('DOMContentLoaded', cargarTcosteo);

function cargarTcosteo() {
    cwoFetch('/almacenes/api/catalogos/tcosteo.php?action=listar')
        .then(res => {
            if (!res.ok) { cwoError('tabla-tcosteo', res.message); return; }
            renderTablaTcosteo(res.data);
        })
        .catch(() => cwoError('tabla-tcosteo', 'Error de conexión'));
}

function renderTablaTcosteo(rows) {
    const el = document.getElementById('tabla-tcosteo');

    if (!rows || !rows.length) {
        el.innerHTML = `<div class="alert alert-warning">
            Sin tipos de costeo registrados. Agrega el primero. ➕</div>`;
        return;
    }

    const filas = rows.map(r => `
        <tr>
            <td>${r.id}</td>
            <td><strong>${r.nombre}</strong></td>
            <td class="td-acciones">
                <button class="btn-icon" title="Editar"
                        onclick="editarTcosteo(${r.id},'${esc(r.nombre)}')">
                    ✏️
                </button>
                <button class="btn-icon" title="Eliminar"
                        onclick="eliminarTcosteo(${r.id})">
                    🗑️
                </button>
            </td>
        </tr>`).join('');

    el.innerHTML = `
        <table class="dashboard-table">
            <thead><tr>
                <th>#</th>
                <th>Nombre</th>
                <th>Acciones</th>
            </tr></thead>
            <tbody>${filas}</tbody>
        </table>`;
}

// ── Modal ──────────────────────────────────────────────────
function abrirModalTcosteo() {
    document.getElementById('modal-tcosteo-titulo').textContent = 'Nuevo tipo de costeo';
    document.getElementById('tc-id').value     = '0';
    document.getElementById('tc-nombre').value = '';
    document.getElementById('modal-tcosteo').style.display = 'flex';
    document.getElementById('tc-nombre').focus();
}

function editarTcosteo(id, nombre) {
    document.getElementById('modal-tcosteo-titulo').textContent = 'Editar tipo de costeo';
    document.getElementById('tc-id').value     = id;
    document.getElementById('tc-nombre').value = nombre;
    document.getElementById('modal-tcosteo').style.display = 'flex';
    document.getElementById('tc-nombre').focus();
}

function cerrarModalTcosteo() {
    document.getElementById('modal-tcosteo').style.display = 'none';
}

document.addEventListener('click', e => {
    if (e.target.id === 'modal-tcosteo') cerrarModalTcosteo();
});

// ── Guardar ────────────────────────────────────────────────
function guardarTcosteo(e) {
    e.preventDefault();
    const body = new FormData();
    body.append('id',     document.getElementById('tc-id').value);
    body.append('nombre', document.getElementById('tc-nombre').value.trim());

    cwoFetch('/almacenes/api/catalogos/tcosteo.php?action=guardar', {
        method: 'POST', body
    }).then(res => {
        if (!res.ok) { alert('❌ ' + res.message); return; }
        cerrarModalTcosteo();
        cargarTcosteo();
    }).catch(() => alert('❌ Error de conexión'));
}

// ── Eliminar ───────────────────────────────────────────────
function eliminarTcosteo(id) {
    if (!confirm('¿Eliminar este tipo de costeo?')) return;
    const body = new FormData();
    body.append('id', id);

    cwoFetch('/almacenes/api/catalogos/tcosteo.php?action=eliminar', {
        method: 'POST', body
    }).then(res => {
        if (!res.ok) { alert('❌ ' + res.message); return; }
        cargarTcosteo();
    }).catch(() => alert('❌ Error de conexión'));
}

// ── Helper ─────────────────────────────────────────────────
function esc(str) {
    return String(str).replace(/'/g, "\\'");
}