// Cargar inventario de una franquicia
function cargarInventario(franquiciaClave) {
    fetch(`/cwo/api/inventario.php?action=lista&franquicia=${franquiciaClave}`)
        .then(r => r.json())
        .then(res => {
            if (!res.ok) {
                alert(res.message);
                return;
            }
            renderInventario(res.data.inventario);
        })
        .catch(err => console.error('Error:', err));
}

// Actualizar stock vía AJAX
function actualizarStock(franquicia, productoId, existencias) {
    fetch('/cwo/api/inventario.php?action=actualizar', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            franquicia_clave: franquicia,
            producto_id:      productoId,
            existencias:      existencias,
        })
    })
    .then(r => r.json())
    .then(res => {
        if (res.ok) cargarInventario(franquicia);
        else alert(res.message);
    });
}