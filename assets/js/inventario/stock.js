let _stock     = [];
let _almacenes = [];

document.addEventListener('DOMContentLoaded', () => {
    cargarAlmacenesSelect();
    cargarStock();
});

// ── Almacenes select ───────────────────────────────────
function cargarAlmacenesSelect() {
    cwoFetch('/almacenes/api/inventario/stock.php?action=almacenes')
        .then(res => {
            if (!res.ok) return;
            _almacenes = res.data;
            const sel = document.getElementById('inv-almacen');
            res.data.forEach(a => {
                const opt = document.createElement('option');
                opt.value       = a.clave;
                opt.textContent = a.clave;
                sel.appendChild(opt);
            });
        });
}

// ── Cargar stock ───────────────────────────────────────
function cargarStock() {
    cwoFetch('/almacenes/api/inventario/stock.php?action=listar')
        .then(res => {
            if (!res.ok) { alert(res.message); return; }
            _stock = res.data;
            renderKPIs(_stock);
            filtrarStock();
        })
        .catch(() => alert('❌ Error de conexión'));
}

// ── KPIs ───────────────────────────────────────────────
function renderKPIs(rows) {
    const total   = rows.length;
    const sinStock = rows.filter(r => r.estado_stock === 'sin_stock').length;
    const bajo    = rows.filter(r => r.estado_stock === 'stock_bajo').length;
    const ok      = rows.filter(r => r.estado_stock === 'stock_ok').length;

    document.getElementById('inv-kpis').innerHTML = `
        ${kpiMini('bi-boxes',          '#3b82f6', '#eff6ff', 'Total productos', total)}
        ${kpiMini('bi-check-circle',   '#10b981', '#d1fae5', 'Stock OK',        ok)}
        ${kpiMini('bi-exclamation-triangle', '#f59e0b', '#fef3c7', 'Stock bajo', bajo)}
        ${kpiMini('bi-x-circle',       '#ef4444', '#fee2e2', 'Sin stock',       sinStock)}
    `;
}

function kpiMini(icon, color, bg, label, val) {
    return `
    <div style="background:#fff;border:1px solid #e5e7eb;border-radius:10px;
                padding:14px 18px;display:flex;align-items:center;gap:14px;">
        <div style="width:40px;height:40px;border-radius:9px;background:${bg};
                    display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="bi ${icon}" style="color:${color};font-size:1.1rem;"></i>
        </div>
        <div>
            <div style="font-size:.75rem;color:#9ca3af;">${label}</div>
            <div style="font-size:1.5rem;font-weight:700;color:#111;line-height:1.2;">${val}</div>
        </div>
    </div>`;
}

// ── Filtrar ────────────────────────────────────────────
function filtrarStock() {
    const buscar  = (document.getElementById('inv-buscar')?.value  ?? '').toLowerCase();
    const almacen = document.getElementById('inv-almacen')?.value  ?? '';
    const tipo    = document.getElementById('inv-tipo')?.value     ?? '';
    const estado  = document.getElementById('inv-estado')?.value   ?? '';

    const fil = _stock.filter(r => {
        const matchB = !buscar  || r.codigo.toLowerCase().includes(buscar)
                                || r.producto.toLowerCase().includes(buscar);
        const matchA = !almacen || r.franquicia_clave === almacen;
        const matchT = !tipo    || r.tipo === tipo;
        const matchE = !estado  || r.estado_stock === estado;
        return matchB && matchA && matchT && matchE;
    });

    document.getElementById('inv-contador').textContent = `${fil.length} registro(s)`;
    renderTablaStock(fil);
}

// ── Render tabla ───────────────────────────────────────
const estadoConfig = {
    sin_stock:  { label:'Sin stock',   bg:'#fee2e2', color:'#991b1b', dot:'#ef4444' },
    stock_bajo: { label:'Stock bajo',  bg:'#fef3c7', color:'#92400e', dot:'#f59e0b' },
    stock_ok:   { label:'Stock OK',    bg:'#d1fae5', color:'#065f46', dot:'#10b981' },
    stock_alto: { label:'Stock alto',  bg:'#dbeafe', color:'#1e40af', dot:'#3b82f6' },
};

const tipoLabel = {
    materia_prima:     'MP',
    producto_terminado:'PT',
    insumo:            'INS',
    envase:            'ENV',
    uniforme:          'UNI',
    herramienta:       'HER',
    refaccion:         'REF',
    otro:              'OTR',
};

