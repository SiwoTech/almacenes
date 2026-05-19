document.addEventListener('DOMContentLoaded', cargarImpuestos);

function cargarImpuestos() {
    cwoFetch('/almacenes/api/catalogos/impuestos.php?action=listar')
        .then(res => {
            if (!res.ok) { cwoError('tabla-impuestos', res.message); return; }
            renderTablaImpuestos(res.data);
        })
        .catch(() => cwoError('tabla-impuestos', 'Error de conexión'));
}

function renderTablaImpuestos(rows) {
    const el = document.getElementById('tabla-impuestos');

    if (!rows || !rows.length) {
        el.innerHTML = `<div class="alert alert-warning">
            Sin esquemas de impuesto registrados. Agrega el primero. ➕</div>`;
        return;
    }

    const filas = rows.map(r => `
        <tr>
            <td>${r.id}</td>
            <td><strong>${r.nombre}</strong></td>
            <td class="num">
                <span class="badge badge-ok">${parseFloat(r.porcentaje).toFixed(2)} %</span>
            </td>
            <td class="td-acciones">
                <button class="btn-icon" title="Editar"
                        onclick="editarImpuesto(${r.id},'${esc(r.nombre)}',${r.porcentaje})">
                    ✏️
                </button>
                <button class="btn-icon" title="Eliminar"
                        onclick="eliminarImpuesto(${r.id})">
                    🗑️
                </button>
            </td>
        </tr>`).join('');

    el.innerHTML = `
        <table class="dashboard-table">
            <thead><tr>
                <th>#</th>
                <th>Nombre</th>
                <th class="num">Porcentaje</th>
                <th>Acciones</th>
            </tr></thead>
            <tbody>${filas}</tbody>
        </table>`;
}

// ── Modal ──────────────────────────────────────────────────
function abrirModalImpuesto() {
    document.getElementById('modal-impuesto-titulo').textContent = 'Nuevo esquema de impuesto';
    document.getElementById('i-id').value          = '0';
    document.getElementById('i-nombre').value      = '';
    document.getElementById('i-porcentaje').value  = '16';
    document.getElementById('modal-impuesto').style.display = 'flex';
    document.getElementById('i-nombre').focus();
}

function editarImpuesto(id, nombre, porcentaje) {
    document.getElementById('modal-impuesto-titulo').textContent = 'Editar esquema de impuesto';
    document.getElementById('i-id').value         = id;
    document.getElementById('i-nombre').value     = nombre;
    document.getElementById('i-porcentaje').value = porcentaje;
    document.getElementById('modal-impuesto').style.display = 'flex';
    document.getElementById('i-nombre').focus();
}

function cerrarModalImpuesto() {
    document.getElementById('modal-impuesto').style.display = 'none';
}

document.addEventListener('click', e => {
    if (e.target.id === 'modal-impuesto') cerrarModalImpuesto();
});

// ── Guardar ────────────────────────────────────────────────
function guardarImpuesto(e) {
    e.preventDefault();
    const body = new FormData();
    body.append('id',         document.getElementById('i-id').value);
    body.append('nombre',     document.getElementById('i-nombre').value.trim());
    body.append('porcentaje', document.getElementById('i-porcentaje').value);

    cwoFetch('/almacenes/api/catalogos/impuestos.php?action=guardar', {
        method: 'POST', body
    }).then(res => {
        if (!res.ok) { alert('❌ ' + res.message); return; }
        cerrarModalImpuesto();
        cargarImpuestos();
    }).catch(() => alert('❌ Error de conexión'));
}

// ── Eliminar ───────────────────────────────────────────────
function eliminarImpuesto(id) {
    if (!confirm('¿Eliminar este esquema de impuesto?')) return;
    const body = new FormData();
    body.append('id', id);

    cwoFetch('/almacenes/api/catalogos/impuestos.php?action=eliminar', {
        method: 'POST', body
    }).then(res => {
        if (!res.ok) { alert('❌ ' + res.message); return; }
        cargarImpuestos();
    }).catch(() => alert('❌ Error de conexión'));
}

// ── Helper ─────────────────────────────────────────────────
function esc(str) {
    return String(str).replace(/'/g, "\\'");
}