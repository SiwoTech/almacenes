<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/db.php';

Session::init();
Session::requireAdmin();

header('Content-Type: application/json');

$db = DB::get('almacenes');
$action = $_GET['action'] ?? '';

function existenciasAjuste(PDO $db, string $clave, int $productoId): float {
    $st = $db->prepare('SELECT existencias FROM inventario WHERE franquicia_clave = ? AND producto_id = ?');
    $st->execute([$clave, $productoId]);
    $row = $st->fetch();
    return $row ? (float)$row['existencias'] : 0;
}

try {
    if ($action === 'listar') {
        $st = $db->prepare("SELECT m.id, m.fecha, m.almacen_clave, m.producto_id, m.cantidad, m.motivo, m.notas, m.usuario, p.nombre AS producto, p.codigo, m.referencia FROM movimientos m LEFT JOIN productos p ON p.id = m.producto_id WHERE m.franquicia_clave = ? AND m.tipo = 'ajuste' ORDER BY m.fecha DESC, m.id DESC LIMIT 500");
        $st->execute([Session::$franquicia]);
        echo json_encode(['ok' => true, 'data' => $st->fetchAll()]);
        exit;
    }

    if ($action === 'registrar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $almacen = trim($_POST['almacen_clave'] ?? '');
        $productoId = (int)($_POST['producto_id'] ?? 0);
        $tipoAjuste = trim($_POST['tipo_ajuste'] ?? '');
        $cantidad = (float)($_POST['cantidad'] ?? 0);
        $motivo = trim($_POST['motivo'] ?? 'otro');
        $fecha = trim($_POST['fecha'] ?? date('Y-m-d'));
        $notas = trim($_POST['notas'] ?? '');

        if ($almacen === '' || $productoId <= 0 || $cantidad <= 0 || !in_array($tipoAjuste, ['incremento', 'decremento'], true)) {
            echo json_encode(['ok' => false, 'message' => 'Datos incompletos']);
            exit;
        }

        $db->beginTransaction();

        $delta = $tipoAjuste === 'incremento' ? $cantidad : -$cantidad;
        if ($delta < 0 && existenciasAjuste($db, $almacen, $productoId) < $cantidad) {
            throw new RuntimeException('Existencias insuficientes para decremento');
        }

        $ins = $db->prepare('INSERT INTO movimientos (franquicia_clave, tipo, producto_id, almacen_clave, cantidad, costo_unitario, precio_venta, referencia, tipo_cliente_id, tipo_gasto_id, motivo, fecha, notas, usuario, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())');
        $ins->execute([
            Session::$franquicia,
            'ajuste',
            $productoId,
            $almacen,
            $cantidad,
            null,
            null,
            $tipoAjuste,
            null,
            null,
            $motivo,
            $fecha,
            $notas,
            Session::$username,
        ]);

        $up = $db->prepare('UPDATE inventario SET existencias = existencias + ? WHERE franquicia_clave = ? AND producto_id = ?');
        $up->execute([$delta, $almacen, $productoId]);
        if ($up->rowCount() === 0) {
            $ini = max(0, $delta);
            $insInv = $db->prepare('INSERT INTO inventario (franquicia_clave, producto_id, existencias) VALUES (?, ?, ?)');
            $insInv->execute([$almacen, $productoId, $ini]);
        }

        $db->commit();
        echo json_encode(['ok' => true, 'message' => 'Ajuste registrado']);
        exit;
    }

    echo json_encode(['ok' => false, 'message' => 'Acción no válida']);
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode(['ok' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
