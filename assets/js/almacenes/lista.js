let _almacenes = [];

document.addEventListener('DOMContentLoaded', cargarAlmacenes);

function cargarAlmacenes() {
    cwoFetch('/almacenes/api/catalogos/almacenes.php?action=listar')
        .then(res => {
            if (!res.ok) { alert(res.message); return; }
            _almacenes = res.data;
            filtrarAlmacenes();
        })
        .catch(() => alert('❌ Error de conexión'));
}

function filtrarAlmacenes() {
    const buscar = (document.getElementById('alm-buscar')?.value ?? '').toLowerCase();
    const tipo   = document.getElementById('alm-tipo')?.value   ?? '';
    const activo = document.getElementById('alm-activo')?.value ?? '';

    const filtrados = _almacenes.filter(a => {
        const matchBuscar = !buscar
            || a.clave.toLowerCase().includes(buscar)
            || (a.nombre_contacto ?? '').toLowerCase().includes(buscar)
            || (a.ciudad ?? '').toLowerCase().includes(buscar)
            || (a.estado ?? '').toLowerCase().includes(buscar);
        const matchTipo   = !tipo   || a.tipo === tipo;
        const matchActivo = activo === '' || String(a.activo) === activo;
        return matchBuscar && matchTipo && matchActivo;
    });

    renderTabla(filtrados);
}

// ── Helpers visuales ───────────────────────────────────
const tipoPill = {
    matriz:     { bg:'#fff7ed', color:'#c2410c', icon:'bi-star-fill' },
    sucursal:   { bg:'#eff6ff', color:'#1d4ed8', icon:'bi-building'  },
    franquicia: { bg:'#f5f3ff', color:'#6d28d9', icon:'bi-shop'      },
    bodega:     { bg:'#f1f5f9', color:'#475569', icon:'bi-box-seam'  },
};

function avatarIniciales(nombre) {
    if (!nombre) return '';
    const partes  = nombre.trim().split(' ');
    const iniciales = (partes[0]?.[0] ?? '') + (partes[1]?.[0] ?? '');
    return iniciales.toUpperCase();
}

function colorAvatar(clave) {
    const colores = ['#f97316','#8b5cf6','#3b82f6','#10b981','#ef4444','#f59e0b','#06b6d4','#ec4899'];
    let hash = 0;
    for (const c of clave) hash = c.charCodeAt(0) + ((hash << 5) - hash);
    return colores[Math.abs(hash) % colores.length];
}

