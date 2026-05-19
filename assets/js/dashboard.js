document.addEventListener('DOMContentLoaded', () => {
    cargarDashboard();
});

function cargarDashboard() {
    cwoFetch('/almacenes/api/dashboard.php')
        .then(res => {
            if (!res.ok) { cwoError('kpi-container', res.message); return; }
            renderKPIs(res.data);
            renderStockCritico(res.data.stock_critico);
            renderUltMovimientos(res.data.ult_movimientos);
            renderPeriodoActivo(res.data.periodo_activo);
        })
        .catch(err => {
            console.error(err);
            cwoError('kpi-container', 'Error de conexión con el servidor');
        });
}

// ── KPIs ─────────────────────────────────────────────────
function renderKPIs(d) {
    const inv = d.kpi_inventario  || {};
    const mov = d.kpi_movimientos || {};
    const pro = d.kpi_produccion  || {};

    setText('kpi-total-productos', inv.total_productos ?? 0);
    setText('kpi-sin-stock',       inv.sin_stock       ?? 0);
    setText('kpi-stock-bajo',      inv.stock_bajo      ?? 0);
    setText('kpi-en-transito',     mov.en_transito     ?? 0);
    setText('kpi-mov-mes',         mov.total_mes       ?? 0);
    setText('kpi-entradas',        mov.entradas        ?? 0);
    setText('kpi-ord-abiertas',    pro.en_proceso      ?? 0);
    setText('kpi-ord-completadas', pro.terminadas      ?? 0);
}

// ── Período activo ────────────────────────────────────────
function renderPeriodoActivo(periodo) {
    const el = document.getElementById('periodo-activo-container');
    if (!el) return;
    if (!periodo) {
        el.innerHTML = `<div class="alert alert-warning">
            ⚠️ Sin período de costeo activo.</div>`;
        return;
    }
    const badge = periodo.estatus === 'abierto'
        ? '<span class="badge badge-ok">Abierto</span>'
        : '<span class="badge badge-bajo">En revisión</span>';
    el.innerHTML = `<div class="alert alert-warning">
        📅 <strong>Período activo:</strong> ${periodo.nombre} ${badge}
        &nbsp;<small>${periodo.fecha_inicio} → ${periodo.fecha_fin}</small>
    </div>`;
}

// ── Stock crítico ─────────────────────────────────────────
function renderStockCritico(rows) {
    const el = document.getElementById('tabla-stock-critico');
    if (!rows || !rows.length) {
        el.innerHTML = `<div class="alert alert-success">
            ✅ Sin productos en estado crítico.</div>`;
        return;
    }
    const filas = rows.map(r => `
        <tr>
            <td><strong>${r.codigo}</strong><br>
                <small style="color:#999">${r.producto}</small></td>
            <td class="num">${r.existencias} ${r.unidad}</td>
            <td class="num">${r.stock_minimo} ${r.unidad}</td>
            <td class="num">
                <span class="badge ${badgeStock(r.estado_stock)}">
                    ${labelStock(r.estado_stock)}
                </span>
            </td>
        </tr>`).join('');

    el.innerHTML = `
        <table class="dashboard-table">
            <thead><tr>
                <th>Producto</th>
                <th class="num">Existencia</th>
                <th class="num">Mínimo</th>
                <th class="num">Estado</th>
            </tr></thead>
            <tbody>${filas}</tbody>
        </table>`;
}

// ── Últimos movimientos ───────────────────────────────────
function renderUltMovimientos(rows) {
    const el = document.getElementById('tabla-ult-movimientos');
    if (!rows || !rows.length) {
        el.innerHTML = `<div class="alert alert-warning">
            Sin movimientos registrados.</div>`;
        return;
    }
    const filas = rows.map(r => `
        <tr>
            <td><strong>${r.folio}</strong></td>
            <td>${labelTipo(r.tipo)}</td>
            <td class="num">${r.num_productos} prod.<br>
                <small style="color:#999">${r.total_unidades} uds.</small></td>
            <td class="num">
                <span class="badge ${badgeEstatus(r.estatus)}">${r.estatus}</span>
            </td>
            <td style="font-size:.8rem;color:#999">
                ${formatFecha(r.fecha_movimiento)}
            </td>
        </tr>`).join('');

    el.innerHTML = `
        <table class="dashboard-table">
            <thead><tr>
                <th>Folio</th>
                <th>Tipo</th>
                <th class="num">Detalle</th>
                <th class="num">Estatus</th>
                <th>Fecha</th>
            </tr></thead>
            <tbody>${filas}</tbody>
        </table>`;
}

// ── Helpers ───────────────────────────────────────────────
function setText(id, val) {
    const el = document.getElementById(id);
    if (el) el.textContent = (val === null || val === undefined) ? '0' : val;
}

function badgeStock(e) {
    return {
        'sin_stock':  'badge-sin',
        'stock_bajo': 'badge-bajo',
        'stock_ok':   'badge-ok',
        'stock_alto': 'badge-alto',
    }[e] ?? '';
}

function labelStock(e) {
    return {
        'sin_stock':  'Sin stock',
        'stock_bajo': 'Stock bajo',
        'stock_ok':   'OK',
        'stock_alto': 'Stock alto',
    }[e] ?? e;
}

function badgeEstatus(e) {
    return {
        'pendiente':   '',
        'en_transito': 'badge-transito',
        'recibido':    'badge-recibido',
        'cancelado':   'badge-cancelado',
    }[e] ?? '';
}

function labelTipo(t) {
    return {
        'entrada_compra':                  'Entrada compra',
        'entrada_produccion':              'Entrada producción',
        'entrada_devolucion':              'Devolución',
        'entrada_recepcion_transferencia': 'Recepción transfer.',
        'salida_venta':                    'Venta',
        'salida_transferencia':            'Transferencia',
        'salida_consumo_interno':          'Consumo interno',
        'ajuste_positivo':                 'Ajuste +',
        'ajuste_negativo':                 'Ajuste −',
        'baja':                            'Baja',
    }[t] ?? t;
}

function formatFecha(f) {
    if (!f) return '—';
    return new Date(f).toLocaleDateString('es-MX', {
        day: '2-digit', month: 'short', year: 'numeric'
    });
}