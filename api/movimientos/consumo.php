<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/db.php';

Session::init();
if (!Session::puedeConsumoInterno()) {
    Session::forbidden();
}

header('Content-Type: application/json');

$db = DB::get('almacenes');
$action = $_GET['action'] ?? '';

function stockConsumo(PDO $db, string $clave, int $productoId): float {
    $st = $db->prepare('SELECT existencias FROM inventario WHERE franquicia_clave = ? AND producto_id = ?');
    $st->execute([$clave, $productoId]);
    $row = $st->fetch();
    return $row ? (float)$row['existencias'] : 0;
}

try {
    if ($action === 'listar') {
        $st = $db->prepare("SELECT m.id, m.fecha, m.almacen_clave, m.producto_id, m.cantidad, m.tipo_gasto_id, m.notas, m.usuario, p.nombre AS producto, tg.nombre AS tipo_gasto FROM movimientos m LEFT JOIN productos p ON p.id = m.producto_id LEFT JOIN tipos_gasto tg ON tg.id = m.tipo_gasto_id WHERE m.franquicia_clave = ? AND m.tipo = 'consumo' ORDER BY m.fecha DESC, m.id DESC LIMIT 500");
        $st->execute([Session::$franquicia]);
        echo json_encode(['ok' => true, 'data' => $st->fetchAll()]);
        exit;
    }

    if ($action === 'registrar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $almacen = trim($_POST['almacen_clave'] ?? '');
        $productoId = (int)($_POST['producto_id'] ?? 0);
        $cantidad = (float)($_POST['cantidad'] ?? 0);
        $tipoGasto = trim($_POST['tipo_gasto_id'] ?? '') === '' ? null : (int)$_POST['tipo_gasto_id'];
        $descripcion = trim($_POST['descripcion'] ?? '');
        $fecha = trim($_POST['fecha'] ?? date('Y-m-d'));

        if ($almacen === '' || $productoId <= 0 || $cantidad <= 0) {
            echo json_encode(['ok' => false, 'message' => 'Datos incompletos']);
            exit;
        }

        $db->beginTransaction();

        if (stockConsumo($db, $almacen, $productoId) < $cantidad) {
            throw new RuntimeException('Existencias insuficientes');
        }

        $ins = $db->prepare('INSERT INTO movimientos (franquicia_clave, tipo, producto_id, almacen_clave, cantidad, costo_unitario, precio_venta, referencia, tipo_cliente_id, tipo_gasto_id, motivo, fecha, notas, usuario, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())');
        $ins->execute([
            Session::$franquicia,
            'consumo',
            $productoId,
            $almacen,
            $cantidad,
            null,
            null,
            null,
            null,
            $tipoGasto,
            null,
            $fecha,
            $descripcion,
            Session::$username,
        ]);

        $up = $db->prepare('UPDATE inventario SET existencias = existencias - ? WHERE franquicia_clave = ? AND producto_id = ?');
        $up->execute([$cantidad, $almacen, $productoId]);

        $db->commit();
        echo json_encode(['ok' => true, 'message' => 'Consumo registrado']);
        exit;
    }

    echo json_encode(['ok' => false, 'message' => 'Acción no válida']);
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode(['ok' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
