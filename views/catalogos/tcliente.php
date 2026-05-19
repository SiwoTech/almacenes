<div class="toolbar">
    <?php if (Session::isAdmin()): ?>
    <button class="btn-orange" onclick="abrirModalTcliente()">
        ➕ Nuevo tipo de cliente
    </button>
    <?php endif; ?>
</div>

<div id="tabla-tcliente">
    <div class="spinner">Cargando...</div>
</div>

<!-- ── Modal ──────────────────────────────────────────────── -->
<div class="modal-backdrop" id="modal-tcliente" style="display:none">
    <div class="modal-box">
        <div class="modal-header">
            <span id="modal-tcliente-titulo">Nuevo tipo de cliente</span>
            <button class="modal-close" onclick="cerrarModalTcliente()">✕</button>
        </div>
        <form id="form-tcliente" onsubmit="guardarTcliente(event)">
            <input type="hidden" id="cl-id" value="0">

            <div class="form-group" style="margin-top:16px;">
                <label>Nombre <span class="req">*</span></label>
                <input type="text" id="cl-nombre" maxlength="100"
                       placeholder="ej: Mayoreo, Menudeo, Franquicia" required>
            </div>

            <div class="form-group">
                <label>Descripción</label>
                <textarea id="cl-descripcion" rows="3" maxlength="500"
                          placeholder="Descripción opcional..."></textarea>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-cancel"
                        onclick="cerrarModalTcliente()">Cancelar</button>
                <button type="submit" class="btn-orange">Guardar</button>
            </div>
        </form>
    </div>
</div>