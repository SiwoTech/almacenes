<?php
if (!Session::puedeConsumoInterno()) {
    Session::forbidden();
}
?>

<div class="card">
    <div class="card-header">Consumo interno</div>
    <div class="form-section">
        <form id="form-consumo" onsubmit="registrarConsumo(event)">
            <div class="form-row two-col">
                <div class="form-group"><label>Almacén <span class="req">*</span></label><select id="con-almacen" name="almacen_clave" required></select></div>
                <div class="form-group"><label>Producto <span class="req">*</span></label><select id="con-producto" name="producto_id" required></select></div>
            </div>
            <div class="form-row two-col">
                <div class="form-group"><label>Cantidad <span class="req">*</span></label><input type="number" step="0.0001" min="0.0001" name="cantidad" required></div>
                <div class="form-group"><label>Tipo de gasto</label><select id="con-tgasto" name="tipo_gasto_id"></select></div>
            </div>
            <div class="form-row two-col">
                <div class="form-group"><label>Descripción</label><input type="text" name="descripcion"></div>
                <div class="form-group"><label>Fecha <span class="req">*</span></label><input type="date" name="fecha" value="<?= date('Y-m-d') ?>" required></div>
            </div>
            <button class="btn-orange" type="submit">Registrar consumo</button>
        </form>
    </div>
</div>

<div class="card" style="margin-top:16px;">
    <div class="card-header">Historial consumos del mes</div>
    <div class="form-section">
        <table class="tabla-base">
            <thead><tr><th>Fecha</th><th>Almacén</th><th>Producto</th><th>Cantidad</th><th>Tipo gasto</th><th>Descripción</th><th>Usuario</th></tr></thead>
            <tbody id="tbody-consumo"></tbody>
        </table>
    </div>
</div>
