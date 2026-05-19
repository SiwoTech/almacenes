<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/db.php';

Session::init();
Session::requireAny();

header('Content-Type: application/json');

$db = DB::get('almacenes');
$action = $_GET['action'] ?? '';

function inventarioDelta(PDO $db, string $clave, int $productoId, float $delta): void {
    $up = $db->prepare('UPDATE inventario SET existencias = existencias + ? WHERE franquicia_clave = ? AND producto_id = ?');
    $up->execute([$delta, $clave, $productoId]);

    if ($up->rowCount() === 0) {
        $ins = $db->prepare('INSERT INTO inventario (franquicia_clave, producto_id, existencias) VALUES (?, ?, ?)');
        $ins->execute([$clave, $productoId, max(0, $delta)]);
    }
}

try {
    if ($action === 'listar') {
        $fechaIni = trim($_GET['fecha_ini'] ?? date('Y-m-01'));
        $fechaFin = trim($_GET['fecha_fin'] ?? date('Y-m-d'));
        $almacen = trim($_GET['almacen'] ?? '');
        $productoId = (int)($_GET['producto_id'] ?? 0);

        $sql = "
            SELECT m.id, m.fecha, m.almacen_clave, m.producto_id, m.cantidad,
                   m.costo_unitario, m.referencia, m.usuario, m.created_at,
                   p.codigo, p.nombre AS producto
            FROM movimientos m
            LEFT JOIN productos p ON p.id = m.producto_id
            WHERE m.franquicia_clave = ?
              AND m.tipo = 'entrada'
              AND m.fecha BETWEEN ? AND ?
        ";
        $params = [Session::$franquicia, $fechaIni, $fechaFin];

        if ($almacen !== '') {
            $sql .= ' AND m.almacen_clave = ?';
            $params[] = $almacen;
        }
        if ($productoId > 0) {
            $sql .= ' AND m.producto_id = ?';
            $params[] = $productoId;
        }

        $sql .= ' ORDER BY m.fecha DESC, m.id DESC LIMIT 500';

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        echo json_encode(['ok' => true, 'data' => $rows]);
        exit;
    }

    if ($action === 'registrar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $almacen = trim($_POST['almacen_clave'] ?? '');
        $productoId = (int)($_POST['producto_id'] ?? 0);
        $cantidad = (float)($_POST['cantidad'] ?? 0);
        $costo = (float)($_POST['costo_unitario'] ?? 0);
        $proveedor = trim($_POST['proveedor'] ?? '');
        $referencia = trim($_POST['referencia'] ?? '');
        $fecha = trim($_POST['fecha'] ?? date('Y-m-d'));
        $notas = trim($_POST['notas'] ?? '');

        if ($almacen === '' || $productoId <= 0 || $cantidad <= 0) {
            echo json_encode(['ok' => false, 'message' => 'Datos incompletos']);
            exit;
        }

        $referenciaFinal = trim($proveedor . ($referencia !== '' ? ' · ' . $referencia : ''));

        $db->beginTransaction();

        $ins = $db->prepare('INSERT INTO movimientos (franquicia_clave, tipo, producto_id, almacen_clave, cantidad, costo_unitario, precio_venta, referencia, tipo_cliente_id, tipo_gasto_id, motivo, fecha, notas, usuario, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())');
        $ins->execute([
            Session::$franquicia,
            'entrada',
            $productoId,
            $almacen,
            $cantidad,
            $costo,
            null,
            $referenciaFinal,
            null,
            null,
            null,
            $fecha,
            $notas,
            Session::$username,
        ]);

        inventarioDelta($db, $almacen, $productoId, $cantidad);

        $db->commit();
        echo json_encode(['ok' => true, 'message' => 'Entrada registrada']);
        exit;
    }

    echo json_encode(['ok' => false, 'message' => 'Acción no válida']);
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode(['ok' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
