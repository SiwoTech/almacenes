<?php Session::requireAny(); ?>

<div class="card">
    <div class="card-header">Registro de entradas</div>
    <div class="form-section">
        <form id="form-entradas" onsubmit="registrarEntrada(event)">
            <div class="form-row two-col">
                <div class="form-group">
                    <label>Almacén destino <span class="req">*</span></label>
                    <select id="ent-almacen" name="almacen_clave" required></select>
                </div>
                <div class="form-group">
                    <label>Producto <span class="req">*</span></label>
                    <select id="ent-producto" name="producto_id" required></select>
                </div>
            </div>
            <div class="form-row two-col">
                <div class="form-group">
                    <label>Cantidad <span class="req">*</span></label>
                    <input type="number" step="0.0001" min="0.0001" name="cantidad" required>
                </div>
                <div class="form-group">
                    <label>Costo unitario <span class="req">*</span></label>
                    <input type="number" step="0.0001" min="0" name="costo_unitario" required>
                </div>
            </div>
            <div class="form-row two-col">
                <div class="form-group">
                    <label>Proveedor / referencia</label>
                    <input type="text" name="proveedor">
                </div>
                <div class="form-group">
                    <label>Fecha <span class="req">*</span></label>
                    <input type="date" name="fecha" value="<?= date('Y-m-d') ?>" required>
                </div>
            </div>
            <div class="form-group">
                <label>Notas</label>
                <textarea name="notas" rows="2"></textarea>
            </div>
            <div>
                <button class="btn-orange" type="submit">Registrar entrada</button>
            </div>
        </form>
    </div>
</div>

<div class="card" style="margin-top:16px;">
    <div class="card-header">Historial de entradas del mes</div>
    <div class="form-section">
        <div class="form-row" style="display:grid;grid-template-columns:repeat(5,minmax(140px,1fr));gap:10px;">
            <div class="form-group"><label>Almacén</label><select id="f-ent-almacen" onchange="filtrarEntradas()"></select></div>
            <div class="form-group"><label>Producto</label><select id="f-ent-producto" onchange="filtrarEntradas()"></select></div>
            <div class="form-group"><label>Desde</label><input id="f-ent-fecha-ini" type="date" value="<?= date('Y-m-01') ?>" onchange="filtrarEntradas()"></div>
            <div class="form-group"><label>Hasta</label><input id="f-ent-fecha-fin" type="date" value="<?= date('Y-m-d') ?>" onchange="filtrarEntradas()"></div>
            <div class="form-group" style="align-self:end;"><button class="btn-secondary" type="button" onclick="exportarEntradas()">Exportar</button></div>
        </div>
        <div id="tabla-entradas">
            <table class="tabla-base">
                <thead><tr><th>Fecha</th><th>Folio</th><th>Almacén</th><th>Producto</th><th>Cantidad</th><th>Costo unitario</th><th>Total</th><th>Usuario</th></tr></thead>
                <tbody id="tbody-entradas"></tbody>
            </table>
        </div>
    </div>
</div>