// ── Render ─────────────────────────────────────────────
function renderTabla(rows) {
    const tbody = document.getElementById('alm-tbody');
    const cont  = document.getElementById('alm-contador');

    cont.textContent = `${rows.length} almacén(es)`;

    if (!rows.length) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" style="text-align:center;padding:40px;color:#9ca3af;font-size:.85rem;">
                    <i class="bi bi-inbox" style="font-size:1.4rem;display:block;margin-bottom:6px;"></i>
                    Sin resultados
                </td>
            </tr>`;
        return;
    }

    tbody.innerHTML = rows.map((a, i) => {
        const pill    = tipoPill[a.tipo] ?? tipoPill.bodega;
        const bg      = colorAvatar(a.clave);
        const ini     = avatarIniciales(a.nombre_contacto);
        const activo  = !!Number(a.activo);
        const borde   = i < rows.length - 1 ? 'border-bottom:1px solid #f3f4f6;' : '';
        const opaci   = activo ? '' : 'opacity:.5;';

        return `
        <tr style="${borde}${opaci}transition:background .12s;" 
            onmouseover="this.style.background='#fafafa'" 
            onmouseout="this.style.background=''">

            <!-- Clave -->
            <td style="padding:13px 16px;">
                <span style="font-family:monospace;font-weight:700;font-size:.9rem;
                             color:#111;letter-spacing:.03em;">
                    ${a.clave}
                </span>
            </td>

            <!-- Tipo pill -->
            <td style="padding:13px 16px;">
                <span style="display:inline-flex;align-items:center;gap:5px;
                             background:${pill.bg};color:${pill.color};
                             padding:3px 10px;border-radius:999px;
                             font-size:.75rem;font-weight:600;">
                    <i class="bi ${pill.icon}" style="font-size:.7rem;"></i>
                    ${a.tipo.charAt(0).toUpperCase() + a.tipo.slice(1)}
                </span>
            </td>

            <!-- Franquiciatario con avatar -->
            <td style="padding:13px 16px;">
                ${a.nombre_contacto ? `
                <div style="display:flex;align-items:center;gap:10px;">
                    <div style="width:32px;height:32px;border-radius:50%;background:${bg};
                                color:#fff;font-size:.7rem;font-weight:700;flex-shrink:0;
                                display:flex;align-items:center;justify-content:center;">
                        ${ini}
                    </div>
                    <span style="color:#374151;font-size:.85rem;">${a.nombre_contacto}</span>
                </div>` : `<span style="color:#d1d5db;">—</span>`}
            </td>

            <!-- Ubicación -->
            <td style="padding:13px 16px;">
                ${a.ciudad ? `
                <div style="font-size:.85rem;color:#374151;">${a.ciudad}</div>
                <div style="font-size:.75rem;color:#9ca3af;margin-top:1px;">${a.estado ?? ''}</div>
                ` : `<span style="color:#d1d5db;">—</span>`}
            </td>

            <!-- Estatus -->
            <td style="padding:13px 16px;">
                <span style="display:inline-flex;align-items:center;gap:5px;
                             background:${activo ? '#d1fae5' : '#fee2e2'};
                             color:${activo ? '#065f46' : '#991b1b'};
                             padding:3px 10px;border-radius:999px;
                             font-size:.75rem;font-weight:600;">
                    <span style="width:6px;height:6px;border-radius:50%;
                                 background:${activo ? '#10b981' : '#ef4444'};
                                 display:inline-block;"></span>
                    ${activo ? 'Activo' : 'Inactivo'}
                </span>
            </td>

            <!-- Acciones -->
            <td style="padding:13px 16px;">
                <div style="display:flex;gap:4px;justify-content:flex-end;">
                    <button class="btn-icon" title="Editar"
                            onclick="editarAlmacen(${JSON.stringify(a).replace(/"/g,'&quot;')})"
                            style="color:#6b7280;">
                        <i class="bi bi-pencil-square"></i>
                    </button>
                    ${a.clave !== 'CWO' ? `
                    <button class="btn-icon" title="${activo ? 'Desactivar' : 'Activar'}"
                            onclick="toggleAlmacen(${a.id}, '${a.clave}', ${a.activo})"
                            style="color:${activo ? '#ef4444' : '#10b981'};">
                        <i class="bi bi-${activo ? 'toggle-on' : 'toggle-off'}" style="font-size:1.1rem;"></i>
                    </button>` : ''}
                </div>
            </td>
        </tr>`;
    }).join('');
}

// ── Modal ──────────────────────────────────────────────
function abrirModalAlmacen() {
    document.getElementById('modal-alm-titulo').textContent = 'Nuevo almacén';
    document.getElementById('alm-id').value        = '';
    document.getElementById('alm-clave').value     = '';
    document.getElementById('alm-tipo-form').value = '';
    document.getElementById('alm-clave').disabled  = false;
    document.getElementById('modal-almacen').style.display = 'flex';
}

function editarAlmacen(a) {
    document.getElementById('modal-alm-titulo').textContent = `Editar — ${a.clave}`;
    document.getElementById('alm-id').value        = a.id;
    document.getElementById('alm-clave').value     = a.clave;
    document.getElementById('alm-tipo-form').value = a.tipo;
    document.getElementById('alm-clave').disabled  = true;
    document.getElementById('modal-almacen').style.display = 'flex';
}

function cerrarModalAlmacen() {
    document.getElementById('modal-almacen').style.display = 'none';
}

function guardarAlmacen(e) {
    e.preventDefault();
    const btn  = document.getElementById('btn-guardar-alm');
    const body = new FormData();
    body.append('id',    document.getElementById('alm-id').value);
    body.append('clave', document.getElementById('alm-clave').value.toUpperCase());
    body.append('tipo',  document.getElementById('alm-tipo-form').value);

    btn.textContent = 'Guardando...';
    btn.disabled    = true;

    cwoFetch('/almacenes/api/catalogos/almacenes.php?action=guardar', { method:'POST', body })
        .then(res => {
            btn.textContent = 'Guardar';
            btn.disabled    = false;
            if (res.ok) { cerrarModalAlmacen(); cargarAlmacenes(); }
            else alert('❌ ' + res.message);
        })
        .catch(() => { btn.textContent = 'Guardar'; btn.disabled = false; alert('❌ Error de conexión'); });
}

function toggleAlmacen(id, clave, activo) {
    if (!confirm(`¿${activo ? 'Desactivar' : 'Activar'} el almacén ${clave}?`)) return;
    const body = new FormData();
    body.append('id', id);

    cwoFetch('/almacenes/api/catalogos/almacenes.php?action=toggle', { method:'POST', body })
        .then(res => {
            if (res.ok) cargarAlmacenes();
            else alert('❌ ' + res.message);
        })
        .catch(() => alert('❌ Error de conexión'));
}

document.getElementById('modal-almacen')
    ?.addEventListener('click', function(e) {
        if (e.target === this) cerrarModalAlmacen();
    });