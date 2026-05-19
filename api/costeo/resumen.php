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
    if ($action !== 'kpis') {
        echo json_encode(['ok' => false, 'message' => 'Acción no válida']);
        exit;
    }

    $kpi = $db->query("SELECT COUNT(*) AS total_productos, AVG(cp.costo_total) AS costo_promedio, AVG(CASE WHEN pv.precio_min > 0 THEN ((pv.precio_min - cp.costo_total) / pv.precio_min) * 100 ELSE 0 END) AS margen_promedio, SUM(CASE WHEN cp.costo_total IS NULL OR cp.costo_total = 0 THEN 1 ELSE 0 END) AS sin_costear FROM productos p LEFT JOIN costos_producto cp ON cp.producto_id = p.id LEFT JOIN (SELECT producto_id, MIN(precio) AS precio_min FROM precios_venta GROUP BY producto_id) pv ON pv.producto_id = p.id WHERE p.activo = 1")->fetch();

    $top = $db->query("SELECT p.codigo, p.nombre AS producto, COALESCE(cp.costo_total, 0) AS costo_total FROM productos p LEFT JOIN costos_producto cp ON cp.producto_id = p.id WHERE p.activo = 1 ORDER BY costo_total DESC LIMIT 10")->fetchAll();

    echo json_encode(['ok' => true, 'data' => ['kpis' => $kpi, 'top' => $top]]);
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
