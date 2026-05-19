<div class="toolbar">
    <?php if (Session::isAdmin()): ?>
    <button class="btn-orange" onclick="abrirModalMoneda()">
        ➕ Nueva moneda
    </button>
    <?php endif; ?>
</div>

<div id="tabla-monedas">
    <div class="spinner">Cargando...</div>
</div>

<!-- ── Modal ──────────────────────────────────────────────── -->
<div class="modal-backdrop" id="modal-moneda" style="display:none">
    <div class="modal-box">
        <div class="modal-header">
            <span id="modal-moneda-titulo">Nueva moneda</span>
            <button class="modal-close" onclick="cerrarModalMoneda()">✕</button>
        </div>
        <form id="form-moneda" onsubmit="guardarMoneda(event)">
            <input type="hidden" id="m-id" value="0">

            <div class="form-group" style="margin-top:16px;">
                <label>Nombre <span class="req">*</span></label>
                <input type="text" id="m-nombre" maxlength="50"
                       placeholder="ej: Peso Mexicano, Dólar Americano" required>
            </div>

            <div class="form-group">
                <label>Símbolo <span class="req">*</span></label>
                <input type="text" id="m-simbolo" maxlength="5"
                       placeholder="ej: $, USD, €" required>
            </div>

            <div class="form-group">
                <label>Tipo de cambio vs MXN <span class="req">*</span></label>
                <div style="display:flex; align-items:center; gap:8px;">
                    <input type="number" id="m-tipo-cambio" min="0.0001"
                           step="0.0001" placeholder="1.0000" required
                           style="width:140px;">
                    <span style="color:var(--gray); font-size:.85rem;">MXN por unidad</span>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-cancel"
                        onclick="cerrarModalMoneda()">Cancelar</button>
                <button type="submit" class="btn-orange">Guardar</button>
            </div>
        </form>
    </div>
</div>