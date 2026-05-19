<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/db.php';

Session::init();
if (!Session::puedeVerCostos()) {
    Session::forbidden();
}

header('Content-Type: application/json');
$db = DB::get('almacenes');
$action = $_GET['action'] ?? '';

try {
    if ($action !== 'listar') {
        echo json_encode(['ok' => false, 'message' => 'Acción no válida']);
        exit;
    }

    $buscar = trim($_GET['buscar'] ?? '');
    $tipo = trim($_GET['tipo'] ?? '');
    $linea = (int)($_GET['linea'] ?? 0);

    $sql = "
        SELECT
            p.codigo,
            p.nombre AS producto,
            p.tipo,
            l.id AS linea_id,
            l.nombre AS linea,
            COALESCE(cp.costo_total, 0) AS costo_total,
            COALESCE(vp.precio_min, 0) AS precio_min,
            (COALESCE(vp.precio_min, 0) - COALESCE(cp.costo_total, 0)) AS margen_valor,
            CASE
                WHEN COALESCE(vp.precio_min, 0) <= 0 THEN 0
                ELSE ((COALESCE(vp.precio_min, 0) - COALESCE(cp.costo_total, 0)) / vp.precio_min) * 100
            END AS margen_pct
        FROM productos p
        LEFT JOIN lineas l ON l.id = p.linea_id
        LEFT JOIN costos_producto cp ON cp.producto_id = p.id
        LEFT JOIN (
            SELECT producto_id, MIN(precio) AS precio_min
            FROM precios_venta
            GROUP BY producto_id
        ) vp ON vp.producto_id = p.id
        WHERE p.activo = 1
    ";

    $params = [];
    if ($buscar !== '') {
        $sql .= ' AND (p.codigo LIKE ? OR p.nombre LIKE ?)';
        $params[] = '%' . $buscar . '%';
        $params[] = '%' . $buscar . '%';
    }
    if ($tipo !== '') {
        $sql .= ' AND p.tipo = ?';
        $params[] = $tipo;
    }
    if ($linea > 0) {
        $sql .= ' AND p.linea_id = ?';
        $params[] = $linea;
    }

    $sql .= ' ORDER BY p.nombre';
    $st = $db->prepare($sql);
    $st->execute($params);
    $rows = $st->fetchAll();

    $tipos = $db->query('SELECT DISTINCT tipo FROM productos WHERE activo = 1 ORDER BY tipo')->fetchAll();
    $lineas = $db->query('SELECT id, nombre FROM lineas ORDER BY nombre')->fetchAll();

    echo json_encode(['ok' => true, 'data' => $rows, 'catalogos' => ['tipos' => $tipos, 'lineas' => $lineas]]);
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
