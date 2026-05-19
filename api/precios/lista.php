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
    if ($action === 'listar') {
        $buscar = trim($_GET['buscar'] ?? '');
        $tipo = trim($_GET['tipo'] ?? '');
        $tipoClienteId = (int)($_GET['tipo_cliente_id'] ?? 0);

        $sql = "
            SELECT p.id AS producto_id, p.codigo, p.nombre AS producto, p.tipo,
                   um.abrev AS unidad,
                   tc.id AS tipo_cliente_id, tc.nombre AS tipo_cliente,
                   m.id AS moneda_id, m.simbolo AS moneda,
                   pv.precio
            FROM productos p
            LEFT JOIN unidades_medida um ON um.id = p.unidad_medida_id
            LEFT JOIN precios_venta pv ON pv.producto_id = p.id
            LEFT JOIN tipos_cliente tc ON tc.id = pv.tipo_cliente_id
            LEFT JOIN monedas m ON m.id = pv.moneda_id
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
        if ($tipoClienteId > 0) {
            $sql .= ' AND pv.tipo_cliente_id = ?';
            $params[] = $tipoClienteId;
        }

        $sql .= ' ORDER BY p.nombre, tc.nombre';

        $st = $db->prepare($sql);
        $st->execute($params);
        echo json_encode(['ok' => true, 'data' => $st->fetchAll()]);
        exit;
    }

    if ($action === 'almacenes') {
        $rows = $db->query('SELECT clave, tipo FROM almacenes WHERE activo = 1 ORDER BY clave')->fetchAll();
        echo json_encode(['ok' => true, 'data' => $rows]);
        exit;
    }

    if ($action === 'catalogos') {
        $tipos = $db->query('SELECT DISTINCT tipo FROM productos WHERE activo = 1 ORDER BY tipo')->fetchAll();
        $clientes = $db->query('SELECT id, nombre FROM tipos_cliente WHERE activo = 1 ORDER BY nombre')->fetchAll();
        echo json_encode(['ok' => true, 'data' => ['tipos' => $tipos, 'clientes' => $clientes]]);
        exit;
    }

    echo json_encode(['ok' => false, 'message' => 'Acción no válida']);
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
