<div class="toolbar">
    <?php if (Session::isAdmin()): ?>
    <button class="btn-orange" onclick="abrirModalLinea()">
        ➕ Nueva línea
    </button>
    <?php endif; ?>
</div>

<div id="tabla-lineas">
    <div class="spinner">Cargando...</div>
</div>

<!-- ── Modal ──────────────────────────────────────────────── -->
<div class="modal-backdrop" id="modal-linea" style="display:none">
    <div class="modal-box">
        <div class="modal-header">
            <span id="modal-linea-titulo">Nueva línea</span>
            <button class="modal-close" onclick="cerrarModalLinea()">✕</button>
        </div>
        <form id="form-linea" onsubmit="guardarLinea(event)">
            <input type="hidden" id="l-id" value="0">

            <div class="form-group" style="margin-top:16px;">
                <label>Nombre <span class="req">*</span></label>
                <input type="text" id="l-nombre" maxlength="100"
                       placeholder="ej: Limpieza, Alimentos, Refacciones" required>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-cancel"
                        onclick="cerrarModalLinea()">Cancelar</button>
                <button type="submit" class="btn-orange">Guardar</button>
            </div>
        </form>
    </div>
</div>