function renderTablaStock(rows) {
    const tbody = document.getElementById('inv-tbody');

    if (!rows.length) {
        tbody.innerHTML = `
            <tr><td colspan="9"
                style="text-align:center;padding:40px;color:#9ca3af;font-size:.85rem;">
                <i class="bi bi-inbox" style="font-size:1.4rem;display:block;margin-bottom:6px;"></i>
                Sin resultados
            </td></tr>`;
        return;
    }

    tbody.innerHTML = rows.map((r, i) => {
        const est  = estadoConfig[r.estado_stock] ?? estadoConfig.stock_ok;
        const borde = i < rows.length - 1 ? 'border-bottom:1px solid #f3f4f6;' : '';
        const tl   = tipoLabel[r.tipo] ?? 'OTR';
        const fecha = r.ultima_actualizacion
            ? r.ultima_actualizacion.substring(0,10)
            : '—';

        return `
        <tr style="${borde}" onmouseover="this.style.background='#fafafa'"
                             onmouseout="this.style.background=''">
            <td style="padding:12px 16px;">
                <span style="font-family:monospace;font-weight:700;font-size:.85rem;color:#111;">
                    ${r.codigo}
                </span>
            </td>
            <td style="padding:12px 16px;">
                <div style="font-size:.875rem;color:#374151;">${r.producto}</div>
                ${r.linea ? `<div style="font-size:.75rem;color:#9ca3af;">${r.linea}</div>` : ''}
            </td>
            <td style="padding:12px 16px;">
                <span style="font-family:monospace;font-size:.8rem;font-weight:600;
                             color:#6d28d9;background:#f5f3ff;padding:2px 8px;border-radius:6px;">
                    ${r.franquicia_clave}
                </span>
            </td>
            <td style="padding:12px 16px;">
                <span style="font-size:.75rem;font-weight:600;color:#475569;
                             background:#f1f5f9;padding:2px 8px;border-radius:6px;">
                    ${tl}
                </span>
            </td>
            <td style="padding:12px 16px;text-align:right;">
                <strong style="font-size:.95rem;color:#111;">
                    ${parseFloat(r.existencias).toLocaleString('es-MX', {maximumFractionDigits:2})}
                </strong>
                <span style="font-size:.75rem;color:#9ca3af;margin-left:4px;">${r.unidad ?? ''}</span>
            </td>
            <td style="padding:12px 16px;text-align:right;color:#6b7280;font-size:.85rem;">
                ${parseFloat(r.stock_minimo).toLocaleString('es-MX', {maximumFractionDigits:2})}
            </td>
            <td style="padding:12px 16px;text-align:right;color:#6b7280;font-size:.85rem;">
                ${parseFloat(r.stock_maximo).toLocaleString('es-MX', {maximumFractionDigits:2})}
            </td>
            <td style="padding:12px 16px;">
                <span style="display:inline-flex;align-items:center;gap:5px;
                             background:${est.bg};color:${est.color};
                             padding:3px 10px;border-radius:999px;
                             font-size:.75rem;font-weight:600;">
                    <span style="width:6px;height:6px;border-radius:50%;
                                 background:${est.dot};display:inline-block;"></span>
                    ${est.label}
                </span>
            </td>
            <td style="padding:12px 16px;font-size:.78rem;color:#9ca3af;">
                ${fecha}
            </td>
        </tr>`;
    }).join('');
}

// ── Exportar CSV ───────────────────────────────────────
function exportarStock() {
    const rows = _stock;
    if (!rows.length) { alert('Sin datos para exportar'); return; }

    const cols = ['franquicia_clave','codigo','producto','tipo','unidad',
                  'existencias','stock_minimo','stock_maximo','estado_stock'];
    const header = ['Almacén','Código','Producto','Tipo','Unidad',
                    'Existencias','Stock Mín','Stock Máx','Estado'];

    const csv = [header.join(','),
        ...rows.map(r => cols.map(c => `"${r[c] ?? ''}"`).join(','))
    ].join('\n');

    const a   = document.createElement('a');
    a.href    = 'data:text/csv;charset=utf-8,\uFEFF' + encodeURIComponent(csv);
    a.download= `stock_${new Date().toISOString().substring(0,10)}.csv`;
    a.click();
}