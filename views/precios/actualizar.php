<?php if (!Session::puedeModificarCostos()) Session::forbidden(); ?>

<div class="card">
    <div class="card-header">Actualizar precio de venta</div>
    <div class="form-section">
        <form id="form-precio" onsubmit="guardarPrecio(event)">
            <div class="form-row two-col">
                <div class="form-group"><label>Producto <span class="req">*</span></label><select id="act-producto" name="producto_id" required></select></div>
                <div class="form-group"><label>Tipo cliente <span class="req">*</span></label><select id="act-cliente" name="tipo_cliente_id" required></select></div>
            </div>
            <div class="form-row two-col">
                <div class="form-group"><label>Moneda <span class="req">*</span></label><select id="act-moneda" name="moneda_id" required></select></div>
                <div class="form-group"><label>Impuesto <span class="req">*</span></label><select id="act-impuesto" name="impuesto_id" required></select></div>
            </div>
            <div class="form-row two-col">
                <div class="form-group"><label>Precio <span class="req">*</span></label><input name="precio" type="number" step="0.0001" min="0" required></div>
                <div class="form-group"><label>% impuesto (informativo)</label><input id="act-impuesto-pct" type="text" readonly></div>
            </div>
            <button class="btn-orange" type="submit">Guardar precio</button>
        </form>
    </div>
</div>

<div class="card" style="margin-top:16px;">
    <div class="card-header">Historial de cambios</div>
    <div class="form-section">
        <table class="tabla-base">
            <thead><tr><th>Fecha</th><th>Producto</th><th>Tipo cliente</th><th>Moneda</th><th>Precio</th><th>Impuesto</th><th>Actualizó</th></tr></thead>
            <tbody id="tbody-precios-historial"></tbody>
        </table>
    </div>
</div>
