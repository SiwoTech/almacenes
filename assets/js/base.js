// Parámetros de sesión Orange
const params = new URLSearchParams(window.location.search);
const CWO = {
    a:   params.get('a'),   // franquicia_clave
    b:   params.get('b'),   // username
    c:   params.get('c'),   // rol
    tab: params.get('tab') ?? 'dashboard',
};

/**
 * Fetch helper — siempre lleva a, b, c
 */
function cwoFetch(url, options = {}) {
    const sep = url.includes('?') ? '&' : '?';
    const fullUrl = `${url}${sep}a=${CWO.a}&b=${CWO.b}&c=${CWO.c}`;
    return fetch(fullUrl, options).then(r => r.json());
}

/**
 * Muestra spinner en un contenedor
 */
function cwoLoading(containerId) {
    document.getElementById(containerId).innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-warning" role="status"></div>
        </div>`;
}

/**
 * Muestra error en un contenedor
 */
function cwoError(containerId, msg = 'Error al cargar datos') {
    document.getElementById(containerId).innerHTML = `
        <div class="alert alert-danger">${msg}</div>`;
}