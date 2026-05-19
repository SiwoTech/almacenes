let _productosIni = [];

document.addEventListener('DOMContentLoaded', cargarAlmacenesIni);

function cargarAlmacenesIni() {
    cwoFetch('/almacenes/api/inventario/stock.php?action=almacenes')
        .then(res => {
            if (!res.ok) return;
            const sel = document.getElementById('ini-almacen');
            res.data.forEach(a => {
                const opt = document.createElement('option');
                opt.value       = a.clave;
                opt.textContent = `${a.clave} (${a.tipo})`;
                sel.appendChild(opt);
            });
        });
}

function cargarProductosIniciales() {
    const clave = document.getElementById('ini-almacen').value;
    if (!clave) { alert('Selecciona un almacén'); return; }

    document.getElementById('ini-subtitulo').textContent = `Cargando productos para ${clave}...`;
    document.getElementById('ini-contenedor').style.display = 'block';

    cwoFetch(`/almacenes/api/inventario/stock.php?action=productos_inicial&clave=${clave}`)
        .then(res => {
            if (!res.ok) { alert(res.message); return; }
            _productosIni = res.data;
            filtrarIniciales();
            document.getElementById('ini-subtitulo').textContent =
                `${res.data.length} productos — Almacén: ${clave}`;
        })
        .catch(() => alert('❌ Error de conexión'));
}

function filtrarIniciales() {
    const buscar = (document.getElementById('ini-buscar')?.value ?? '').toLowerCase();
    const fil = !buscar ? _productosIni
        : _productosIni.filter(p =>
            p.codigo.toLowerCase().includes(buscar) ||
            p.nombre.toLowerCase().includes(buscar));
    renderTablaIni(fil);
}

const tipoLabelIni = {
    materia_prima:     {l:'MP',  bg:'#fdf4ff', c:'#7e22ce'},
    producto_terminado:{l:'PT',  bg:'#d1fae5', c:'#065f46'},
    insumo:            {l:'INS', bg:'#e0e7ff', c:'#3730a3'},
    envase:            {l:'ENV', bg:'#fce7f3', c:'#9d174d'},
    uniforme:          {l:'UNI', bg:'#fff7ed', c:'#c2410c'},
    herramienta:       {l:'HER', bg:'#f1f5f9', c:'#475569'},
    refaccion:         {l:'REF', bg:'#fef9c3', c:'#854d0e'},
    otro:              {l:'OTR', bg:'#f3f4f6', c:'#374151'},
};

function renderTablaIni(rows) {
    const tbody = document.getElementById('ini-tbody');

    if (!rows.length) {
        tbody.innerHTML = `<tr><td colspan="8"
            style="text-align:center;padding:30px;color:#9ca3af;">Sin productos</td></tr>`;
        return;
    }

    tbody.innerHTML = rows.map(p => {
        const tl = tipoLabelIni[p.tipo] ?? tipoLabelIni.otro;
        const ya = Number(p.tiene_registro);

        return `
        <tr data-id="${p.id}"
            style="border-bottom:1px solid #f3f4f6;${ya ? '' : ''}"
            onmouseover="this.style.background='#fafafa'"
            onmouseout="this.style.background=''">
            <td style="padding:10px 16px;">
                <span style="font-family:monospace;font-weight:700;font-size:.85rem;">${p.codigo}</span>
            </td>
            <td style="padding:10px 16px;font-size:.875rem;color:#374151;">${p.nombre}</td>
            <td style="padding:10px 16px;">
                <span style="font-size:.72rem;font-weight:600;padding:2px 8px;border-radius:6px;
                             background:${tl.bg};color:${tl.c};">${tl.l}</span>
            </td>
            <td style="padding:10px 16px;font-size:.82rem;color:#6b7280;">${p.unidad ?? '—'}</td>
            <td style="padding:10px 16px;text-align:right;">
                <input type="number" min="0" step="0.0001"
                       value="${parseFloat(p.existencias)}"
                       data-campo="existencias" data-pid="${p.id}"
                       style="width:110px;padding:6px 8px;border:1px solid #e5e7eb;
                              border-radius:6px;font-size:.875rem;text-align:right;">
            </td>
            <td style="padding:10px 16px;text-align:right;">
                <input type="number" min="0" step="0.0001"
                       value="${parseFloat(p.stock_minimo)}"
                       data-campo="stock_minimo" data-pid="${p.id}"
                       style="width:90px;padding:6px 8px;border:1px solid #e5e7eb;
                              border-radius:6px;font-size:.875rem;text-align:right;">
            </td>
            <td style="padding:10px 16px;text-align:right;">
                <input type="number" min="0" step="0.0001"
                       value="${parseFloat(p.stock_maximo)}"
                       data-campo="stock_maximo" data-pid="${p.id}"
                       style="width:90px;padding:6px 8px;border:1px solid #e5e7eb;
                              border-radius:6px;font-size:.875rem;text-align:right;">
            </td>
            <td style="padding:10px 16px;">
                ${ya
                    ? '<span style="font-size:.75rem;color:#10b981;font-weight:600;"><i class="bi bi-check-circle"></i> Registrado</span>'
                    : '<span style="font-size:.75rem;color:#9ca3af;">Nuevo</span>'}
            </td>
        </tr>`;
    }).join('');
}

function limpiarIniciales() {
    document.querySelectorAll('#ini-tbody input[type="number"]')
        .forEach(inp => inp.value = '0');
}

function guardarIniciales() {
    const clave = document.getElementById('ini-almacen').value;
    if (!clave) { alert('Selecciona un almacén'); return; }

    const items = [];
    document.querySelectorAll('#ini-tbody tr[data-id]').forEach(tr => {
        const pid = parseInt(tr.dataset.id);
        const get = campo => parseFloat(
            tr.querySelector(`input[data-campo="${campo}"]`)?.value ?? 0
        );
        items.push({
            producto_id:  pid,
            existencias:  get('existencias'),
            stock_minimo: get('stock_minimo'),
            stock_maximo: get('stock_maximo'),
        });
    });

    if (!items.length) { alert('Sin productos'); return; }

    const btn = document.getElementById('btn-guardar-ini');
    btn.textContent = 'Guardando...';
    btn.disabled    = true;

    cwoFetch('/almacenes/api/inventario/stock.php?action=guardar_inicial', {
        method:  'POST',
        headers: {'Content-Type':'application/json'},
        body:    JSON.stringify({ clave, items }),
    }).then(res => {
        btn.textContent = 'Guardar inventario';
        btn.disabled    = false;
        if (res.ok) {
            alert(`✅ ${res.message}`);
            cargarProductosIniciales();
        } else {
            alert('❌ ' + res.message);
        }
    }).catch(() => {
        btn.textContent = 'Guardar inventario';
        btn.disabled    = false;
        alert('❌ Error de conexión');
    });
}