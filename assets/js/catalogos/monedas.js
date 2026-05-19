document.addEventListener('DOMContentLoaded', cargarMonedas);

function cargarMonedas() {
    cwoFetch('/almacenes/api/catalogos/monedas.php?action=listar')
        .then(res => {
            if (!res.ok) { cwoError('tabla-monedas', res.message); return; }
            renderTablaMonedas(res.data);
        })
        .catch(() => cwoError('tabla-monedas', 'Error de conexión'));
}

function renderTablaMonedas(rows) {
    const el = document.getElementById('tabla-monedas');

    if (!rows || !rows.length) {
        el.innerHTML = `<div class="alert alert-warning">
            Sin monedas registradas. Agrega la primera. ➕</div>`;
        return;
    }

    const filas = rows.map(r => `
        <tr>
            <td>${r.id}</td>
            <td><strong>${r.nombre}</strong></td>
            <td><code>${r.simbolo}</code></td>
            <td class="num">
                <span class="badge badge-alto">
                    ${parseFloat(r.tipo_cambio).toFixed(4)}
                </span>
            </td>
            <td class="td-acciones">
                <button class="btn-icon" title="Editar"
                        onclick="editarMoneda(${r.id},'${esc(r.nombre)}','${esc(r.simbolo)}',${r.tipo_cambio})">
                    ✏️
                </button>
                <button class="btn-icon" title="Eliminar"
                        onclick="eliminarMoneda(${r.id})">
                    🗑️
                </button>
            </td>
        </tr>`).join('');

    el.innerHTML = `
        <table class="dashboard-table">
            <thead><tr>
                <th>#</th>
                <th>Nombre</th>
                <th>Símbolo</th>
                <th class="num">Tipo de cambio</th>
                <th>Acciones</th>
            </tr></thead>
            <tbody>${filas}</tbody>
        </table>`;
}

// ── Modal ──────────────────────────────────────────────────
function abrirModalMoneda() {
    document.getElementById('modal-moneda-titulo').textContent = 'Nueva moneda';
    document.getElementById('m-id').value          = '0';
    document.getElementById('m-nombre').value      = '';
    document.getElementById('m-simbolo').value     = '';
    document.getElementById('m-tipo-cambio').value = '1.0000';
    document.getElementById('modal-moneda').style.display = 'flex';
    document.getElementById('m-nombre').focus();
}

function editarMoneda(id, nombre, simbolo, tipoCambio) {
    document.getElementById('modal-moneda-titulo').textContent = 'Editar moneda';
    document.getElementById('m-id').value          = id;
    document.getElementById('m-nombre').value      = nombre;
    document.getElementById('m-simbolo').value     = simbolo;
    document.getElementById('m-tipo-cambio').value = tipoCambio;
    document.getElementById('modal-moneda').style.display = 'flex';
    document.getElementById('m-nombre').focus();
}

function cerrarModalMoneda() {
    document.getElementById('modal-moneda').style.display = 'none';
}

document.addEventListener('click', e => {
    if (e.target.id === 'modal-moneda') cerrarModalMoneda();
});

// ── Guardar ────────────────────────────────────────────────
function guardarMoneda(e) {
    e.preventDefault();
    const body = new FormData();
    body.append('id',          document.getElementById('m-id').value);
    body.append('nombre',      document.getElementById('m-nombre').value.trim());
    body.append('simbolo',     document.getElementById('m-simbolo').value.trim());
    body.append('tipo_cambio', document.getElementById('m-tipo-cambio').value);

    cwoFetch('/almacenes/api/catalogos/monedas.php?action=guardar', {
        method: 'POST', body
    }).then(res => {
        if (!res.ok) { alert('❌ ' + res.message); return; }
        cerrarModalMoneda();
        cargarMonedas();
    }).catch(() => alert('❌ Error de conexión'));
}

// ── Eliminar ───────────────────────────────────────────────
function eliminarMoneda(id) {
    if (!confirm('¿Eliminar esta moneda?')) return;
    const body = new FormData();
    body.append('id', id);

    cwoFetch('/almacenes/api/catalogos/monedas.php?action=eliminar', {
        method: 'POST', body
    }).then(res => {
        if (!res.ok) { alert('❌ ' + res.message); return; }
        cargarMonedas();
    }).catch(() => alert('❌ Error de conexión'));
}

// ── Helper ─────────────────────────────────────────────────
function esc(str) {
    return String(str).replace(/'/g, "\\'");
}