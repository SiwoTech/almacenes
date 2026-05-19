<?php Session::requireAny(); ?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
    <div>
        <h2 style="font-size:1rem; font-weight:700; color:#111; margin:0;">
            Stock Actual
        </h2>
        <p style="font-size:.8rem; color:#9ca3af; margin:3px 0 0;">
            Inventario en tiempo real por almacén
        </p>
    </div>
    <button class="btn-orange" onclick="exportarStock()"
            style="display:flex;align-items:center;gap:6px;font-size:.85rem;">
        <i class="bi bi-download"></i> Exportar
    </button>
</div>

<!-- Filtros -->
<div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-bottom:18px;">
    <div style="position:relative;flex:1;min-width:200px;max-width:280px;">
        <i class="bi bi-search" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#9ca3af;font-size:.85rem;"></i>
        <input id="inv-buscar" type="text" placeholder="Buscar código o producto..."
               oninput="filtrarStock()"
               style="width:100%;padding:8px 10px 8px 32px;border:1px solid #e5e7eb;
                      border-radius:8px;font-size:.85rem;">
    </div>

    <select id="inv-almacen" onchange="filtrarStock()"
            style="padding:8px 12px;border:1px solid #e5e7eb;border-radius:8px;font-size:.85rem;">
        <option value="">Todos los almacenes</option>
    </select>

    <select id="inv-tipo" onchange="filtrarStock()"
            style="padding:8px 12px;border:1px solid #e5e7eb;border-radius:8px;font-size:.85rem;">
        <option value="">Todos los tipos</option>
        <option value="materia_prima">Materia prima</option>
        <option value="producto_terminado">Producto terminado</option>
        <option value="insumo">Insumo</option>
        <option value="envase">Envase</option>
        <option value="uniforme">Uniforme</option>
        <option value="herramienta">Herramienta</option>
        <option value="refaccion">Refacción</option>
        <option value="otro">Otro</option>
    </select>

    <select id="inv-estado" onchange="filtrarStock()"
            style="padding:8px 12px;border:1px solid #e5e7eb;border-radius:8px;font-size:.85rem;">
        <option value="">Todos los estados</option>
        <option value="sin_stock">Sin stock</option>
        <option value="stock_bajo">Stock bajo</option>
        <option value="stock_ok">Stock OK</option>
        <option value="stock_alto">Stock alto</option>
    </select>

    <span id="inv-contador" style="font-size:.8rem;color:#9ca3af;margin-left:auto;"></span>
</div>

<!-- KPIs rápidos -->
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:20px;"
     id="inv-kpis">
</div>

<!-- Tabla -->
<div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;">
    <table style="width:100%;border-collapse:collapse;font-size:.875rem;">
        <thead>
            <tr style="background:#f9fafb;border-bottom:1px solid #e5e7eb;">
                <th style="padding:11px 16px;text-align:left;font-weight:600;color:#6b7280;font-size:.78rem;text-transform:uppercase;letter-spacing:.04em;">Código</th>
                <th style="padding:11px 16px;text-align:left;font-weight:600;color:#6b7280;font-size:.78rem;text-transform:uppercase;letter-spacing:.04em;">Producto</th>
                <th style="padding:11px 16px;text-align:left;font-weight:600;color:#6b7280;font-size:.78rem;text-transform:uppercase;letter-spacing:.04em;">Almacén</th>
                <th style="padding:11px 16px;text-align:left;font-weight:600;color:#6b7280;font-size:.78rem;text-transform:uppercase;letter-spacing:.04em;">Tipo</th>
                <th style="padding:11px 16px;text-align:right;font-weight:600;color:#6b7280;font-size:.78rem;text-transform:uppercase;letter-spacing:.04em;">Existencias</th>
                <th style="padding:11px 16px;text-align:right;font-weight:600;color:#6b7280;font-size:.78rem;text-transform:uppercase;letter-spacing:.04em;">Mín</th>
                <th style="padding:11px 16px;text-align:right;font-weight:600;color:#6b7280;font-size:.78rem;text-transform:uppercase;letter-spacing:.04em;">Máx</th>
                <th style="padding:11px 16px;text-align:left;font-weight:600;color:#6b7280;font-size:.78rem;text-transform:uppercase;letter-spacing:.04em;">Estado</th>
                <th style="padding:11px 16px;text-align:left;font-weight:600;color:#6b7280;font-size:.78rem;text-transform:uppercase;letter-spacing:.04em;">Actualizado</th>
            </tr>
        </thead>
        <tbody id="inv-tbody">
            <tr>
                <td colspan="9" style="text-align:center;padding:40px;color:#9ca3af;">
                    <i class="bi bi-arrow-clockwise"></i> Cargando...
                </td>
            </tr>
        </tbody>
    </table>
</div>