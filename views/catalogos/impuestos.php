<div class="toolbar">
    <?php if (Session::isAdmin()): ?>
    <button class="btn-orange" onclick="abrirModalImpuesto()">
        ➕ Nuevo esquema
    </button>
    <?php endif; ?>
</div>

<div id="tabla-impuestos">
    <div class="spinner">Cargando...</div>
</div>

<!-- ── Modal ──────────────────────────────────────────────── -->
<div class="modal-backdrop" id="modal-impuesto" style="display:none">
    <div class="modal-box">
        <div class="modal-header">
            <span id="modal-impuesto-titulo">Nuevo esquema de impuesto</span>
            <button class="modal-close" onclick="cerrarModalImpuesto()">✕</button>
        </div>
        <form id="form-impuesto" onsubmit="guardarImpuesto(event)">
            <input type="hidden" id="i-id" value="0">

            <div class="form-group" style="margin-top:16px;">
                <label>Nombre <span class="req">*</span></label>
                <input type="text" id="i-nombre" maxlength="100"
                       placeholder="ej: IVA 16%, Exento, Tasa 0%" required>
            </div>

            <div class="form-group">
                <label>Porcentaje <span class="req">*</span></label>
                <div style="display:flex; align-items:center; gap:8px;">
                    <input type="number" id="i-porcentaje" min="0" max="100"
                           step="0.01" placeholder="16.00" required
                           style="width:120px;">
                    <span style="color:var(--gray); font-size:.9rem;">%</span>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-cancel"
                        onclick="cerrarModalImpuesto()">Cancelar</button>
                <button type="submit" class="btn-orange">Guardar</button>
            </div>
        </form>
    </div>
</div>