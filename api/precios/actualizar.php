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
        $st = $db->query("SELECT pv.updated_at, p.codigo, p.nombre AS producto, tc.nombre AS tipo_cliente, m.simbolo AS moneda, pv.precio, ei.porcentaje AS impuesto, pv.actualizado_por FROM precios_venta pv LEFT JOIN productos p ON p.id = pv.producto_id LEFT JOIN tipos_cliente tc ON tc.id = pv.tipo_cliente_id LEFT JOIN monedas m ON m.id = pv.moneda_id LEFT JOIN esquemas_impuesto ei ON ei.id = pv.impuesto_id ORDER BY pv.updated_at DESC LIMIT 500");
        echo json_encode(['ok' => true, 'data' => $st->fetchAll()]);
        exit;
    }

    if ($action === 'catalogos') {
        $productos = $db->query('SELECT id, codigo, nombre FROM productos WHERE activo = 1 ORDER BY nombre')->fetchAll();
        $clientes = $db->query('SELECT id, nombre FROM tipos_cliente WHERE activo = 1 ORDER BY nombre')->fetchAll();
        $monedas = $db->query('SELECT id, nombre, simbolo FROM monedas ORDER BY nombre')->fetchAll();
        $impuestos = $db->query('SELECT id, nombre, porcentaje FROM esquemas_impuesto ORDER BY porcentaje')->fetchAll();
        echo json_encode(['ok' => true, 'data' => compact('productos', 'clientes', 'monedas', 'impuestos')]);
        exit;
    }

    if ($action === 'guardar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        Session::requireCWOAdmin();

        $productoId = (int)($_POST['producto_id'] ?? 0);
        $tipoClienteId = (int)($_POST['tipo_cliente_id'] ?? 0);
        $monedaId = (int)($_POST['moneda_id'] ?? 0);
        $precio = (float)($_POST['precio'] ?? -1);
        $impuestoId = (int)($_POST['impuesto_id'] ?? 0);

        if ($productoId <= 0 || $tipoClienteId <= 0 || $monedaId <= 0 || $precio < 0 || $impuestoId <= 0) {
            echo json_encode(['ok' => false, 'message' => 'Datos incompletos']);
            exit;
        }

        $sql = 'INSERT INTO precios_venta (producto_id, tipo_cliente_id, moneda_id, precio, impuesto_id, actualizado_por, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE precio=VALUES(precio), impuesto_id=VALUES(impuesto_id), actualizado_por=VALUES(actualizado_por), updated_at=NOW()';
        $st = $db->prepare($sql);
        $st->execute([$productoId, $tipoClienteId, $monedaId, $precio, $impuestoId, Session::$username]);

        echo json_encode(['ok' => true, 'message' => 'Precio actualizado']);
        exit;
    }

    echo json_encode(['ok' => false, 'message' => 'Acción no válida']);
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
