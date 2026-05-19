document.addEventListener('DOMContentLoaded', cargarTgasto);

const BASES_LABEL = {
    unidades_producidas: 'Unidades producidas',
    unidades_enviadas:   'Unidades enviadas',
    horas_maquina:       'Horas máquina',
    peso_producido:      'Peso producido (kg)',
    manual:              'Manual (%)',
};

function cargarTgasto() {
    cwoFetch('/almacenes/api/catalogos/tgasto.php?action=listar')
        .then(res => {
            if (!res.ok) { cwoError('tabla-tgasto', res.message); return; }
            renderTablaTgasto(res.data);
        })
        .catch(() => cwoError('tabla-tgasto', 'Error de conexión'));
}

function renderTablaTgasto(rows) {
    const el = document.getElementById('tabla-tgasto');

    if (!rows || !rows.length) {
        el.innerHTML = `<div class="alert alert-warning">
            Sin tipos de gasto registrados. Agrega el primero. ➕</div>`;
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
                <span class="badge badge-alto">
                    ${BASES_LABEL[r.base_prorrateo] ?? r.base_prorrateo}
                </span>
            </td>
            <td>
                <span class="badge ${r.activo == 1 ? 'badge-ok' : 'badge-sin'}">
                    ${r.activo == 1 ? 'Activo' : 'Inactivo'}
                </span>
            </td>
            <td class="td-acciones">
                <button class="btn-icon" title="Editar"
                        onclick="editarTgasto(${r.id},'${esc(r.nombre)}','${esc(r.descripcion ?? '')}','${r.base_prorrateo}')">
                    ✏️
                </button>
                <button class="btn-icon" title="${r.activo == 1 ? 'Desactivar' : 'Activar'}"
                        onclick="toggleTgasto(${r.id})">
                    ${r.activo == 1 ? '🔴' : '🟢'}
                </button>
            </td>
        </tr>`).join('');

    el.innerHTML = `
        <table class="dashboard-table">
            <thead><tr>
                <th>#</th>
                <th>Nombre</th>
                <th>Base prorrateo</th>
                <th>Estatus</th>
                <th>Acciones</th>
            </tr></thead>
            <tbody>${filas}</tbody>
        </table>`;
}

// ── Modal ──────────────────────────────────────────────────
function abrirModalTgasto() {
    document.getElementById('modal-tgasto-titulo').textContent = 'Nuevo tipo de gasto';
    document.getElementById('tg-id').value          = '0';
    document.getElementById('tg-nombre').value      = '';
    document.getElementById('tg-descripcion').value = '';
    document.getElementById('tg-base').value        = 'unidades_producidas';
    document.getElementById('modal-tgasto').style.display = 'flex';
    document.getElementById('tg-nombre').focus();
}

function editarTgasto(id, nombre, descripcion, base) {
    document.getElementById('modal-tgasto-titulo').textContent = 'Editar tipo de gasto';
    document.getElementById('tg-id').value          = id;
    document.getElementById('tg-nombre').value      = nombre;
    document.getElementById('tg-descripcion').value = descripcion;
    document.getElementById('tg-base').value        = base;
    document.getElementById('modal-tgasto').style.display = 'flex';
    document.getElementById('tg-nombre').focus();
}

function cerrarModalTgasto() {
    document.getElementById('modal-tgasto').style.display = 'none';
}

document.addEventListener('click', e => {
    if (e.target.id === 'modal-tgasto') cerrarModalTgasto();
});

// ── Guardar ────────────────────────────────────────────────
function guardarTgasto(e) {
    e.preventDefault();
    const body = new FormData();
    body.append('id',             document.getElementById('tg-id').value);
    body.append('nombre',         document.getElementById('tg-nombre').value.trim());
    body.append('descripcion',    document.getElementById('tg-descripcion').value.trim());
    body.append('base_prorrateo', document.getElementById('tg-base').value);

    cwoFetch('/almacenes/api/catalogos/tgasto.php?action=guardar', {
        method: 'POST', body
    }).then(res => {
        if (!res.ok) { alert('❌ ' + res.message); return; }
        cerrarModalTgasto();
        cargarTgasto();
    }).catch(() => alert('❌ Error de conexión'));
}

// ── Toggle activo ──────────────────────────────────────────
function toggleTgasto(id) {
    if (!confirm('¿Cambiar estatus de este tipo de gasto?')) return;
    const body = new FormData();
    body.append('id', id);

    cwoFetch('/almacenes/api/catalogos/tgasto.php?action=toggle', {
        method: 'POST', body
    }).then(res => {
        if (!res.ok) { alert('❌ ' + res.message); return; }
        cargarTgasto();
    }).catch(() => alert('❌ Error de conexión'));
}

// ── Helper ─────────────────────────────────────────────────
function esc(str) {
    return String(str).replace(/'/g, "\\'");
}