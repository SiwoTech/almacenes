<?php Session::requireAny(); ?>

<div class="card">
    <div class="card-header">Transferencia entre almacenes</div>
    <div class="form-section">
        <form id="form-transferencias" onsubmit="registrarTransferencia(event)">
            <div class="form-row two-col">
                <div class="form-group"><label>Almacén origen <span class="req">*</span></label><select id="tr-origen" name="origen" required></select></div>
                <div class="form-group"><label>Almacén destino <span class="req">*</span></label><select id="tr-destino" name="destino" required></select></div>
            </div>
            <div class="form-row two-col">
                <div class="form-group"><label>Producto <span class="req">*</span></label><select id="tr-producto" name="producto_id" required></select></div>
                <div class="form-group"><label>Cantidad <span class="req">*</span></label><input type="number" step="0.0001" min="0.0001" name="cantidad" required></div>
            </div>
            <div class="form-row two-col">
                <div class="form-group"><label>Fecha <span class="req">*</span></label><input type="date" name="fecha" value="<?= date('Y-m-d') ?>" required></div>
                <div class="form-group"><label>Notas</label><input type="text" name="notas"></div>
            </div>
            <button class="btn-orange" type="submit">Registrar transferencia</button>
        </form>
    </div>
</div>

<div class="card" style="margin-top:16px;">
    <div class="card-header">Historial transferencias del mes</div>
    <div class="form-section">
        <div class="form-row" style="display:grid;grid-template-columns:repeat(5,minmax(140px,1fr));gap:10px;">
            <div class="form-group"><label>Origen</label><select id="f-tr-origen" onchange="filtrarTransferencias()"></select></div>
            <div class="form-group"><label>Destino</label><select id="f-tr-destino" onchange="filtrarTransferencias()"></select></div>
            <div class="form-group"><label>Desde</label><input id="f-tr-fecha-ini" type="date" value="<?= date('Y-m-01') ?>" onchange="filtrarTransferencias()"></div>
            <div class="form-group"><label>Hasta</label><input id="f-tr-fecha-fin" type="date" value="<?= date('Y-m-d') ?>" onchange="filtrarTransferencias()"></div>
            <div class="form-group" style="align-self:end;"><button class="btn-secondary" type="button" onclick="exportarTransferencias()">Exportar</button></div>
        </div>
        <table class="tabla-base">
            <thead><tr><th>Fecha</th><th>Origen</th><th>Destino</th><th>Producto</th><th>Cantidad</th><th>Estado</th><th>Usuario</th></tr></thead>
            <tbody id="tbody-transferencias"></tbody>
        </table>
    </div>
</div>
