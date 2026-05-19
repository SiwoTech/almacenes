<div class="toolbar">
    <?php if (Session::isAdmin()): ?>
    <button class="btn-orange" onclick="abrirModalTcosteo()">
        ➕ Nuevo tipo de costeo
    </button>
    <?php endif; ?>
</div>

<div id="tabla-tcosteo">
    <div class="spinner">Cargando...</div>
</div>

<!-- ── Modal ──────────────────────────────────────────────── -->
<div class="modal-backdrop" id="modal-tcosteo" style="display:none">
    <div class="modal-box">
        <div class="modal-header">
            <span id="modal-tcosteo-titulo">Nuevo tipo de costeo</span>
            <button class="modal-close" onclick="cerrarModalTcosteo()">✕</button>
        </div>
        <form id="form-tcosteo" onsubmit="guardarTcosteo(event)">
            <input type="hidden" id="tc-id" value="0">

            <div class="form-group" style="margin-top:16px;">
                <label>Nombre <span class="req">*</span></label>
                <input type="text" id="tc-nombre" maxlength="50"
                       placeholder="ej: Promedio, PEPS, UEPS, Estándar" required>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-cancel"
                        onclick="cerrarModalTcosteo()">Cancelar</button>
                <button type="submit" class="btn-orange">Guardar</button>
            </div>
        </form>
    </div>
</div>