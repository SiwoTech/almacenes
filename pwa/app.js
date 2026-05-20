let sessionParams = { a: '', b: '', c: '' };
let productos = [];
let conteoActual = [];
let almacenActivo = '';

window.addEventListener('DOMContentLoaded', async () => {
    inicializarSesion();
    registrarSW();
    bindEventos();
    actualizarBannerOffline();
    await cargarAlmacenes();
});

function inicializarSesion() {
    const fromStorage = JSON.parse(localStorage.getItem('cwo_session') || '{}');
    const p = new URLSearchParams(window.location.search);
    sessionParams = {
        a: p.get('a') || fromStorage.a || '',
        b: p.get('b') || fromStorage.b || '',
        c: p.get('c') || fromStorage.c || '',
    };
    localStorage.setItem('cwo_session', JSON.stringify(sessionParams));
}

function apiUrl(url) {
    const sep = url.includes('?') ? '&' : '?';
    return `${url}${sep}a=${encodeURIComponent(sessionParams.a)}&b=${encodeURIComponent(sessionParams.b)}&c=${encodeURIComponent(sessionParams.c)}`;
}

async function apiFetch(url, options = {}) {
    const r = await fetch(apiUrl(url), options);
    return r.json();
}

function bindEventos() {
    document.getElementById('btn-iniciar').addEventListener('click', () => iniciarConteo(document.getElementById('sel-almacen').value));
    document.getElementById('buscar-producto').addEventListener('input', e => renderProductos(buscarProducto(e.target.value)));
    document.getElementById('btn-guardar').addEventListener('click', () => {
        guardarConteoLocal(almacenActivo, conteoActual);
        mostrarResumen();
    });
    document.getElementById('btn-sincronizar').addEventListener('click', () => sincronizarConteo(almacenActivo, conteoActual));
    document.getElementById('btn-sync').addEventListener('click', sincronizarPendientes);
    document.getElementById('lista-productos').addEventListener('change', onConteoChange);

    window.addEventListener('online', () => {
        actualizarBannerOffline();
        sincronizarPendientes();
    });
    window.addEventListener('offline', actualizarBannerOffline);

    navigator.serviceWorker && navigator.serviceWorker.addEventListener('message', function(event) {
        if (event.data && event.data.type === 'sync-conteos') sincronizarPendientes();
    });
}

function mostrarPantalla(id) {
    document.querySelectorAll('.pwa-screen').forEach(function(s) { s.classList.remove('active'); });
    document.getElementById(id).classList.add('active');
}

function actualizarBannerOffline() {
    document.getElementById('offline-banner').style.display = navigator.onLine ? 'none' : 'block';
}

async function registrarSW() {
    if (!('serviceWorker' in navigator)) return;
    try {
        var reg = await navigator.serviceWorker.register('/almacenes/pwa/sw.js');
        if ('sync' in reg) {
            try { await reg.sync.register('cwo-sync-conteos'); } catch (e) {}
        }
    } catch (e) {}
}

async function cargarAlmacenes() {
    try {
        var res = await apiFetch('/almacenes/api/inventario/stock.php?action=almacenes');
        if (!res.ok) throw new Error('No se cargaron almacenes');
        localStorage.setItem('cwo_almacenes_cache', JSON.stringify(res.data || []));
        renderAlmacenes(res.data || []);
    } catch (e) {
        var cache = JSON.parse(localStorage.getItem('cwo_almacenes_cache') || '[]');
        renderAlmacenes(cache);
    }
}

function renderAlmacenes(rows) {
    var opts = ['<option value="">Selecciona almacén</option>'];
    rows.forEach(function(a) {
        opts.push('<option value="' + escAttr(a.clave) + '">' + escHtml(a.clave) + '</option>');
    });
    document.getElementById('sel-almacen').innerHTML = opts.join('');
}

