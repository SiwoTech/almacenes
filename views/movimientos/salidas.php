<?php Session::requireAny(); ?>

<div class="card">
    <div class="card-header">Registro de salidas</div>
    <div class="form-section">
        <form id="form-salidas" onsubmit="registrarSalida(event)">
            <div class="form-row two-col">
                <div class="form-group"><label>Almacén origen <span class="req">*</span></label><select id="sal-almacen" name="almacen_clave" required></select></div>
                <div class="form-group"><label>Producto <span class="req">*</span></label><select id="sal-producto" name="producto_id" required></select></div>
            </div>
            <div class="form-row two-col">
                <div class="form-group"><label>Cantidad <span class="req">*</span></label><input type="number" step="0.0001" min="0.0001" name="cantidad" required></div>
                <div class="form-group"><label>Precio venta</label><input type="number" step="0.0001" min="0" name="precio_venta"></div>
            </div>
            <div class="form-row two-col">
                <div class="form-group"><label>Tipo cliente</label><select id="sal-tcliente" name="tipo_cliente_id"></select></div>
                <div class="form-group"><label>Referencia</label><input type="text" name="referencia"></div>
            </div>
            <div class="form-row two-col">
                <div class="form-group"><label>Fecha <span class="req">*</span></label><input type="date" name="fecha" value="<?= date('Y-m-d') ?>" required></div>
                <div class="form-group"><label>Notas</label><input type="text" name="notas"></div>
            </div>
            <button class="btn-orange" type="submit">Registrar salida</button>
        </form>
    </div>
</div>

<div class="card" style="margin-top:16px;">
    <div class="card-header">Historial salidas del mes</div>
    <div class="form-section">
        <div class="form-row" style="display:grid;grid-template-columns:repeat(5,minmax(140px,1fr));gap:10px;">
            <div class="form-group"><label>Almacén</label><select id="f-sal-almacen" onchange="filtrarSalidas()"></select></div>
            <div class="form-group"><label>Producto</label><select id="f-sal-producto" onchange="filtrarSalidas()"></select></div>
            <div class="form-group"><label>Desde</label><input id="f-sal-fecha-ini" type="date" value="<?= date('Y-m-01') ?>" onchange="filtrarSalidas()"></div>
            <div class="form-group"><label>Hasta</label><input id="f-sal-fecha-fin" type="date" value="<?= date('Y-m-d') ?>" onchange="filtrarSalidas()"></div>
            <div class="form-group" style="align-self:end;"><button class="btn-secondary" type="button" onclick="exportarSalidas()">Exportar</button></div>
        </div>
        <table class="tabla-base">
            <thead><tr><th>Fecha</th><th>Folio</th><th>Almacén</th><th>Producto</th><th>Cantidad</th><th>Precio</th><th>Referencia</th><th>Usuario</th></tr></thead>
            <tbody id="tbody-salidas"></tbody>
        </table>
    </div>
</div>
