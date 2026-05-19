let _salidas = [];

document.addEventListener('DOMContentLoaded', iniciarSalidas);

async function iniciarSalidas() {
    await Promise.all([cargarAlmSal(), cargarProdSal(), cargarTiposCliente()]);
    listarSalidas();
}

async function cargarAlmSal() {
    const res = await cwoFetch('/almacenes/api/inventario/stock.php?action=almacenes');
    const html = ['<option value="">Todos</option>', ...res.data.map(a => `<option value="${a.clave}">${a.clave}</option>`)].join('');
    document.getElementById('sal-almacen').innerHTML = html.replace('Todos', 'Selecciona');
    document.getElementById('f-sal-almacen').innerHTML = html;
}

async function cargarProdSal() {
    const res = await cwoFetch('/almacenes/api/catalogos/productos.php?action=listar&activo=1');
    const html = ['<option value="">Todos</option>', ...res.data.map(p => `<option value="${p.id}">${p.codigo} · ${p.nombre}</option>`)].join('');
    document.getElementById('sal-producto').innerHTML = html.replace('Todos', 'Selecciona');
    document.getElementById('f-sal-producto').innerHTML = html;
}

async function cargarTiposCliente() {
    const res = await cwoFetch('/almacenes/api/catalogos/tcliente.php?action=listar');
    document.getElementById('sal-tcliente').innerHTML = ['<option value="">N/A</option>', ...res.data.map(c => `<option value="${c.id}">${c.nombre}</option>`)].join('');
}

async function registrarSalida(e) {
    e.preventDefault();
    const body = new FormData(document.getElementById('form-salidas'));
    const res = await cwoFetch('/almacenes/api/movimientos/salidas.php?action=registrar', { method: 'POST', body });
    if (!res.ok) return alert(res.message || 'No se pudo registrar');
    document.getElementById('form-salidas').reset();
    document.querySelector('#form-salidas input[name="fecha"]').value = new Date().toISOString().slice(0, 10);
    listarSalidas();
}

function filtrarSalidas() { listarSalidas(); }

async function listarSalidas() {
    const q = new URLSearchParams({
        fecha_ini: document.getElementById('f-sal-fecha-ini').value,
        fecha_fin: document.getElementById('f-sal-fecha-fin').value,
        almacen: document.getElementById('f-sal-almacen').value,
        producto_id: document.getElementById('f-sal-producto').value,
    });
    const res = await cwoFetch(`/almacenes/api/movimientos/salidas.php?action=listar&${q.toString()}`);
    if (!res.ok) return;
    _salidas = res.data || [];
    document.getElementById('tbody-salidas').innerHTML = !_salidas.length
        ? '<tr><td colspan="8">Sin registros</td></tr>'
        : _salidas.map(r => `<tr><td>${r.fecha || ''}</td><td>${r.id}</td><td>${r.almacen_clave || ''}</td><td>${r.codigo || ''} · ${r.producto || ''}</td><td>${Number(r.cantidad || 0).toFixed(2)}</td><td>${Number(r.precio_venta || 0).toFixed(2)}</td><td>${r.referencia || ''}</td><td>${r.usuario || ''}</td></tr>`).join('');
}

function exportarSalidas() {
    exportarCsvSal('salidas', _salidas, [['fecha','Fecha'],['id','Folio'],['almacen_clave','Almacén'],['producto','Producto'],['cantidad','Cantidad'],['precio_venta','Precio'],['referencia','Referencia'],['usuario','Usuario']]);
}

function exportarCsvSal(nombre, rows, cols) {
    const csv = [cols.map(c => c[1]).join(',')].concat(rows.map(r => cols.map(c => `"${String(r[c[0]] ?? '').replaceAll('"', '""')}"`).join(','))).join('\n');
    const a = document.createElement('a');
    a.href = URL.createObjectURL(new Blob([csv], { type: 'text/csv;charset=utf-8' }));
    a.download = `${nombre}_${new Date().toISOString().slice(0,10)}.csv`;
    a.click();
}
