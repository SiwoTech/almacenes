document.addEventListener('DOMContentLoaded', iniciarConsumo);

async function iniciarConsumo() {
    const [alm, prod, tg] = await Promise.all([
        cwoFetch('/almacenes/api/inventario/stock.php?action=almacenes'),
        cwoFetch('/almacenes/api/catalogos/productos.php?action=listar&activo=1'),
        cwoFetch('/almacenes/api/catalogos/tgasto.php?action=listar')
    ]);

    document.getElementById('con-almacen').innerHTML = ['<option value="">Selecciona</option>', ...alm.data.map(a => `<option value="${a.clave}">${a.clave}</option>`)].join('');
    document.getElementById('con-producto').innerHTML = ['<option value="">Selecciona</option>', ...prod.data.map(p => `<option value="${p.id}">${p.codigo} · ${p.nombre}</option>`)].join('');
    document.getElementById('con-tgasto').innerHTML = ['<option value="">N/A</option>', ...tg.data.map(t => `<option value="${t.id}">${t.nombre}</option>`)].join('');

    listarConsumos();
}

async function registrarConsumo(e) {
    e.preventDefault();
    const body = new FormData(document.getElementById('form-consumo'));
    const res = await cwoFetch('/almacenes/api/movimientos/consumo.php?action=registrar', { method: 'POST', body });
    if (!res.ok) return alert(res.message || 'No se pudo registrar');
    document.getElementById('form-consumo').reset();
    document.querySelector('#form-consumo input[name="fecha"]').value = new Date().toISOString().slice(0, 10);
    listarConsumos();
}

async function listarConsumos() {
    const res = await cwoFetch('/almacenes/api/movimientos/consumo.php?action=listar');
    if (!res.ok) return;
    const rows = res.data || [];
    document.getElementById('tbody-consumo').innerHTML = !rows.length
        ? '<tr><td colspan="7">Sin registros</td></tr>'
        : rows.map(r => `<tr><td>${r.fecha || ''}</td><td>${r.almacen_clave || ''}</td><td>${r.producto || ''}</td><td>${Number(r.cantidad || 0).toFixed(2)}</td><td>${r.tipo_gasto || ''}</td><td>${r.notas || ''}</td><td>${r.usuario || ''}</td></tr>`).join('');
}