async function iniciarConteo(clave) {
    if (!clave) {
        alert('Selecciona un almacén primero');
        return;
    }
    almacenActivo = clave;

    // Mostrar indicador de carga
    mostrarPantalla('screen-conteo');
    document.getElementById('lista-productos').innerHTML = '<div class="pwa-card" style="text-align:center;padding:30px;">⏳ Cargando productos...</div>';
    document.getElementById('buscar-producto').value = '';

    try {
        // Usar listar_pwa que devuelve TODOS los productos activos del almacén
        // incluso los que no tienen registro en inventario (existencias = 0)
        var res = await apiFetch('/almacenes/api/inventario/stock.php?action=listar_pwa&clave=' + encodeURIComponent(clave));
        if (!res.ok) throw new Error(res.message || 'Error al cargar inventario');

        productos = (res.data || []).map(function(p) {
            return {
                producto_id: Number(p.producto_id || 0),
                codigo:      p.codigo     || '',
                producto:    p.producto   || '',
                tipo:        p.tipo       || '',
                unidad:      p.unidad     || '',
                sistema:     Number(p.existencias || 0),
                contado:     Number(p.existencias || 0),
                diferencia:  0,
            };
        }).filter(function(p) { return p.producto_id > 0; });

        localStorage.setItem('cwo_productos_' + clave, JSON.stringify(productos));

        if (!productos.length) {
            document.getElementById('lista-productos').innerHTML =
                '<div class="pwa-card" style="color:#856404;background:#fff8e1;padding:16px;">⚠️ No hay productos con control de almacén para esta franquicia.</div>';
            conteoActual = [];
            return;
        }

    } catch (e) {
        // Modo offline: cargar desde caché
        productos = JSON.parse(localStorage.getItem('cwo_productos_' + clave) || '[]');
        if (!productos.length) {
            document.getElementById('lista-productos').innerHTML =
                '<div class="pwa-card" style="color:#991b1b;background:#fff0f0;padding:16px;">❌ Sin conexión y sin caché disponible para este almacén.</div>';
            conteoActual = [];
            return;
        }
    }

    conteoActual = productos.map(function(p) { return Object.assign({}, p); });
    renderProductos(conteoActual);
}

function buscarProducto(query) {
    var q = (query || '').toLowerCase().trim();
    if (!q) return conteoActual;
    return conteoActual.filter(function(p) {
        return (p.codigo + ' ' + p.producto).toLowerCase().indexOf(q) !== -1;
    });
}

function renderProductos(items) {
    var el = document.getElementById('lista-productos');
    if (!items || !items.length) {
        el.innerHTML = '<div class="pwa-card" style="text-align:center;padding:20px;color:#6c757d;">Sin productos que coincidan</div>';
        return;
    }

    el.innerHTML = items.map(function(item) {
        var diff = Number(item.contado) - Number(item.sistema);
        var diffClass = diff > 0 ? 'pwa-diff-pos' : (diff < 0 ? 'pwa-diff-neg' : 'pwa-diff-cero');
        var diffStr  = (diff >= 0 ? '+' : '') + diff.toFixed(2);
        return '<div class="pwa-card pwa-item">' +
            '<div class="pwa-item-info">' +
                '<div><strong>' + escHtml(item.codigo) + '</strong></div>' +
                '<div class="pwa-item-nombre">' + escHtml(item.producto) + '</div>' +
                '<div class="pwa-item-tipo">' + escHtml(item.tipo) + (item.unidad ? ' · ' + escHtml(item.unidad) : '') + '</div>' +
            '</div>' +
            '<div class="pwa-item-sis">Sis:<br><strong>' + Number(item.sistema).toFixed(2) + '</strong></div>' +
            '<div class="pwa-item-cnt">' +
                '<input class="pwa-input js-contado" type="number" step="0.01" min="0" value="' + item.contado + '" data-producto-id="' + Number(item.producto_id) + '">' +
                '<div class="' + diffClass + '">' + diffStr + '</div>' +
            '</div>' +
        '</div>';
    }).join('');
}

