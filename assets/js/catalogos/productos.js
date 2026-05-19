// ── Estado global ──────────────────────────────────────────
let _catalogos = null;
let _filasTodas = [];

// ── Inicialización ─────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    cargarCatalogosForm().then(() => {
        cargarLineasFiltro();
        cargarProductos();
    });
});

// ── Cargar catálogos para selects del modal ────────────────
function cargarCatalogosForm() {
    return cwoFetch('/almacenes/api/catalogos/productos.php?action=catalogos')
        .then(res => {
            if (!res.ok) return;
            _catalogos = res.data;
            llenarSelect('p-linea',    _catalogos.lineas,    'id', 'nombre',  '-- Sin línea --');
            llenarSelect('p-unidad',   _catalogos.unidades,  'id', 'abrev',   '-- Sin unidad --');
            llenarSelect('p-tcosteo',  _catalogos.tcosteo,   'id', 'nombre',  '-- Sin tipo --');
            llenarSelect('p-impuesto', _catalogos.impuestos, 'id', 'nombre',  '-- Sin esquema --');
            llenarSelect('p-moneda',   _catalogos.monedas,   'id', 'nombre',  '-- Sin moneda --');
        });
}

// ── Llenar filtro de líneas ────────────────────────────────
function cargarLineasFiltro() {
    if (!_catalogos) return;
    const sel = document.getElementById('f-linea');
    _catalogos.lineas.forEach(l => {
        const o = document.createElement('option');
        o.value = l.id;
        o.textContent = l.nombre;
        sel.appendChild(o);
    });
}

// ── Cargar productos desde API ─────────────────────────────
function cargarProductos() {
    const tipo   = document.getElementById('f-tipo').value;
    const linea  = document.getElementById('f-linea').value;
    const activo = document.getElementById('f-activo').value;

    const params = new URLSearchParams({ action: 'listar' });
    if (tipo)  params.append('tipo',   tipo);
    if (linea) params.append('linea',  linea);
    params.append('activo', activo);

    document.getElementById('tabla-productos').innerHTML =
        '<div class="spinner">Cargando...</div>';

    cwoFetch('/almacenes/api/catalogos/productos.php?' + params.toString())
        .then(res => {
            if (!res.ok) { cwoError('tabla-productos', res.message); return; }
            _filasTodas = res.data;
            filtrarTabla();
        })
        .catch(() => cwoError('tabla-productos', 'Error de conexión'));
}

// ── Filtro local por búsqueda ──────────────────────────────
function filtrarTabla() {
    const q = (document.getElementById('f-buscar').value ?? '').toLowerCase();
    const filas = q
        ? _filasTodas.filter(r =>
            r.codigo.toLowerCase().includes(q) ||
            r.nombre.toLowerCase().includes(q) ||
            (r.linea  ?? '').toLowerCase().includes(q) ||
            (r.unidad ?? '').toLowerCase().includes(q))
        : _filasTodas;
    renderTablaProductos(filas);
}

// ── Render tabla ───────────────────────────────────────────
const TIPO_BADGE = {
    materia_prima:      ['badge-mp',   'Mat. Prima'],
    producto_terminado: ['badge-pt',   'Prod. Term.'],
    refaccion:          ['badge-ref',  'Refacción'],
    maquina:            ['badge-maq',  'Máquina'],
    insumo:             ['badge-ins',  'Insumo'],
    uniforme:           ['badge-uni',  'Uniforme'],
    herramienta:        ['badge-her',  'Herramienta'],
    envase:             ['badge-env',  'Envase'],
    servicio:           ['badge-ser',  'Servicio'],
    franquicia:         ['badge-fra',  'Franquicia'],
    contable:           ['badge-con',  'Contable'],
    otro:               ['badge-otro', 'Otro'],
};

