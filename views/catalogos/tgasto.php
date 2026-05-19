<div class="toolbar">
    <?php if (Session::isAdmin()): ?>
    <button class="btn-orange" onclick="abrirModalTgasto()">
        ➕ Nuevo tipo de gasto
    </button>
    <?php endif; ?>
</div>

<div id="tabla-tgasto">
    <div class="spinner">Cargando...</div>
</div>

<!-- ── Modal ──────────────────────────────────────────────── -->
<div class="modal-backdrop" id="modal-tgasto" style="display:none">
    <div class="modal-box">
        <div class="modal-header">
            <span id="modal-tgasto-titulo">Nuevo tipo de gasto</span>
            <button class="modal-close" onclick="cerrarModalTgasto()">✕</button>
        </div>
        <form id="form-tgasto" onsubmit="guardarTgasto(event)">
            <input type="hidden" id="tg-id" value="0">

            <div class="form-group" style="margin-top:16px;">
                <label>Nombre <span class="req">*</span></label>
                <input type="text" id="tg-nombre" maxlength="100"
                       placeholder="ej: Renta, Energía, Transporte" required>
            </div>

            <div class="form-group">
                <label>Base de prorrateo <span class="req">*</span></label>
                <select id="tg-base" required>
                    <option value="unidades_producidas">Unidades producidas</option>
                    <option value="unidades_enviadas">Unidades enviadas</option>
                    <option value="horas_maquina">Horas máquina</option>
                    <option value="peso_producido">Peso producido (kg)</option>
                    <option value="manual">Manual (% asignado)</option>
                </select>
            </div>

            <div class="form-group">
                <label>Descripción</label>
                <textarea id="tg-descripcion" rows="3" maxlength="500"
                          placeholder="Descripción opcional..."></textarea>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-cancel"
                        onclick="cerrarModalTgasto()">Cancelar</button>
                <button type="submit" class="btn-orange">Guardar</button>
            </div>
        </form>
    </div>
</div>