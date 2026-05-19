let _transferencias = [];

document.addEventListener('DOMContentLoaded', iniciarTransferencias);

async function iniciarTransferencias() {
    const [alm, prod] = await Promise.all([
        cwoFetch('/almacenes/api/inventario/stock.php?action=almacenes'),
        cwoFetch('/almacenes/api/catalogos/productos.php?action=listar&activo=1')
    ]);

    const almOpts = ['<option value="">Todos</option>', ...alm.data.map(a => `<option value="${a.clave}">${a.clave}</option>`)].join('');
    ['tr-origen', 'tr-destino'].forEach(id => document.getElementById(id).innerHTML = almOpts.replace('Todos', 'Selecciona'));
    ['f-tr-origen', 'f-tr-destino'].forEach(id => document.getElementById(id).innerHTML = almOpts);

    document.getElementById('tr-producto').innerHTML = ['<option value="">Selecciona</option>', ...prod.data.map(p => `<option value="${p.id}">${p.codigo} · ${p.nombre}</option>`)].join('');
    listarTransferencias();
}

async function registrarTransferencia(e) {
    e.preventDefault();
    const form = document.getElementById('form-transferencias');
    const body = new FormData(form);
    if (body.get('origen') === body.get('destino')) return alert('Origen y destino deben ser diferentes');
    const res = await cwoFetch('/almacenes/api/movimientos/transferencias.php?action=registrar', { method: 'POST', body });
    if (!res.ok) return alert(res.message || 'No se pudo registrar');
    form.reset();
    document.querySelector('#form-transferencias input[name="fecha"]').value = new Date().toISOString().slice(0, 10);
    listarTransferencias();
}

function filtrarTransferencias() { listarTransferencias(); }

async function listarTransferencias() {
    const q = new URLSearchParams({
        fecha_ini: document.getElementById('f-tr-fecha-ini').value,
        fecha_fin: document.getElementById('f-tr-fecha-fin').value,
        origen: document.getElementById('f-tr-origen').value,
        destino: document.getElementById('f-tr-destino').value,
    });
    const res = await cwoFetch(`/almacenes/api/movimientos/transferencias.php?action=listar&${q.toString()}`);
    if (!res.ok) return;
    _transferencias = res.data || [];
    document.getElementById('tbody-transferencias').innerHTML = !_transferencias.length
        ? '<tr><td colspan="7">Sin registros</td></tr>'
        : _transferencias.map(r => `<tr><td>${r.fecha || ''}</td><td>${r.almacen_origen || ''}</td><td>${r.almacen_destino || ''}</td><td>${r.codigo || ''} · ${r.producto || ''}</td><td>${Number(r.cantidad || 0).toFixed(2)}</td><td>${estadoBadge(r.estado)}</td><td>${r.usuario || ''}</td></tr>`).join('');
}

function estadoBadge(estado) {
    const e = estado || 'pendiente';
    const cls = e === 'recibido' ? 'badge-ok' : (e === 'cancelado' ? 'badge-sin' : (e === 'en_transito' ? 'badge-bajo' : 'badge'));
    return `<span class="badge ${cls}">${e}</span>`;
}

function exportarTransferencias() {
    const cols = [['fecha','Fecha'],['almacen_origen','Origen'],['almacen_destino','Destino'],['producto','Producto'],['cantidad','Cantidad'],['estado','Estado'],['usuario','Usuario']];
    const csv = [cols.map(c => c[1]).join(',')].concat(_transferencias.map(r => cols.map(c => `"${String(r[c[0]] ?? '').replaceAll('"','""')}"`).join(','))).join('\n');
    const a = document.createElement('a');
    a.href = URL.createObjectURL(new Blob([csv], { type: 'text/csv;charset=utf-8' }));
    a.download = `transferencias_${new Date().toISOString().slice(0,10)}.csv`;
    a.click();
}
