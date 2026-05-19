let _preciosRows = [];

document.addEventListener('DOMContentLoaded', iniciarPrecios);

async function iniciarPrecios() {
    const cat = await cwoFetch('/almacenes/api/precios/lista.php?action=catalogos');
    document.getElementById('pre-tipo').innerHTML = ['<option value="">Todos</option>', ...(cat.data.tipos || []).map(t => `<option value="${t.tipo}">${t.tipo}</option>`)].join('');
    document.getElementById('pre-cliente').innerHTML = ['<option value="">Todos</option>', ...(cat.data.clientes || []).map(c => `<option value="${c.id}">${c.nombre}</option>`)].join('');
    listarPrecios();
}

function filtrarPrecios() { listarPrecios(); }

async function listarPrecios() {
    const q = new URLSearchParams({
        buscar: document.getElementById('pre-buscar').value,
        tipo: document.getElementById('pre-tipo').value,
        tipo_cliente_id: document.getElementById('pre-cliente').value,
    });
    const res = await cwoFetch(`/almacenes/api/precios/lista.php?action=listar&${q.toString()}`);
    if (!res.ok) return;
    _preciosRows = res.data || [];
    renderTablaPrecios(_preciosRows);
}

function renderTablaPrecios(rows) {
    const wrap = document.getElementById('tabla-precios');
    if (!rows.length) {
        wrap.innerHTML = '<div class="alert alert-info">Sin datos de precios</div>';
        return;
    }

    const clientes = [...new Set(rows.map(r => r.tipo_cliente).filter(Boolean))];
    const mapa = {};
    rows.forEach(r => {
        const key = r.producto_id;
        if (!mapa[key]) {
            mapa[key] = { codigo: r.codigo, producto: r.producto, tipo: r.tipo, unidad: r.unidad, precios: {} };
        }
        if (r.tipo_cliente) mapa[key].precios[r.tipo_cliente] = `${r.moneda || ''} ${Number(r.precio || 0).toFixed(2)}`;
    });

    const head = ['Código', 'Producto', 'Tipo', 'Unidad', ...clientes].map(c => `<th>${c}</th>`).join('');
    const body = Object.values(mapa).map(r => `<tr><td>${r.codigo}</td><td>${r.producto}</td><td>${r.tipo || ''}</td><td>${r.unidad || ''}</td>${clientes.map(c => `<td>${r.precios[c] || '-'}</td>`).join('')}</tr>`).join('');

    wrap.innerHTML = `<table class="tabla-base"><thead><tr>${head}</tr></thead><tbody>${body}</tbody></table>`;
}

function exportarPrecios() {
    const cols = [['codigo', 'Código'], ['producto', 'Producto'], ['tipo', 'Tipo'], ['unidad', 'Unidad'], ['tipo_cliente', 'Tipo cliente'], ['moneda', 'Moneda'], ['precio', 'Precio']];
    const csv = [cols.map(c => c[1]).join(',')].concat(_preciosRows.map(r => cols.map(c => `"${String(r[c[0]] ?? '').replaceAll('"', '""')}"`).join(','))).join('\n');
    const a = document.createElement('a');
    a.href = URL.createObjectURL(new Blob([csv], { type: 'text/csv;charset=utf-8' }));
    a.download = `precios_${new Date().toISOString().slice(0,10)}.csv`;
    a.click();
}
