<?php if (!Session::puedeVerCostos()) Session::forbidden(); ?>

<div class="card">
    <div class="card-header">Estructura de costos</div>
    <div class="form-section">
        <table class="tabla-base">
            <thead><tr><th>Código</th><th>Producto</th><th>Tipo costeo</th><th>MP</th><th>MO</th><th>GI</th><th>Total</th><th></th></tr></thead>
            <tbody id="tbody-costeo-estructura"></tbody>
        </table>
    </div>
</div>

<div id="modal-costeo" class="modal-backdrop" style="display:none;">
    <div class="modal-box">
        <div class="modal-header"><h3 id="modal-costeo-titulo">Editar costo</h3></div>
        <div class="form-section">
            <form id="form-costeo" onsubmit="guardarCosto(event)">
                <input type="hidden" name="producto_id" id="costeo-producto-id">
                <div class="form-group"><label>Producto</label><input id="costeo-producto" type="text" readonly></div>
                <div class="form-group"><label>Tipo costeo</label><select id="costeo-tipo" name="tipo_costeo_id" required></select></div>
                <div class="form-row two-col">
                    <div class="form-group"><label>Costo MP</label><input id="costeo-mp" name="costo_mp" type="number" step="0.0001" min="0" required></div>
                    <div class="form-group"><label>Costo MO</label><input id="costeo-mo" name="costo_mo" type="number" step="0.0001" min="0" required></div>
                </div>
                <div class="form-row two-col">
                    <div class="form-group"><label>Costo GI</label><input id="costeo-gi" name="costo_gi" type="number" step="0.0001" min="0" required></div>
                    <div class="form-group"><label>Costo total</label><input id="costeo-total" name="costo_total" type="number" step="0.0001" min="0" readonly></div>
                </div>
                <div class="form-group"><label>Notas</label><textarea name="notas" id="costeo-notas" rows="2"></textarea></div>
                <div class="modal-footer">
                    <button class="btn-cancel" type="button" onclick="cerrarModalCosto()">Cancelar</button>
                    <button class="btn-orange" type="submit">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>
