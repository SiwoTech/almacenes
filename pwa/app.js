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

    window.addEventListener('online', () => {
        actualizarBannerOffline();
        sincronizarPendientes();
    });
    window.addEventListener('offline', actualizarBannerOffline);

    navigator.serviceWorker?.addEventListener('message', event => {
        if (event.data?.type === 'sync-conteos') sincronizarPendientes();
    });
}

function mostrarPantalla(id) {
    document.querySelectorAll('.pwa-screen').forEach(s => s.classList.remove('active'));
    document.getElementById(id).classList.add('active');
}

function actualizarBannerOffline() {
    document.getElementById('offline-banner').style.display = navigator.onLine ? 'none' : 'block';
}

async function registrarSW() {
    if (!('serviceWorker' in navigator)) return;
    try {
        const reg = await navigator.serviceWorker.register('/almacenes/pwa/sw.js');
        if ('sync' in reg) {
            try { await reg.sync.register('cwo-sync-conteos'); } catch (_) {}
        }
    } catch (_) {}
}

async function cargarAlmacenes() {
    try {
        const res = await apiFetch('/almacenes/api/inventario/stock.php?action=almacenes');
        if (!res.ok) throw new Error('No se cargaron almacenes');
        localStorage.setItem('cwo_almacenes_cache', JSON.stringify(res.data || []));
        renderAlmacenes(res.data || []);
    } catch (_) {
        const cache = JSON.parse(localStorage.getItem('cwo_almacenes_cache') || '[]');
        renderAlmacenes(cache);
    }
}

function renderAlmacenes(rows) {
    document.getElementById('sel-almacen').innerHTML = ['<option value="">Selecciona almacén</option>', ...rows.map(a => `<option value="${a.clave}">${a.clave}</option>`)].join('');
}

async function iniciarConteo(clave) {
    if (!clave) return;
    almacenActivo = clave;

    try {
        const res = await apiFetch('/almacenes/api/inventario/stock.php?action=listar');
        if (!res.ok) throw new Error('Error al cargar inventario');
        productos = (res.data || [])
            .filter(p => p.franquicia_clave === clave)
            .map(p => {
                const productoId = Number(p.producto_id || 0);
                if (!productoId) return null;
                return {
                    producto_id: productoId,
                    codigo: p.codigo,
                    producto: p.producto,
                    sistema: Number(p.existencias || 0),
                    contado: Number(p.existencias || 0),
                    diferencia: 0,
                };
            })
            .filter(Boolean);
        localStorage.setItem(`cwo_productos_${clave}`, JSON.stringify(productos));
    } catch (_) {
        productos = JSON.parse(localStorage.getItem(`cwo_productos_${clave}`) || '[]');
    }

    conteoActual = productos.map(p => ({ ...p }));
    renderProductos(conteoActual);
    mostrarPantalla('screen-conteo');
}

function buscarProducto(query) {
    const q = (query || '').toLowerCase().trim();
    if (!q) return conteoActual;
    return conteoActual.filter(p => `${p.codigo} ${p.producto}`.toLowerCase().includes(q));
}

function renderProductos(items) {
    const el = document.getElementById('lista-productos');
    if (!items.length) {
        el.innerHTML = '<div class="pwa-card">Sin productos</div>';
        return;
    }

    el.innerHTML = items.map(item => {
        const diff = Number(item.contado) - Number(item.sistema);
        return `
        <div class="pwa-card pwa-item">
            <div>
                <div><strong>${item.codigo || ''}</strong></div>
                <div>${item.producto || ''}</div>
            </div>
            <div>Sis: ${Number(item.sistema).toFixed(2)}</div>
            <div>
                <input class="pwa-input" type="number" step="0.0001" value="${item.contado}" onchange="actualizarContado(${item.producto_id}, this.value)">
                <div class="${diff >= 0 ? 'pwa-diff-pos' : 'pwa-diff-neg'}">${diff >= 0 ? '+' : ''}${diff.toFixed(2)}</div>
            </div>
        </div>`;
    }).join('');
}

function actualizarContado(productoId, valor) {
    conteoActual = conteoActual.map(i => {
        if (Number(i.producto_id) !== Number(productoId)) return i;
        const contado = Number(valor || 0);
        return { ...i, contado, diferencia: contado - Number(i.sistema) };
    });
    renderProductos(buscarProducto(document.getElementById('buscar-producto').value));
}

function guardarConteoLocal(clave, items) {
    localStorage.setItem(`cwo_conteo_${clave}`, JSON.stringify(items));
}

function mostrarResumen() {
    const difs = conteoActual.filter(i => Number(i.contado) - Number(i.sistema) !== 0);
    document.getElementById('tbody-resumen').innerHTML = !difs.length
        ? '<tr><td colspan="4">Sin diferencias</td></tr>'
        : difs.map(i => `<tr><td>${i.codigo} · ${i.producto}</td><td>${Number(i.sistema).toFixed(2)}</td><td>${Number(i.contado).toFixed(2)}</td><td>${Number(i.contado - i.sistema).toFixed(2)}</td></tr>`).join('');
    mostrarPantalla('screen-resumen');
}

async function sincronizarConteo(clave, items) {
    const payload = {
        clave,
        items: items.map(i => ({
            producto_id: i.producto_id,
            contado: Number(i.contado),
            sistema: Number(i.sistema),
            diferencia: Number(i.contado) - Number(i.sistema),
        })),
    };

    if (!navigator.onLine) {
        encolarPendiente(payload);
        actualizarBannerOffline();
        return;
    }

    try {
        const res = await apiFetch('/almacenes/api/inventario/fisico.php?action=sincronizar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        });
        if (!res.ok) throw new Error(res.message || 'Error de sincronización');
        localStorage.removeItem(`cwo_conteo_${clave}`);
        alert(`Sincronización completa. Ajustados: ${res.ajustados || 0}`);
        mostrarPantalla('screen-inicio');
    } catch (_) {
        encolarPendiente(payload);
        actualizarBannerOffline();
    }
}

function encolarPendiente(payload) {
    const queue = JSON.parse(localStorage.getItem('cwo_conteos_pendientes') || '[]');
    queue.push(payload);
    localStorage.setItem('cwo_conteos_pendientes', JSON.stringify(queue));
}

async function sincronizarPendientes() {
    if (!navigator.onLine) return;
    const queue = JSON.parse(localStorage.getItem('cwo_conteos_pendientes') || '[]');
    if (!queue.length) return;

    const remaining = [];
    for (const item of queue) {
        try {
            const res = await apiFetch('/almacenes/api/inventario/fisico.php?action=sincronizar', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(item),
            });
            if (!res.ok) remaining.push(item);
        } catch (_) {
            remaining.push(item);
        }
    }

    localStorage.setItem('cwo_conteos_pendientes', JSON.stringify(remaining));
}
