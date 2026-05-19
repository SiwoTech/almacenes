document.addEventListener('DOMContentLoaded', cargarUnidades);

function cargarUnidades() {
    cwoFetch('/almacenes/api/catalogos/unidades.php?action=listar')
        .then(res => {
            if (!res.ok) { cwoError('tabla-unidades', res.message); return; }
            renderTablaUnidades(res.data);
        })
        .catch(() => cwoError('tabla-unidades', 'Error de conexión'));
}

function renderTablaUnidades(rows) {
    const el = document.getElementById('tabla-unidades');

    if (!rows || !rows.length) {
        el.innerHTML = `<div class="alert alert-warning">
            Sin unidades registradas. Agrega la primera. ➕</div>`;
        return;
    }

    const filas = rows.map(r => `
        <tr>
            <td>${r.id}</td>
            <td><strong>${r.nombre}</strong></td>
            <td><code>${r.abrev}</code></td>
            <td class="td-acciones">
                <button class="btn-icon" title="Editar"
                        onclick="editarUnidad(${r.id},'${esc(r.nombre)}','${esc(r.abrev)}')">
                    ✏️
                </button>
                <button class="btn-icon" title="Eliminar"
                        onclick="eliminarUnidad(${r.id})">
                    🗑️
                </button>
            </td>
        </tr>`).join('');

    el.innerHTML = `
        <table class="dashboard-table">
            <thead><tr>
                <th>#</th>
                <th>Nombre</th>
                <th>Abreviatura</th>
                <th>Acciones</th>
            </tr></thead>
            <tbody>${filas}</tbody>
        </table>`;
}

// ── Modal ──────────────────────────────────────────────────
function abrirModal() {
    document.getElementById('modal-titulo').textContent = 'Nueva unidad de medida';
    document.getElementById('u-id').value     = '0';
    document.getElementById('u-nombre').value = '';
    document.getElementById('u-abrev').value  = '';
    document.getElementById('modal-unidad').style.display = 'flex';
    document.getElementById('u-nombre').focus();
}

function editarUnidad(id, nombre, abrev) {
    document.getElementById('modal-titulo').textContent = 'Editar unidad de medida';
    document.getElementById('u-id').value     = id;
    document.getElementById('u-nombre').value = nombre;
    document.getElementById('u-abrev').value  = abrev;
    document.getElementById('modal-unidad').style.display = 'flex';
    document.getElementById('u-nombre').focus();
}

function cerrarModal() {
    document.getElementById('modal-unidad').style.display = 'none';
}

document.addEventListener('click', e => {
    if (e.target.id === 'modal-unidad') cerrarModal();
});

// ── Guardar ────────────────────────────────────────────────
function guardarUnidad(e) {
    e.preventDefault();
    const body = new FormData();
    body.append('id',     document.getElementById('u-id').value);
    body.append('nombre', document.getElementById('u-nombre').value.trim());
    body.append('abrev',  document.getElementById('u-abrev').value.trim());

    cwoFetch('/almacenes/api/catalogos/unidades.php?action=guardar', {
        method: 'POST', body
    }).then(res => {
        if (!res.ok) { alert('❌ ' + res.message); return; }
        cerrarModal();
        cargarUnidades();
    }).catch(() => alert('❌ Error de conexión'));
}

// ── Eliminar ───────────────────────────────────────────────
function eliminarUnidad(id) {
    if (!confirm('¿Eliminar esta unidad de medida?')) return;
    const body = new FormData();
    body.append('id', id);

    cwoFetch('/almacenes/api/catalogos/unidades.php?action=eliminar', {
        method: 'POST', body
    }).then(res => {
        if (!res.ok) { alert('❌ ' + res.message); return; }
        cargarUnidades();
    }).catch(() => alert('❌ Error de conexión'));
}

// ── Helper ─────────────────────────────────────────────────
function esc(str) {
    return String(str).replace(/'/g, "\\'");
}