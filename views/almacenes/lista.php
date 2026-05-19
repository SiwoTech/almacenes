<?php Session::requireAdmin(); ?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
    <div>
        <h2 style="font-size:1rem; font-weight:700; color:#111; margin:0;">
            Catálogo de Almacenes
        </h2>
        <p style="font-size:.8rem; color:#9ca3af; margin:3px 0 0;">
            Gestiona los almacenes y franquicias del sistema
        </p>
    </div>
    <?php if (Session::isAdmin()): ?>
    <button class="btn-orange" onclick="abrirModalAlmacen()" style="display:flex;align-items:center;gap:6px;">
        <i class="bi bi-plus-lg"></i> Nuevo almacén
    </button>
    <?php endif; ?>
</div>

<!-- Filtros -->
<div style="display:flex; gap:10px; flex-wrap:wrap; align-items:center; margin-bottom:18px;">
    <div style="position:relative; flex:1; min-width:200px; max-width:280px;">
        <i class="bi bi-search" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#9ca3af;font-size:.85rem;"></i>
        <input id="alm-buscar" type="text" placeholder="Buscar clave, nombre, ciudad..."
               oninput="filtrarAlmacenes()"
               style="width:100%;padding:8px 10px 8px 32px;border:1px solid #e5e7eb;
                      border-radius:8px;font-size:.85rem;background:#fff;">
    </div>
    <select id="alm-tipo" onchange="filtrarAlmacenes()"
            style="padding:8px 12px;border:1px solid #e5e7eb;border-radius:8px;font-size:.85rem;background:#fff;color:#374151;">
        <option value="">Todos los tipos</option>
        <option value="matriz">Matriz</option>
        <option value="sucursal">Sucursal</option>
        <option value="franquicia">Franquicia</option>
        <option value="bodega">Bodega</option>
    </select>
    <select id="alm-activo" onchange="filtrarAlmacenes()"
            style="padding:8px 12px;border:1px solid #e5e7eb;border-radius:8px;font-size:.85rem;background:#fff;color:#374151;">
        <option value="">Todos</option>
        <option value="1">Activos</option>
        <option value="0">Inactivos</option>
    </select>
    <span id="alm-contador" style="font-size:.8rem;color:#9ca3af;margin-left:auto;"></span>
</div>

<!-- Tabla -->
<div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;">
    <table style="width:100%;border-collapse:collapse;font-size:.875rem;">
        <thead>
            <tr style="background:#f9fafb;border-bottom:1px solid #e5e7eb;">
                <th style="padding:11px 16px;text-align:left;font-weight:600;color:#6b7280;font-size:.78rem;text-transform:uppercase;letter-spacing:.04em;">Clave</th>
                <th style="padding:11px 16px;text-align:left;font-weight:600;color:#6b7280;font-size:.78rem;text-transform:uppercase;letter-spacing:.04em;">Tipo</th>
                <th style="padding:11px 16px;text-align:left;font-weight:600;color:#6b7280;font-size:.78rem;text-transform:uppercase;letter-spacing:.04em;">Franquiciatario</th>
                <th style="padding:11px 16px;text-align:left;font-weight:600;color:#6b7280;font-size:.78rem;text-transform:uppercase;letter-spacing:.04em;">Ubicación</th>
                <th style="padding:11px 16px;text-align:left;font-weight:600;color:#6b7280;font-size:.78rem;text-transform:uppercase;letter-spacing:.04em;">Estatus</th>
                <?php if (Session::isAdmin()): ?>
                <th style="padding:11px 16px;width:80px;"></th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody id="alm-tbody">
            <tr>
                <td colspan="6" style="text-align:center;padding:40px;color:#9ca3af;">
                    <i class="bi bi-arrow-clockwise"></i> Cargando...
                </td>
            </tr>
        </tbody>
    </table>
</div>

<!-- Modal -->
<?php if (Session::isAdmin()): ?>
<div id="modal-almacen" class="modal-backdrop" style="display:none;">
    <div class="modal-box">
        <div class="modal-header">
            <span id="modal-alm-titulo">Nuevo almacén</span>
            <button class="modal-close" onclick="cerrarModalAlmacen()">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <form id="form-almacen" onsubmit="guardarAlmacen(event)" style="padding-top:12px;">
            <input type="hidden" id="alm-id" value="">

            <div class="form-group">
                <label>Clave <span class="req">*</span></label>
                <input type="text" id="alm-clave" maxlength="20"
                       placeholder="Ej: CWO, FRQ001, PDC"
                       style="text-transform:uppercase;" required>
                <small style="color:#9ca3af;font-size:.78rem;">
                    Debe coincidir exactamente con la clave en cwofran
                </small>
            </div>

            <div class="form-group">
                <label>Tipo <span class="req">*</span></label>
                <select id="alm-tipo-form" required>
                    <option value="">— Selecciona —</option>
                    <option value="matriz">Matriz</option>
                    <option value="sucursal">Sucursal</option>
                    <option value="franquicia">Franquicia</option>
                    <option value="bodega">Bodega</option>
                </select>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="cerrarModalAlmacen()">
                    Cancelar
                </button>
                <button type="submit" class="btn-orange" id="btn-guardar-alm">
                    Guardar
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>