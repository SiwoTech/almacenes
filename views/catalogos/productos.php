<!-- ── Toolbar ─────────────────────────────────────────────── -->
<div class="toolbar" style="gap:10px; flex-wrap:wrap; justify-content:space-between;">

    <!-- Filtros -->
    <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">

        <select id="f-tipo" onchange="cargarProductos()" style="font-size:.82rem; padding:6px 10px; border:1px solid var(--border); border-radius:6px;">
			<option value="">Todos los tipos</option>
			<option value="materia_prima">Materia prima</option>
			<option value="producto_terminado">Producto terminado</option>
			<option value="refaccion">Refacción</option>
			<option value="maquina">Máquina</option>
			<option value="insumo">Insumo</option>
			<option value="uniforme">Uniforme</option>
			<option value="herramienta">Herramienta</option>
			<option value="envase">Envase</option>
			<option value="servicio">Servicio</option>
			<option value="franquicia">Franquicia</option>
			<option value="contable">Contable</option>
			<option value="otro">Otro</option>
		</select>

        <select id="f-linea" onchange="cargarProductos()" style="font-size:.82rem; padding:6px 10px; border:1px solid var(--border); border-radius:6px;">
            <option value="">Todas las líneas</option>
        </select>

        <select id="f-activo" onchange="cargarProductos()" style="font-size:.82rem; padding:6px 10px; border:1px solid var(--border); border-radius:6px;">
            <option value="1">Solo activos</option>
            <option value="0">Solo inactivos</option>
            <option value="todos">Todos</option>
        </select>

        <input type="search" id="f-buscar" placeholder="🔍 Buscar..." oninput="filtrarTabla()"
               style="font-size:.82rem; padding:6px 10px; border:1px solid var(--border); border-radius:6px; width:180px;">
    </div>

    <!-- Botones -->
    <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
        <a class="btn-secondary"  href="/almacenes/assets/plantillas/productos_plantilla.csv"
           download
           >
            📥 Plantilla
        </a>
        <button class="btn-secondary" onclick="abrirModalImportar()">
            📤 Importar CSV
        </button>
        <button class="btn-orange" onclick="abrirModalProducto()">
            ➕ Nuevo producto
        </button>
    </div>

</div>

<!-- ── Tabla ───────────────────────────────────────────────── -->
<div id="tabla-productos">
    <div class="spinner">Cargando...</div>
</div>

<!-- ══════════════════════════════════════════════════════════
     MODAL PRODUCTO
     ══════��═══════════════════════════════════════════════════ -->
