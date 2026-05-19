<?php Session::requireAdmin(); ?>

<div class="card">
    <div class="card-header">Ajuste de inventario</div>
    <div class="form-section">
        <form id="form-ajustes" onsubmit="registrarAjuste(event)">
            <div class="form-row two-col">
                <div class="form-group"><label>Almacén <span class="req">*</span></label><select id="aju-almacen" name="almacen_clave" required></select></div>
                <div class="form-group"><label>Producto <span class="req">*</span></label><select id="aju-producto" name="producto_id" required></select></div>
            </div>
            <div class="form-row two-col">
                <div class="form-group"><label>Tipo ajuste <span class="req">*</span></label><select name="tipo_ajuste" required><option value="incremento">Incremento</option><option value="decremento">Decremento</option></select></div>
                <div class="form-group"><label>Cantidad <span class="req">*</span></label><input type="number" step="0.0001" min="0.0001" name="cantidad" required></div>
            </div>
            <div class="form-row two-col">
                <div class="form-group"><label>Motivo <span class="req">*</span></label><select name="motivo" required><option value="merma">Merma</option><option value="robo">Robo</option><option value="error">Error</option><option value="conteo_fisico">Conteo físico</option><option value="otro">Otro</option></select></div>
                <div class="form-group"><label>Fecha <span class="req">*</span></label><input type="date" name="fecha" value="<?= date('Y-m-d') ?>" required></div>
            </div>
            <div class="form-group"><label>Notas</label><textarea name="notas" rows="2"></textarea></div>
            <button class="btn-orange" type="submit">Registrar ajuste</button>
        </form>
    </div>
</div>

<div class="card" style="margin-top:16px;">
    <div class="card-header">Historial ajustes</div>
    <div class="form-section">
        <table class="tabla-base">
            <thead><tr><th>Fecha</th><th>Almacén</th><th>Producto</th><th>Tipo</th><th>Cantidad</th><th>Motivo</th><th>Usuario</th></tr></thead>
            <tbody id="tbody-ajustes"></tbody>
        </table>
    </div>
</div>
