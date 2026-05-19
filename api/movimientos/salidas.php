<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/db.php';

Session::init();
Session::requireAny();

header('Content-Type: application/json');

$db = DB::get('almacenes');
$action = $_GET['action'] ?? '';

function existenciasActuales(PDO $db, string $clave, int $productoId): float {
    $st = $db->prepare('SELECT existencias FROM inventario WHERE franquicia_clave = ? AND producto_id = ?');
    $st->execute([$clave, $productoId]);
    $row = $st->fetch();
    return $row ? (float)$row['existencias'] : 0.0;
}

try {
    if ($action === 'listar') {
        $fechaIni = trim($_GET['fecha_ini'] ?? date('Y-m-01'));
        $fechaFin = trim($_GET['fecha_fin'] ?? date('Y-m-d'));
        $almacen = trim($_GET['almacen'] ?? '');
        $productoId = (int)($_GET['producto_id'] ?? 0);

        $sql = "
            SELECT m.id, m.fecha, m.almacen_clave, m.producto_id, m.cantidad,
                   m.precio_venta, m.referencia, m.usuario, p.codigo, p.nombre AS producto
            FROM movimientos m
            LEFT JOIN productos p ON p.id = m.producto_id
            WHERE m.franquicia_clave = ?
              AND m.tipo = 'salida'
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
        $st = $db->prepare($sql);
        $st->execute($params);
        echo json_encode(['ok' => true, 'data' => $st->fetchAll()]);
        exit;
    }

    if ($action === 'registrar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $almacen = trim($_POST['almacen_clave'] ?? '');
        $productoId = (int)($_POST['producto_id'] ?? 0);
        $cantidad = (float)($_POST['cantidad'] ?? 0);
        $precio = trim($_POST['precio_venta'] ?? '') === '' ? null : (float)$_POST['precio_venta'];
        $tipoCliente = trim($_POST['tipo_cliente_id'] ?? '') === '' ? null : (int)$_POST['tipo_cliente_id'];
        $referencia = trim($_POST['referencia'] ?? '');
        $fecha = trim($_POST['fecha'] ?? date('Y-m-d'));
        $notas = trim($_POST['notas'] ?? '');

        if ($almacen === '' || $productoId <= 0 || $cantidad <= 0) {
            echo json_encode(['ok' => false, 'message' => 'Datos incompletos']);
            exit;
        }

        $db->beginTransaction();

        $actual = existenciasActuales($db, $almacen, $productoId);
        if ($actual < $cantidad) {
            throw new RuntimeException('Existencias insuficientes');
        }

        $ins = $db->prepare('INSERT INTO movimientos (franquicia_clave, tipo, producto_id, almacen_clave, cantidad, costo_unitario, precio_venta, referencia, tipo_cliente_id, tipo_gasto_id, motivo, fecha, notas, usuario, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())');
        $ins->execute([
            Session::$franquicia,
            'salida',
            $productoId,
            $almacen,
            $cantidad,
            null,
            $precio,
            $referencia,
            $tipoCliente,
            null,
            null,
            $fecha,
            $notas,
            Session::$username,
        ]);

        $up = $db->prepare('UPDATE inventario SET existencias = existencias - ? WHERE franquicia_clave = ? AND producto_id = ?');
        $up->execute([$cantidad, $almacen, $productoId]);

        $db->commit();
        echo json_encode(['ok' => true, 'message' => 'Salida registrada']);
        exit;
    }

    echo json_encode(['ok' => false, 'message' => 'Acción no válida']);
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode(['ok' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