function renderTablaProductos(rows) {
    const el = document.getElementById('tabla-productos');

    if (!rows || !rows.length) {
        el.innerHTML = `<div class="alert alert-warning">
            Sin productos encontrados.</div>`;
        return;
    }

    const filas = rows.map(r => {
        const [badgeCls, badgeTxt] = TIPO_BADGE[r.tipo] ?? ['badge-otro', r.tipo];
        const costo = r.costo_base > 0
            ? `$${parseFloat(r.costo_base).toFixed(2)}`
            : '<span style="color:#bbb">—</span>';
        const iconos = [];
        if (r.ctrl_almacen  == 1) iconos.push('<span title="Control almacén">📦</span>');
        if (r.es_manufactura == 1) iconos.push('<span title="Manufactura">🏭</span>');
        if (r.requiere_lote  == 1) iconos.push('<span title="Requiere lote">🏷️</span>');
        if (r.requiere_serie == 1) iconos.push('<span title="Requiere serie">🔢</span>');
        if (r.solo_matriz    == 1) iconos.push('<span title="Solo matriz">🏢</span>');

        return `
        <tr class="${r.activo == 1 ? '' : 'row-inactivo'}">
            <td><code style="font-size:.8rem">${r.codigo}</code></td>
            <td>
                <strong>${r.nombre}</strong>
                ${r.linea ? `<br><small style="color:#999">${r.linea}</small>` : ''}
            </td>
            <td><span class="badge ${badgeCls}">${badgeTxt}</span></td>
            <td>${r.unidad ?? '<span style="color:#bbb">—</span>'}</td>
            <td class="num">${costo}</td>
            <td style="font-size:1rem; letter-spacing:2px">${iconos.join('')}</td>
            <td>
                <span class="badge ${r.activo == 1 ? 'badge-ok' : 'badge-sin'}">
                    ${r.activo == 1 ? 'Activo' : 'Inactivo'}
                </span>
            </td>
            <td class="td-acciones">
                <button class="btn-icon" title="Editar"
                        onclick="editarProducto(${r.id})">✏️</button>
                <button class="btn-icon" title="${r.activo == 1 ? 'Desactivar' : 'Activar'}"
                        onclick="toggleProducto(${r.id})">
                    ${r.activo == 1 ? '🔴' : '🟢'}
                </button>
            </td>
        </tr>`;
    }).join('');

    el.innerHTML = `
        <div style="font-size:.8rem; color:#999; margin-bottom:8px;">
            ${rows.length} producto(s) encontrado(s)
        </div>
        <table class="dashboard-table">
            <thead><tr>
                <th>Código</th>
                <th>Nombre / Línea</th>
                <th>Tipo</th>
                <th>Unidad</th>
                <th class="num">Costo base</th>
                <th>Flags</th>
                <th>Estatus</th>
                <th>Acciones</th>
            </tr></thead>
            <tbody>${filas}</tbody>
        </table>`;
}

// ── Modal: abrir nuevo ─────────────────────────────────────
function abrirModalProducto() {
    document.getElementById('modal-producto-titulo').textContent = 'Nuevo producto';
    document.getElementById('p-id').value            = '0';
    document.getElementById('p-codigo').value        = '';
    document.getElementById('p-nombre').value        = '';
    document.getElementById('p-descripcion').value   = '';
    document.getElementById('p-tipo').value          = '';
    document.getElementById('p-linea').value         = '';
    document.getElementById('p-unidad').value        = '';
    document.getElementById('p-tcosteo').value       = '';
    document.getElementById('p-impuesto').value      = '';
    document.getElementById('p-moneda').value        = '';
    document.getElementById('p-costo-base').value    = '';
    document.getElementById('p-stock-min').value     = '';
    document.getElementById('p-stock-max').value     = '';
    document.getElementById('p-codigo-sat').value    = '';
    document.getElementById('p-unidad-sat').value    = '';
    document.getElementById('p-barras').value        = '';
    document.getElementById('p-peso').value          = '';
    document.getElementById('p-ctrl-almacen').checked = true;
    document.getElementById('p-manufactura').checked  = false;
    document.getElementById('p-solo-matriz').checked  = false;
    document.getElementById('p-lote').checked         = false;
    document.getElementById('p-serie').checked        = false;
    document.getElementById('modal-producto').style.display = 'flex';
    document.getElementById('p-codigo').focus();
}

