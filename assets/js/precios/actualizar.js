document.addEventListener('DOMContentLoaded', iniciarActualizar);

let _impuestos = [];

async function iniciarActualizar() {
    const res = await cwoFetch('/almacenes/api/precios/actualizar.php?action=catalogos');
    if (!res.ok) return;

    const { productos, clientes, monedas, impuestos } = res.data;
    _impuestos = impuestos || [];

    document.getElementById('act-producto').innerHTML = ['<option value="">Selecciona</option>', ...productos.map(p => `<option value="${p.id}">${p.codigo} · ${p.nombre}</option>`)].join('');
    document.getElementById('act-cliente').innerHTML = ['<option value="">Selecciona</option>', ...clientes.map(c => `<option value="${c.id}">${c.nombre}</option>`)].join('');
    document.getElementById('act-moneda').innerHTML = ['<option value="">Selecciona</option>', ...monedas.map(m => `<option value="${m.id}">${m.simbolo} ${m.nombre}</option>`)].join('');
    document.getElementById('act-impuesto').innerHTML = ['<option value="">Selecciona</option>', ...impuestos.map(i => `<option value="${i.id}">${i.nombre} (${i.porcentaje}%)</option>`)].join('');

    document.getElementById('act-impuesto').addEventListener('change', () => {
        const id = Number(document.getElementById('act-impuesto').value || 0);
        const imp = _impuestos.find(i => Number(i.id) === id);
        document.getElementById('act-impuesto-pct').value = imp ? `${imp.porcentaje}%` : '';
    });

    listarHistorial();
}

async function guardarPrecio(e) {
    e.preventDefault();
    const body = new FormData(document.getElementById('form-precio'));
    const res = await cwoFetch('/almacenes/api/precios/actualizar.php?action=guardar', { method: 'POST', body });
    if (!res.ok) return alert(res.message || 'No se pudo guardar');
    listarHistorial();
}

async function listarHistorial() {
    const res = await cwoFetch('/almacenes/api/precios/actualizar.php?action=listar');
    if (!res.ok) return;
    const rows = res.data || [];
    document.getElementById('tbody-precios-historial').innerHTML = !rows.length
        ? '<tr><td colspan="7">Sin registros</td></tr>'
        : rows.map(r => `<tr><td>${r.updated_at || ''}</td><td>${r.codigo || ''} · ${r.producto || ''}</td><td>${r.tipo_cliente || ''}</td><td>${r.moneda || ''}</td><td>${Number(r.precio || 0).toFixed(2)}</td><td>${Number(r.impuesto || 0).toFixed(2)}%</td><td>${r.actualizado_por || ''}</td></tr>`).join('');
}
