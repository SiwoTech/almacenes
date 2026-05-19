let _costeoRows = [];
let _tiposCosteo = [];

document.addEventListener('DOMContentLoaded', iniciarEstructura);

async function iniciarEstructura() {
    const [lista, cats] = await Promise.all([
        cwoFetch('/almacenes/api/costeo/estructura.php?action=listar'),
        cwoFetch('/almacenes/api/costeo/estructura.php?action=catalogos')
    ]);
    if (!lista.ok || !cats.ok) return;

    _costeoRows = lista.data || [];
    _tiposCosteo = cats.data.tipos || [];
    document.getElementById('costeo-tipo').innerHTML = ['<option value="">Selecciona</option>', ..._tiposCosteo.map(t => `<option value="${t.id}">${t.nombre}</option>`)].join('');

    document.getElementById('tbody-costeo-estructura').innerHTML = !_costeoRows.length
        ? '<tr><td colspan="8">Sin productos</td></tr>'
        : _costeoRows.map(r => `<tr><td>${r.codigo}</td><td>${r.producto}</td><td>${r.tipo_costeo || '-'}</td><td>${Number(r.costo_mp || 0).toFixed(2)}</td><td>${Number(r.costo_mo || 0).toFixed(2)}</td><td>${Number(r.costo_gi || 0).toFixed(2)}</td><td>${Number(r.costo_total || 0).toFixed(2)}</td><td><button class="btn-secondary" onclick='abrirModalCosto(${JSON.stringify(r).replace(/"/g, '&quot;')})'>Editar</button></td></tr>`).join('');

    ['costeo-mp', 'costeo-mo', 'costeo-gi'].forEach(id => document.getElementById(id).addEventListener('input', recalcularCostoTotal));
}

function abrirModalCosto(row) {
    document.getElementById('costeo-producto-id').value = row.producto_id;
    document.getElementById('costeo-producto').value = `${row.codigo} · ${row.producto}`;
    document.getElementById('costeo-tipo').value = row.tipo_costeo_id || '';
    document.getElementById('costeo-mp').value = Number(row.costo_mp || 0);
    document.getElementById('costeo-mo').value = Number(row.costo_mo || 0);
    document.getElementById('costeo-gi').value = Number(row.costo_gi || 0);
    document.getElementById('costeo-notas').value = row.notas || '';
    recalcularCostoTotal();
    document.getElementById('modal-costeo').style.display = 'flex';
}

function cerrarModalCosto() {
    document.getElementById('modal-costeo').style.display = 'none';
}

function recalcularCostoTotal() {
    const mp = Number(document.getElementById('costeo-mp').value || 0);
    const mo = Number(document.getElementById('costeo-mo').value || 0);
    const gi = Number(document.getElementById('costeo-gi').value || 0);
    document.getElementById('costeo-total').value = (mp + mo + gi).toFixed(4);
}

async function guardarCosto(e) {
    e.preventDefault();
    const body = new FormData(document.getElementById('form-costeo'));
    const res = await cwoFetch('/almacenes/api/costeo/estructura.php?action=guardar', { method: 'POST', body });
    if (!res.ok) return alert(res.message || 'No se pudo guardar');
    cerrarModalCosto();
    iniciarEstructura();
}

document.addEventListener('click', e => {
    if (e.target.id === 'modal-costeo') cerrarModalCosto();
});