// ── Modal: editar ──────────────────────────────────────────
function editarProducto(id) {
    cwoFetch(`/almacenes/api/catalogos/productos.php?action=detalle&id=${id}`)
        .then(res => {
            if (!res.ok) { alert('❌ ' + res.message); return; }
            const r = res.data;
            document.getElementById('modal-producto-titulo').textContent = 'Editar producto';
            document.getElementById('p-id').value            = r.id;
            document.getElementById('p-codigo').value        = r.codigo;
            document.getElementById('p-nombre').value        = r.nombre;
            document.getElementById('p-descripcion').value   = r.descripcion   ?? '';
            document.getElementById('p-tipo').value          = r.tipo;
            document.getElementById('p-linea').value         = r.linea_id      ?? '';
            document.getElementById('p-unidad').value        = r.unidad_medida_id ?? '';
            document.getElementById('p-tcosteo').value       = r.tipo_costeo_id   ?? '';
            document.getElementById('p-impuesto').value      = r.esquema_impuesto_id ?? '';
            document.getElementById('p-moneda').value        = r.moneda_id      ?? '';
            document.getElementById('p-costo-base').value    = r.costo_base     ?? '';
            document.getElementById('p-stock-min').value     = r.stock_minimo   ?? '';
            document.getElementById('p-stock-max').value     = r.stock_maximo   ?? '';
            document.getElementById('p-codigo-sat').value    = r.codigo_sat     ?? '';
            document.getElementById('p-unidad-sat').value    = r.unidad_sat     ?? '';
            document.getElementById('p-barras').value        = r.codigo_barras  ?? '';
            document.getElementById('p-peso').value          = r.peso_kg        ?? '';
            document.getElementById('p-ctrl-almacen').checked = r.ctrl_almacen  == 1;
            document.getElementById('p-manufactura').checked  = r.es_manufactura == 1;
            document.getElementById('p-solo-matriz').checked  = r.solo_matriz   == 1;
            document.getElementById('p-lote').checked         = r.requiere_lote == 1;
            document.getElementById('p-serie').checked        = r.requiere_serie == 1;
            document.getElementById('modal-producto').style.display = 'flex';
            document.getElementById('p-codigo').focus();
        })
        .catch(() => alert('❌ Error de conexión'));
}

function cerrarModalProducto() {
    document.getElementById('modal-producto').style.display = 'none';
}

document.addEventListener('click', e => {
    if (e.target.id === 'modal-producto') cerrarModalProducto();
});

// ── Guardar ────────────────────────────────────────────────
function guardarProducto(e) {
    e.preventDefault();
    const body = new FormData();
    body.append('id',                  document.getElementById('p-id').value);
    body.append('codigo',              document.getElementById('p-codigo').value.trim());
    body.append('nombre',              document.getElementById('p-nombre').value.trim());
    body.append('descripcion',         document.getElementById('p-descripcion').value.trim());
    body.append('tipo',                document.getElementById('p-tipo').value);
    body.append('linea_id',            document.getElementById('p-linea').value);
    body.append('unidad_medida_id',    document.getElementById('p-unidad').value);
    body.append('tipo_costeo_id',      document.getElementById('p-tcosteo').value);
    body.append('esquema_impuesto_id', document.getElementById('p-impuesto').value);
    body.append('moneda_id',           document.getElementById('p-moneda').value);
    body.append('costo_base',          document.getElementById('p-costo-base').value || '0');
    body.append('stock_minimo',        document.getElementById('p-stock-min').value  || '0');
    body.append('stock_maximo',        document.getElementById('p-stock-max').value  || '0');
    body.append('codigo_sat',          document.getElementById('p-codigo-sat').value.trim());
    body.append('unidad_sat',          document.getElementById('p-unidad-sat').value.trim());
    body.append('codigo_barras',       document.getElementById('p-barras').value.trim());
    body.append('peso_kg',             document.getElementById('p-peso').value);

    // Checkboxes — solo se envían si están marcados
    if (document.getElementById('p-ctrl-almacen').checked) body.append('ctrl_almacen',  '1');
    if (document.getElementById('p-manufactura').checked)  body.append('es_manufactura','1');
    if (document.getElementById('p-solo-matriz').checked)  body.append('solo_matriz',   '1');
    if (document.getElementById('p-lote').checked)         body.append('requiere_lote', '1');
    if (document.getElementById('p-serie').checked)        body.append('requiere_serie','1');

    cwoFetch('/almacenes/api/catalogos/productos.php?action=guardar', {
        method: 'POST', body
    }).then(res => {
        if (!res.ok) { alert('❌ ' + res.message); return; }
        cerrarModalProducto();
        cargarProductos();
    }).catch(() => alert('❌ Error de conexión'));
}