function onConteoChange(event) {
    if (!event.target.classList.contains('js-contado')) return;
    actualizarContado(Number(event.target.dataset.productoId), event.target.value);
}

function actualizarContado(productoId, valor) {
    conteoActual = conteoActual.map(function(i) {
        if (Number(i.producto_id) !== Number(productoId)) return i;
        var contado = Number(valor || 0);
        return Object.assign({}, i, { contado: contado, diferencia: contado - Number(i.sistema) });
    });
    renderProductos(buscarProducto(document.getElementById('buscar-producto').value));
}

function guardarConteoLocal(clave, items) {
    localStorage.setItem('cwo_conteo_' + clave, JSON.stringify(items));
}

function mostrarResumen() {
    var difs = conteoActual.filter(function(i) { return (Number(i.contado) - Number(i.sistema)) !== 0; });
    var tbody = document.getElementById('tbody-resumen');
    if (!difs.length) {
        tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;padding:16px;">✅ Sin diferencias</td></tr>';
    } else {
        tbody.innerHTML = difs.map(function(i) {
            var diff = Number(i.contado) - Number(i.sistema);
            return '<tr>' +
                '<td>' + escHtml(i.codigo) + ' · ' + escHtml(i.producto) + '</td>' +
                '<td>' + Number(i.sistema).toFixed(2) + '</td>' +
                '<td>' + Number(i.contado).toFixed(2) + '</td>' +
                '<td class="' + (diff >= 0 ? 'pwa-diff-pos' : 'pwa-diff-neg') + '">' + (diff >= 0 ? '+' : '') + diff.toFixed(2) + '</td>' +
            '</tr>';
        }).join('');
    }
    mostrarPantalla('screen-resumen');
}

async function sincronizarConteo(clave, items) {
    var payload = {
        clave: clave,
        items: items.map(function(i) {
            return {
                producto_id: i.producto_id,
                contado:     Number(i.contado),
                sistema:     Number(i.sistema),
                diferencia:  Number(i.contado) - Number(i.sistema),
            };
        }),
    };

    if (!navigator.onLine) {
        encolarPendiente(payload);
        actualizarBannerOffline();
        alert('Sin conexión. El conteo se sincronizará automáticamente al reconectar.');
        return;
    }

    try {
        var res = await apiFetch('/almacenes/api/inventario/fisico.php?action=sincronizar', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify(payload),
        });
        if (!res.ok) throw new Error(res.message || 'Error de sincronización');
        localStorage.removeItem('cwo_conteo_' + clave);
        alert('✅ Sincronización completa. Ajustados: ' + (res.ajustados || 0) + ', Sin diferencia: ' + (res.sin_diferencia || 0));
        mostrarPantalla('screen-inicio');
    } catch (e) {
        encolarPendiente(payload);
        alert('❌ Error al sincronizar. Se guardó localmente para reintentar.');
        actualizarBannerOffline();
    }
}

function encolarPendiente(payload) {
    var queue = JSON.parse(localStorage.getItem('cwo_conteos_pendientes') || '[]');
    queue.push(payload);
    localStorage.setItem('cwo_conteos_pendientes', JSON.stringify(queue));
}

async function sincronizarPendientes() {
    if (!navigator.onLine) return;
    var queue = JSON.parse(localStorage.getItem('cwo_conteos_pendientes') || '[]');
    if (!queue.length) return;

    var remaining = [];
    for (var i = 0; i < queue.length; i++) {
        try {
            var res = await apiFetch('/almacenes/api/inventario/fisico.php?action=sincronizar', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify(queue[i]),
            });
            if (!res.ok) remaining.push(queue[i]);
        } catch (e) {
            remaining.push(queue[i]);
        }
    }
    localStorage.setItem('cwo_conteos_pendientes', JSON.stringify(remaining));
}

function escHtml(value) {
    return String(value == null ? '' : value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function escAttr(value) {
    return escHtml(value).replace(/`/g, '&#96;');
}