<div class="modal-backdrop" id="modal-producto" style="display:none">
    <div class="modal-box" style="width:620px; max-height:90vh; overflow-y:auto;">

        <div class="modal-header">
            <span id="modal-producto-titulo">Nuevo producto</span>
            <button class="modal-close" onclick="cerrarModalProducto()">✕</button>
        </div>

        <form id="form-producto" onsubmit="guardarProducto(event)">
            <input type="hidden" id="p-id" value="0">

            <!-- ── Sección: Identificación ── -->
            <div class="form-section">Identificación</div>

            <div class="form-row">
                <div class="form-group">
                    <label>Código <span class="req">*</span></label>
                    <input type="text" id="p-codigo" maxlength="50"
                           placeholder="ej: MP-001" required>
                </div>
                <div class="form-group">
                    <label>Tipo <span class="req">*</span></label>
                    <select id="p-tipo" required onchange="onTipoChange()">
						<option value="">-- Selecciona --</option>
						<option value="materia_prima">Materia prima</option>
						<option value="producto_terminado">Producto terminado</option>
						<option value="refaccion">Refacción</option>
						<option value="maquina">Máquina</option>
						<option value="insumo">Insumo</option>
						<option value="uniforme">Uniforme</option>
						<option value="herramienta">Herramienta</option>
						<option value="envase">Envase</option>
						<option value="servicio">Servicio</option>
						<option value="franquicia">Franquicia</option>
						<option value="contable">Contable</option>
						<option value="otro">Otro</option>
					</select>
                </div>
            </div>

            <div class="form-group">
                <label>Nombre <span class="req">*</span></label>
                <input type="text" id="p-nombre" maxlength="200"
                       placeholder="Nombre del producto" required>
            </div>

            <div class="form-group">
                <label>Descripción</label>
                <textarea id="p-descripcion" rows="2"
                          placeholder="Descripción opcional..."></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Línea</label>
                    <select id="p-linea"></select>
                </div>
                <div class="form-group">
                    <label>Unidad de medida</label>
                    <select id="p-unidad"></select>
                </div>
            </div>

            <!-- ── Sección: Costos y finanzas ── -->
            <div class="form-section">Costos y finanzas</div>

            <div class="form-row">
                <div class="form-group">
                    <label>Costo base</label>
                    <input type="number" id="p-costo-base" min="0"
                           step="0.0001" placeholder="0.0000">
                </div>
                <div class="form-group">
                    <label>Tipo de costeo</label>
                    <select id="p-tcosteo"></select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Esquema de impuesto</label>
                    <select id="p-impuesto"></select>
                </div>
                <div class="form-group">
                    <label>Moneda</label>
                    <select id="p-moneda"></select>
                </div>
            </div>

            <!-- ── Sección: Inventario ── -->
            <div class="form-section">Inventario</div>

            <div class="form-row">
                <div class="form-group">
                    <label>Stock mínimo</label>
                    <input type="number" id="p-stock-min" min="0"
                           step="0.0001" placeholder="0.0000">
                </div>
                <div class="form-group">
                    <label>Stock máximo</label>
                    <input type="number" id="p-stock-max" min="0"
                           step="0.0001" placeholder="0.0000">
                </div>
            </div>

            <!-- ── Sección: SAT ── -->
            <div class="form-section">Datos SAT</div>

            <div class="form-row">
                <div class="form-group">
                    <label>Código SAT</label>
                    <input type="text" id="p-codigo-sat" maxlength="20"
                           placeholder="ej: 50202300">
                </div>
                <div class="form-group">
                    <label>Unidad SAT</label>
                    <input type="text" id="p-unidad-sat" maxlength="20"
                           placeholder="ej: KGM, H87">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Código de barras</label>
                    <input type="text" id="p-barras" maxlength="100"
                           placeholder="EAN13 / QR">
                </div>
                <div class="form-group">
                    <label>Peso (kg)</label>
                    <input type="number" id="p-peso" min="0"
                           step="0.0001" placeholder="0.0000">
                </div>
            </div>

            <!-- ── Sección: Opciones ── -->
            <div class="form-section">Opciones</div>

            <div style="padding: 0 20px 16px; display:flex; flex-wrap:wrap; gap:16px;">
                <label class="chk-label">
                    <input type="checkbox" id="p-ctrl-almacen" checked>
                    Control de almacén
                </label>
                <label class="chk-label">
                    <input type="checkbox" id="p-manufactura">
                    Es manufactura
                </label>
                <label class="chk-label">
                    <input type="checkbox" id="p-solo-matriz">
                    Solo matriz (CWO)
                </label>
                <label class="chk-label">
                    <input type="checkbox" id="p-lote">
                    Requiere lote
                </label>
                <label class="chk-label">
                    <input type="checkbox" id="p-serie">
                    Requiere serie
                </label>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-cancel"
                        onclick="cerrarModalProducto()">Cancelar</button>
                <button type="submit" class="btn-orange">Guardar</button>
            </div>
        </form>
    </div>
</div>

<!-- ══ MODAL IMPORTAR ══════════════════════════════════════ -->
<div class="modal-backdrop" id="modal-importar" style="display:none">
    <div class="modal-box" style="width:720px; max-height:90vh; overflow-y:auto;">

        <div class="modal-header">
            <span>📤 Importar productos desde CSV</span>
            <button class="modal-close" onclick="cerrarModalImportar()">✕</button>
        </div>

        <!-- Paso 1: subir -->
        <div id="imp-paso1" style="padding:20px;">
            <div class="alert alert-info" style="margin-bottom:16px;">
                ℹ️ El CSV debe tener la misma estructura que la plantilla.
                Las columnas <strong>codigo</strong>, <strong>nombre</strong>
                y <strong>tipo</strong> son obligatorias.
            </div>

            <div class="form-group" style="padding:0;">
                <label>Selecciona el archivo CSV <span class="req">*</span></label>
                <input type="file" id="imp-archivo" accept=".csv">
            </div>

            <div style="margin-top:16px; display:flex; justify-content:flex-end; gap:10px;">
                <button class="btn-cancel" onclick="cerrarModalImportar()">Cancelar</button>
                <button class="btn-orange" onclick="previsualizarCSV()">
                    Previsualizar →
                </button>
            </div>
        </div>

        <!-- Paso 2: previsualizar -->
        <div id="imp-paso2" style="display:none; padding:20px;">
            <div id="imp-resumen"></div>
            <div id="imp-tabla" style="margin-top:12px; max-height:340px; overflow-y:auto;"></div>

            <div style="margin-top:16px; display:flex; justify-content:flex-end; gap:10px;">
                <button class="btn-cancel" onclick="volverPaso1()">← Volver</button>
                <button class="btn-orange" id="btn-confirmar" onclick="confirmarImportacion()">
                    ✅ Confirmar importación
                </button>
            </div>
        </div>

        <!-- Paso 3: resultado -->
        <div id="imp-paso3" style="display:none; padding:20px;">
            <div id="imp-resultado"></div>
            <div style="margin-top:16px; display:flex; justify-content:flex-end;">
                <button class="btn-orange" onclick="cerrarModalImportar()">Cerrar</button>
            </div>
        </div>

    </div>
</div>