<?php if (!Session::puedeVerCostos()) Session::forbidden(); ?>

<div class="card">
    <div class="card-header">Costos por producto</div>
    <div class="form-section">
        <div class="form-row" style="display:grid;grid-template-columns:2fr 1fr 1fr;gap:10px;">
            <div class="form-group"><label>Buscar</label><input id="cos-buscar" type="text" oninput="iniciarProductos()" placeholder="Código o producto"></div>
            <div class="form-group"><label>Tipo</label><select id="cos-tipo" onchange="iniciarProductos()"><option value="">Todos</option></select></div>
            <div class="form-group"><label>Línea</label><select id="cos-linea" onchange="iniciarProductos()"><option value="">Todas</option></select></div>
        </div>
        <table class="tabla-base">
            <thead><tr><th>Código</th><th>Producto</th><th>Costo total</th><th>Precio venta (menor)</th><th>Margen $</th><th>Margen %</th><th>Semáforo</th></tr></thead>
            <tbody id="tbody-costeo-productos"></tbody>
        </table>
    </div>
</div>
