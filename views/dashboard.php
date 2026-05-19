<?php
$esAdmon = in_array(Session::$rol, [Session::ROL_ADMIN, Session::ROL_ADMON]);
?>

<!-- ── Fila 1: KPIs Inventario + Tránsito ──────────────── -->
<div class="kpi-grid">

    <div class="kpi-card">
        <div class="kpi-icon">📦</div>
        <div>
            <div class="kpi-label">Productos en stock</div>
            <div class="kpi-value" id="kpi-total-productos">—</div>
        </div>
    </div>

    <div class="kpi-card">
        <div class="kpi-icon danger">🚫</div>
        <div>
            <div class="kpi-label">Sin stock</div>
            <div class="kpi-value" id="kpi-sin-stock">—</div>
        </div>
    </div>

    <div class="kpi-card">
        <div class="kpi-icon warning">⚠️</div>
        <div>
            <div class="kpi-label">Stock bajo</div>
            <div class="kpi-value" id="kpi-stock-bajo">—</div>
        </div>
    </div>

    <div class="kpi-card">
        <div class="kpi-icon success">🚚</div>
        <div>
            <div class="kpi-label">En tránsito</div>
            <div class="kpi-value" id="kpi-en-transito">—</div>
        </div>
    </div>

</div>

<!-- ── Fila 2: Movimientos + Producción ────────────────── -->
<div class="kpi-grid" style="margin-top:12px;">

    <div class="kpi-card">
        <div class="kpi-icon">🔄</div>
        <div>
            <div class="kpi-label">Movimientos este mes</div>
            <div class="kpi-value" id="kpi-mov-mes">—</div>
        </div>
    </div>

    <div class="kpi-card">
        <div class="kpi-icon warning">⚙️</div>
        <div>
            <div class="kpi-label">Órdenes en proceso</div>
            <div class="kpi-value" id="kpi-ord-abiertas">—</div>
        </div>
    </div>

    <div class="kpi-card">
        <div class="kpi-icon">📥</div>
        <div>
            <div class="kpi-label">Entradas este mes</div>
            <div class="kpi-value" id="kpi-entradas">—</div>
        </div>
    </div>

    <div class="kpi-card">
        <div class="kpi-icon success">✅</div>
        <div>
            <div class="kpi-label">Órdenes terminadas</div>
            <div class="kpi-value" id="kpi-ord-completadas">—</div>
        </div>
    </div>

</div>

<!-- ── Período activo (solo admon/admin) ─────────────────── -->
<?php if ($esAdmon): ?>
<div id="periodo-activo-container" style="margin-top:16px;"></div>
<?php endif; ?>

<!-- ── Tablas ─────────────────────────────────────────────── -->
<div class="two-col" style="margin-top:24px;">

    <div>
        <h6>⚠️ Stock Crítico</h6>
        <div id="tabla-stock-critico">
            <div class="spinner">Cargando...</div>
        </div>
    </div>

    <div>
        <h6>🕐 Últimos Movimientos</h6>
        <div id="tabla-ult-movimientos">
            <div class="spinner">Cargando...</div>
        </div>
    </div>

</div>