<?php
// Guard — solo CWO admin
if (!Session::isCWO() || !Session::isAdmin()) {
    Session::forbidden();
}
?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
    <div>
        <h2 style="font-size:1rem;font-weight:700;color:#111;margin:0;">
            Carga Inicial de Inventario
        </h2>
        <p style="font-size:.8rem;color:#9ca3af;margin:3px 0 0;">
            Registra las existencias iniciales por almacén. Solo CWO Admin.
        </p>
    </div>
</div>

<div class="alert alert-info" style="margin-bottom:20px;">
    <i class="bi bi-info-circle"></i>
    Selecciona un almacén, captura las existencias de cada producto y guarda.
    Si el producto ya tiene registro, se actualizará.
</div>

<!-- Selector almacén -->
<div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:20px;margin-bottom:20px;">
    <div style="display:flex;gap:16px;align-items:flex-end;flex-wrap:wrap;">
        <div style="flex:1;min-width:200px;max-width:300px;">
            <label style="font-size:.8rem;font-weight:600;color:#6b7280;display:block;margin-bottom:5px;">
                Almacén <span class="req">*</span>
            </label>
            <select id="ini-almacen"
                    style="width:100%;padding:8px 12px;border:1px solid #e5e7eb;border-radius:8px;font-size:.875rem;">
                <option value="">— Selecciona almacén —</option>
            </select>
        </div>
        <div style="flex:1;min-width:200px;max-width:280px;">
            <label style="font-size:.8rem;font-weight:600;color:#6b7280;display:block;margin-bottom:5px;">
                Buscar producto
            </label>
            <div style="position:relative;">
                <i class="bi bi-search" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#9ca3af;font-size:.85rem;"></i>
                <input id="ini-buscar" type="text" placeholder="Código o nombre..."
                       oninput="filtrarIniciales()"
                       style="width:100%;padding:8px 10px 8px 32px;border:1px solid #e5e7eb;border-radius:8px;font-size:.85rem;">
            </div>
        </div>
        <button class="btn-orange" onclick="cargarProductosIniciales()"
                style="display:flex;align-items:center;gap:6px;font-size:.85rem;">
            <i class="bi bi-arrow-repeat"></i> Cargar productos
        </button>
    </div>
</div>

<!-- Tabla captura -->
<div id="ini-contenedor" style="display:none;">
    <div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;">
        <div style="padding:14px 16px;border-bottom:1px solid #e5e7eb;
                    display:flex;justify-content:space-between;align-items:center;">
            <span id="ini-subtitulo" style="font-size:.875rem;font-weight:600;color:#374151;"></span>
            <div style="display:flex;gap:8px;">
                <button class="btn-secondary" onclick="limpiarIniciales()" style="font-size:.85rem;">
                    <i class="bi bi-x-circle"></i> Limpiar
                </button>
                <button class="btn-orange" onclick="guardarIniciales()"
                        id="btn-guardar-ini"
                        style="display:flex;align-items:center;gap:6px;font-size:.85rem;">
                    <i class="bi bi-check-lg"></i> Guardar inventario
                </button>
            </div>
        </div>

        <table style="width:100%;border-collapse:collapse;font-size:.875rem;">
            <thead>
                <tr style="background:#f9fafb;border-bottom:1px solid #e5e7eb;">
                    <th style="padding:10px 16px;text-align:left;font-weight:600;color:#6b7280;font-size:.78rem;text-transform:uppercase;letter-spacing:.04em;">Código</th>
                    <th style="padding:10px 16px;text-align:left;font-weight:600;color:#6b7280;font-size:.78rem;text-transform:uppercase;letter-spacing:.04em;">Producto</th>
                    <th style="padding:10px 16px;text-align:left;font-weight:600;color:#6b7280;font-size:.78rem;text-transform:uppercase;letter-spacing:.04em;">Tipo</th>
                    <th style="padding:10px 16px;text-align:left;font-weight:600;color:#6b7280;font-size:.78rem;text-transform:uppercase;letter-spacing:.04em;">Unidad</th>
                    <th style="padding:10px 16px;text-align:right;font-weight:600;color:#6b7280;font-size:.78rem;text-transform:uppercase;letter-spacing:.04em;min-width:130px;">Existencias</th>
                    <th style="padding:10px 16px;text-align:right;font-weight:600;color:#6b7280;font-size:.78rem;text-transform:uppercase;letter-spacing:.04em;min-width:110px;">Stock mín</th>
                    <th style="padding:10px 16px;text-align:right;font-weight:600;color:#6b7280;font-size:.78rem;text-transform:uppercase;letter-spacing:.04em;min-width:110px;">Stock máx</th>
                    <th style="padding:10px 16px;text-align:left;font-weight:600;color:#6b7280;font-size:.78rem;text-transform:uppercase;letter-spacing:.04em;">Estado</th>
                </tr>
            </thead>
            <tbody id="ini-tbody"></tbody>
        </table>
    </div>
</div>