<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/response.php';

$action = $_GET['action'] ?? '';

match($action) {
    'lista'     => getInventario(),
    'actualizar'=> actualizarStock(),
    default     => Response::error('Acción no válida', 404)
};

// ── GET inventario por franquicia ──────────────────────────
function getInventario(): void {
    $franquicia = $_GET['franquicia'] ?? '';

    if (!$franquicia) {
        Response::error('franquicia requerida');
    }

    $sql = "SELECT * FROM v_inventario
            WHERE franquicia_clave = :franquicia
            ORDER BY producto";

    $stmt = DB::get('almacenes')->prepare($sql);
    $stmt->execute([':franquicia' => $franquicia]);
    $rows = $stmt->fetchAll();

    // Enriquecer con nombre de franquicia desde cwofran
    $fran = DB::get('cwofran')
        ->prepare("SELECT franquicia, nombre_contacto, ciudad
                   FROM franquicias WHERE franquicia = :clave");
    $fran->execute([':clave' => $franquicia]);
    $info = $fran->fetch();

    Response::ok([
        'franquicia' => $info,
        'inventario' => $rows,
    ]);
}

// ── POST actualizar stock ──────────────────────────────────
function actualizarStock(): void {
    $data = json_decode(file_get_contents('php://input'), true);

    $required = ['franquicia_clave', 'producto_id', 'existencias'];
    foreach ($required as $field) {
        if (!isset($data[$field])) {
            Response::error("Campo requerido: $field");
        }
    }

    $sql = "UPDATE inventario
            SET existencias        = :existencias,
                ultima_actualizacion = NOW()
            WHERE franquicia_clave = :franquicia
              AND producto_id      = :producto";

    $stmt = DB::get('almacenes')->prepare($sql);
    $stmt->execute([
        ':existencias' => $data['existencias'],
        ':franquicia'  => $data['franquicia_clave'],
        ':producto'    => $data['producto_id'],
    ]);

    Response::ok(['filas' => $stmt->rowCount()], 'Stock actualizado');
}