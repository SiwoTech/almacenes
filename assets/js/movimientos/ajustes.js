document.addEventListener('DOMContentLoaded', iniciarAjustes);

async function iniciarAjustes() {
    const [alm, prod] = await Promise.all([
        cwoFetch('/almacenes/api/inventario/stock.php?action=almacenes'),
        cwoFetch('/almacenes/api/catalogos/productos.php?action=listar&activo=1')
    ]);
    document.getElementById('aju-almacen').innerHTML = ['<option value="">Selecciona</option>', ...alm.data.map(a => `<option value="${a.clave}">${a.clave}</option>`)].join('');
    document.getElementById('aju-producto').innerHTML = ['<option value="">Selecciona</option>', ...prod.data.map(p => `<option value="${p.id}">${p.codigo} · ${p.nombre}</option>`)].join('');
    listarAjustes();
}

async function registrarAjuste(e) {
    e.preventDefault();
    const body = new FormData(document.getElementById('form-ajustes'));
    const res = await cwoFetch('/almacenes/api/movimientos/ajustes.php?action=registrar', { method: 'POST', body });
    if (!res.ok) return alert(res.message || 'No se pudo registrar');
    document.getElementById('form-ajustes').reset();
    document.querySelector('#form-ajustes input[name="fecha"]').value = new Date().toISOString().slice(0, 10);
    listarAjustes();
}

async function listarAjustes() {
    const res = await cwoFetch('/almacenes/api/movimientos/ajustes.php?action=listar');
    if (!res.ok) return;
    document.getElementById('tbody-ajustes').innerHTML = !(res.data || []).length
        ? '<tr><td colspan="7">Sin registros</td></tr>'
        : res.data.map(r => `<tr><td>${r.fecha || ''}</td><td>${r.almacen_clave || ''}</td><td>${r.codigo || ''} · ${r.producto || ''}</td><td>${r.referencia || ''}</td><td>${Number(r.cantidad || 0).toFixed(2)}</td><td>${r.motivo || ''}</td><td>${r.usuario || ''}</td></tr>`).join('');
}