// ── Toggle activo ──────────────────────────────────────────
function toggleProducto(id) {
    if (!confirm('¿Cambiar estatus de este producto?')) return;
    const body = new FormData();
    body.append('id', id);

    cwoFetch('/almacenes/api/catalogos/productos.php?action=toggle', {
        method: 'POST', body
    }).then(res => {
        if (!res.ok) { alert('❌ ' + res.message); return; }
        cargarProductos();
    }).catch(() => alert('❌ Error de conexión'));
}

// ── Helpers ────────────────────────────────────────────────
function llenarSelect(elId, items, valKey, txtKey, placeholder = '-- Todos --') {
    const sel = document.getElementById(elId);
    sel.innerHTML = `<option value="">${placeholder}</option>`;
    (items ?? []).forEach(item => {
        const o = document.createElement('option');
        o.value = item[valKey];
        o.textContent = item[txtKey];
        sel.appendChild(o);
    });
}

function onTipoChange() {
    // Futura lógica: si tipo = producto_terminado, mostrar campo receta, etc.
}

function esc(str) {
    return String(str).replace(/'/g, "\\'");
}
// ══════════════════════════════════════════════════════════
// IMPORTADOR CSV
// ══════════════════════════════════════════════════════════

let _filasCSV = [];

function abrirModalImportar() {
    volverPaso1();
    document.getElementById('imp-archivo').value = '';
    document.getElementById('modal-importar').style.display = 'flex';
}

function cerrarModalImportar() {
    document.getElementById('modal-importar').style.display = 'none';
}

document.addEventListener('click', e => {
    if (e.target.id === 'modal-importar') cerrarModalImportar();
});

function volverPaso1() {
    document.getElementById('imp-paso1').style.display = 'block';
    document.getElementById('imp-paso2').style.display = 'none';
    document.getElementById('imp-paso3').style.display = 'none';
}

// ── Paso 1 → Previsualizar ─────────────────────────────────
function previsualizarCSV() {
    const archivo = document.getElementById('imp-archivo').files[0];
    if (!archivo) { alert('Selecciona un archivo CSV.'); return; }

    const body = new FormData();
    body.append('archivo', archivo);

    const btn = document.querySelector('#imp-paso1 .btn-orange');
    btn.textContent = 'Procesando...';
    btn.disabled = true;

    cwoFetch('/almacenes/api/catalogos/importar_productos.php?action=previsualizar', {
        method: 'POST', body
    }).then(res => {
        btn.textContent = 'Previsualizar →';
        btn.disabled = false;

        if (!res.ok) { alert('❌ ' + res.message); return; }

        _filasCSV = res.data.filas;
        renderPrevisualizacion(res.data);

        document.getElementById('imp-paso1').style.display = 'none';
        document.getElementById('imp-paso2').style.display = 'block';

    }).catch(() => {
        btn.textContent = 'Previsualizar →';
        btn.disabled = false;
        alert('❌ Error de conexión');
    });
}

function renderPrevisualizacion(data) {
    // Resumen
    const hayErrores = data.errores.length > 0;
    document.getElementById('imp-resumen').innerHTML = `
        <div style="display:flex; gap:12px; flex-wrap:wrap; margin-bottom:12px;">
            <div class="kpi-mini">
                <span class="kpi-num">${data.total}</span>
                <span class="kpi-lbl">Total filas</span>
            </div>
            <div class="kpi-mini" style="--kpi-color:#16a34a">
                <span class="kpi-num" style="color:#16a34a">${data.validas}</span>
                <span class="kpi-lbl">Válidas</span>
            </div>
            <div class="kpi-mini" style="--kpi-color:#dc2626">
                <span class="kpi-num" style="color:#dc2626">${data.total - data.validas}</span>
                <span class="kpi-lbl">Con errores</span>
            </div>
        </div>
        ${hayErrores ? `
        <div class="alert" style="background:#fef2f2; border:1px solid #fecaca; color:#991b1b;
             border-radius:8px; padding:10px 14px; font-size:.82rem; margin-bottom:10px;">
            ⚠️ Las filas con errores <strong>no se importarán</strong>.<br>
            ${data.errores.map(e => `• ${e}`).join('<br>')}
        </div>` : ''}
        ${data.validas === 0 ? `
        <div class="alert alert-warning">
            ❌ No hay filas válidas para importar. Corrige el CSV y vuelve a intentar.
        </div>` : ''}
    `;

    // Deshabilitar botón si no hay válidas
    document.getElementById('btn-confirmar').disabled = data.validas === 0;

    // Tabla previa
    const cols = ['codigo','nombre','tipo','linea','unidad','costo_base'];
    const filas = data.filas.map(f => `
        <tr class="${f.ok ? 'imp-fila-ok' : 'imp-fila-error'}">
            <td style="font-size:.75rem; color:#999">${f.num}</td>
            <td>${f.ok ? '✅' : '❌'}</td>
            ${cols.map(c => `<td style="font-size:.8rem">${f.data[c] ?? ''}</td>`).join('')}
            <td style="font-size:.75rem; color:#dc2626">${f.errores.join(', ')}</td>
        </tr>`).join('');

    document.getElementById('imp-tabla').innerHTML = `
        <table class="dashboard-table" style="font-size:.82rem;">
            <thead><tr>
                <th>#</th><th></th>
                ${cols.map(c => `<th>${c}</th>`).join('')}
                <th>Errores</th>
            </tr></thead>
            <tbody>${filas}</tbody>
        </table>`;
}

// ── Paso 2 → Confirmar ─────────────────────────────────────
function confirmarImportacion() {
    const filasValidas = _filasCSV
        .filter(f => f.ok)
        .map(f => f.data);

    if (!filasValidas.length) { alert('No hay filas válidas.'); return; }

    const btn    = document.getElementById('btn-confirmar');
    const total  = filasValidas.length;

    // ── Ocultar paso 2 y mostrar progreso ──────────────────
    document.getElementById('imp-paso2').style.display = 'none';
    document.getElementById('imp-paso3').style.display = 'block';
    document.getElementById('imp-resultado').innerHTML  = _buildProgreso(0, total);

    btn.textContent = 'Importando...';
    btn.disabled    = true;

    // ── Animación de progreso simulada mientras espera ─────
    let progreso  = 0;
    const maxFake = 85; // llega hasta 85% mientras espera respuesta
    const interval = setInterval(() => {
        if (progreso < maxFake) {
            // avanza más rápido al inicio, más lento al final
            progreso += Math.random() * (progreso < 50 ? 4 : 1.5);
            progreso  = Math.min(progreso, maxFake);
            _updateProgreso(Math.round(progreso), total);
        }
    }, 120);

    // ── Request ────────────────────────────────────────────
    const body = new FormData();
    body.append('filas', JSON.stringify(filasValidas));

    cwoFetch('/almacenes/api/catalogos/importar_productos.php?action=confirmar', {
        method: 'POST', body
    }).then(res => {
        clearInterval(interval);
        _updateProgreso(100, total);

        setTimeout(() => {
            btn.textContent = '✅ Confirmar importación';
            btn.disabled    = false;

            const color = res.ok ? '#16a34a' : '#dc2626';
            const icon  = res.ok ? '✅' : '❌';
            const ins   = res.data?.insertados ?? 0;
            const omi   = res.data?.omitidos   ?? 0;
            const errs  = res.data?.errores     ?? [];

            let html = `
                <div style="text-align:center; padding:20px 0;">
                    <div style="font-size:3rem;">${icon}</div>
                    <div style="font-size:1.1rem; font-weight:700;
                                color:${color}; margin:10px 0;">
                        ${res.message}
                    </div>
                </div>

                <!-- Resumen de conteos -->
                <div style="display:flex; gap:12px; justify-content:center;
                            margin-bottom:16px; flex-wrap:wrap;">
                    <div style="background:#f0fdf4; border:1px solid #bbf7d0;
                                border-radius:8px; padding:10px 20px; text-align:center;">
                        <div style="font-size:1.5rem; font-weight:700;
                                    color:#16a34a;">${ins}</div>
                        <div style="font-size:.78rem; color:#166534;">Insertados</div>
                    </div>
                    <div style="background:#fffbeb; border:1px solid #fde68a;
                                border-radius:8px; padding:10px 20px; text-align:center;">
                        <div style="font-size:1.5rem; font-weight:700;
                                    color:#d97706;">${omi}</div>
                        <div style="font-size:.78rem; color:#92400e;">Omitidos</div>
                    </div>
                    <div style="background:#fef2f2; border:1px solid #fecaca;
                                border-radius:8px; padding:10px 20px; text-align:center;">
                        <div style="font-size:1.5rem; font-weight:700;
                                    color:#dc2626;">${errs.length}</div>
                        <div style="font-size:.78rem; color:#991b1b;">Errores</div>
                    </div>
                </div>`;

            if (errs.length) {
                html += `
                    <div style="background:#fef2f2; border:1px solid #fecaca;
                                color:#991b1b; border-radius:8px; padding:10px 14px;
                                font-size:.82rem; max-height:200px; overflow-y:auto;">
                        ${errs.map(e => `• ${e}`).join('<br>')}
                    </div>`;
            }

            document.getElementById('imp-resultado').innerHTML = html;
            if (res.ok) cargarProductos();

        }, 400); // pequeña pausa para que se vea el 100%

    }).catch(() => {
        clearInterval(interval);
        btn.textContent = '✅ Confirmar importación';
        btn.disabled    = false;
        document.getElementById('imp-resultado').innerHTML = `
            <div style="text-align:center; padding:30px 0;">
                <div style="font-size:3rem;">❌</div>
                <div style="font-size:1rem; font-weight:700;
                            color:#dc2626; margin-top:10px;">
                    Error de conexión. Intenta de nuevo.
                </div>
            </div>`;
    });
}

// ── Helpers de progreso ────────────────────────────────────
function _buildProgreso(pct, total) {
    return `
        <div style="padding:30px 20px; text-align:center;">
            <div style="font-size:1rem; font-weight:600; color:#374151;
                        margin-bottom:20px;">
                ⏳ Importando <strong>${total}</strong> productos...
            </div>

            <!-- Barra -->
            <div style="background:#e5e7eb; border-radius:999px;
                        height:14px; overflow:hidden; margin-bottom:10px;">
                <div id="imp-barra"
                     style="height:100%; width:${pct}%;
                            background:linear-gradient(90deg, #f97316, #ea580c);
                            border-radius:999px;
                            transition:width .15s ease;">
                </div>
            </div>

            <!-- Porcentaje -->
            <div id="imp-pct"
                 style="font-size:1.4rem; font-weight:700;
                        color:#ea580c; margin-bottom:6px;">
                ${pct}%
            </div>
            <div id="imp-sub"
                 style="font-size:.82rem; color:#6b7280;">
                Por favor espera, no cierres esta ventana...
            </div>
        </div>`;
}

function _updateProgreso(pct, total) {
    const barra = document.getElementById('imp-barra');
    const label = document.getElementById('imp-pct');
    const sub   = document.getElementById('imp-sub');
    if (!barra) return;

    barra.style.width = pct + '%';
    label.textContent = pct + '%';

    if (pct >= 100) {
        sub.textContent  = '✅ Finalizando...';
        label.style.color = '#16a34a';
        barra.style.background = '#16a34a';
    }
}