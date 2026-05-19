document.addEventListener('DOMContentLoaded', iniciarProductos);

async function iniciarProductos() {
    const q = new URLSearchParams({
        buscar: document.getElementById('cos-buscar')?.value || '',
        tipo: document.getElementById('cos-tipo')?.value || '',
        linea: document.getElementById('cos-linea')?.value || '',
    });

    const res = await cwoFetch(`/almacenes/api/costeo/productos.php?action=listar&${q.toString()}`);
    if (!res.ok) return;

    const rows = res.data || [];
    const cat = res.catalogos || {};

    if (document.getElementById('cos-tipo').options.length <= 1) {
        document.getElementById('cos-tipo').innerHTML = ['<option value="">Todos</option>', ...(cat.tipos || []).map(t => `<option value="${t.tipo}">${t.tipo}</option>`)].join('');
    }
    if (document.getElementById('cos-linea').options.length <= 1) {
        document.getElementById('cos-linea').innerHTML = ['<option value="">Todas</option>', ...(cat.lineas || []).map(l => `<option value="${l.id}">${l.nombre}</option>`)].join('');
    }

    document.getElementById('tbody-costeo-productos').innerHTML = !rows.length
        ? '<tr><td colspan="7">Sin registros</td></tr>'
        : rows.map(r => {
            const pct = Number(r.margen_pct || 0);
            const color = pct > 30 ? 'badge-ok' : (pct >= 10 ? 'badge-bajo' : 'badge-sin');
            return `<tr><td>${r.codigo || ''}</td><td>${r.producto || ''}</td><td>${Number(r.costo_total || 0).toFixed(2)}</td><td>${Number(r.precio_min || 0).toFixed(2)}</td><td>${Number(r.margen_valor || 0).toFixed(2)}</td><td>${pct.toFixed(2)}%</td><td><span class="badge ${color}">${pct > 30 ? 'Verde' : (pct >= 10 ? 'Amarillo' : 'Rojo')}</span></td></tr>`;
        }).join('');
}
