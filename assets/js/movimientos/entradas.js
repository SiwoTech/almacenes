let _entradas = [];

document.addEventListener('DOMContentLoaded', iniciarEntradas);

async function iniciarEntradas() {
    await Promise.all([cargarAlmacenes('ent-almacen', 'f-ent-almacen'), cargarProductos('ent-producto', 'f-ent-producto')]);
    listarEntradas();
}

async function cargarAlmacenes(...ids) {
    const res = await cwoFetch('/almacenes/api/inventario/stock.php?action=almacenes');
    const opts = ['<option value="">Todos</option>', ...res.data.map(a => `<option value="${a.clave}">${a.clave}</option>`)];
    ids.forEach((id, idx) => {
        const el = document.getElementById(id);
        if (el) el.innerHTML = idx === 0 ? opts.join('').replace('Todos', 'Selecciona') : opts.join('');
    });
}

async function cargarProductos(...ids) {
    const res = await cwoFetch('/almacenes/api/catalogos/productos.php?action=listar&activo=1');
    const opts = ['<option value="">Todos</option>', ...res.data.map(p => `<option value="${p.id}">${p.codigo} · ${p.nombre}</option>`)];
    ids.forEach((id, idx) => {
        const el = document.getElementById(id);
        if (el) el.innerHTML = idx === 0 ? opts.join('').replace('Todos', 'Selecciona') : opts.join('');
    });
}

async function registrarEntrada(e) {
    e.preventDefault();
    const body = new FormData(document.getElementById('form-entradas'));
    const res = await cwoFetch('/almacenes/api/movimientos/entradas.php?action=registrar', { method: 'POST', body });
    if (!res.ok) return alert(res.message || 'No se pudo registrar');
    document.getElementById('form-entradas').reset();
    document.querySelector('#form-entradas input[name="fecha"]').value = new Date().toISOString().slice(0, 10);
    listarEntradas();
}

function filtrarEntradas() { listarEntradas(); }

async function listarEntradas() {
    const q = new URLSearchParams({
        fecha_ini: document.getElementById('f-ent-fecha-ini').value,
        fecha_fin: document.getElementById('f-ent-fecha-fin').value,
        almacen: document.getElementById('f-ent-almacen').value,
        producto_id: document.getElementById('f-ent-producto').value,
    });
    const res = await cwoFetch(`/almacenes/api/movimientos/entradas.php?action=listar&${q.toString()}`);
    if (!res.ok) return;
    _entradas = res.data || [];
    const tb = document.getElementById('tbody-entradas');
    tb.innerHTML = !_entradas.length
        ? '<tr><td colspan="8">Sin registros</td></tr>'
        : _entradas.map(r => `
            <tr>
                <td>${r.fecha || ''}</td>
                <td>${r.id}</td>
                <td>${r.almacen_clave || ''}</td>
                <td>${r.codigo || ''} · ${r.producto || ''}</td>
                <td>${Number(r.cantidad || 0).toFixed(2)}</td>
                <td>${Number(r.costo_unitario || 0).toFixed(2)}</td>
                <td>${(Number(r.cantidad || 0) * Number(r.costo_unitario || 0)).toFixed(2)}</td>
                <td>${r.usuario || ''}</td>
            </tr>
        `).join('');
}

function exportarEntradas() {
    exportarCsv('entradas', _entradas, [
        ['fecha', 'Fecha'], ['id', 'Folio'], ['almacen_clave', 'Almacén'], ['producto', 'Producto'],
        ['cantidad', 'Cantidad'], ['costo_unitario', 'Costo unitario'], ['usuario', 'Usuario'],
    ]);
}

function exportarCsv(nombre, rows, cols) {
    const lines = [cols.map(c => c[1]).join(',')];
    rows.forEach(r => lines.push(cols.map(c => `"${String(r[c[0]] ?? '').replaceAll('"', '""')}"`).join(',')));
    const blob = new Blob([lines.join('\n')], { type: 'text/csv;charset=utf-8' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = `${nombre}_${new Date().toISOString().slice(0,10)}.csv`;
    a.click();
}
