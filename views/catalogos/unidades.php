<div class="toolbar">
    <?php if (Session::isAdmin()): ?>
    <button class="btn-orange" onclick="abrirModal()">
        ➕ Nueva unidad
    </button>
    <?php endif; ?>
</div>

<div id="tabla-unidades">
    <div class="spinner">Cargando...</div>
</div>

<!-- ── Modal ──────────────────────────────────────────────── -->
<div class="modal-backdrop" id="modal-unidad" style="display:none">
    <div class="modal-box">
        <div class="modal-header">
            <span id="modal-titulo">Nueva unidad de medida</span>
            <button class="modal-close" onclick="cerrarModal()">✕</button>
        </div>
        <form id="form-unidad" onsubmit="guardarUnidad(event)">
            <input type="hidden" id="u-id" value="0">

            <div class="form-group">
                <label>Nombre <span class="req">*</span></label>
                <input type="text" id="u-nombre" maxlength="50"
                       placeholder="ej: Kilogramo" required>
            </div>

            <div class="form-group">
                <label>Abreviatura <span class="req">*</span></label>
                <input type="text" id="u-abrev" maxlength="10"
                       placeholder="ej: kg" required>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-cancel"
                        onclick="cerrarModal()">Cancelar</button>
                <button type="submit" class="btn-orange">Guardar</button>
            </div>
        </form>
    </div>
</div>