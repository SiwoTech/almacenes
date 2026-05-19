<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/db.php';

Session::init();
Session::requireAny();

header('Content-Type: application/json');

$db = DB::get('almacenes');
$action = $_GET['action'] ?? '';

function stockDisponible(PDO $db, string $clave, int $productoId): float {
    $st = $db->prepare('SELECT existencias FROM inventario WHERE franquicia_clave = ? AND producto_id = ?');
    $st->execute([$clave, $productoId]);
    $row = $st->fetch();
    return $row ? (float)$row['existencias'] : 0;
}

function incrementarInventario(PDO $db, string $clave, int $productoId, float $cantidad): void {
    $up = $db->prepare('UPDATE inventario SET existencias = existencias + ? WHERE franquicia_clave = ? AND producto_id = ?');
    $up->execute([$cantidad, $clave, $productoId]);
    if ($up->rowCount() === 0) {
        $ins = $db->prepare('INSERT INTO inventario (franquicia_clave, producto_id, existencias) VALUES (?, ?, ?)');
        $ins->execute([$clave, $productoId, $cantidad]);
    }
}

try {
    if ($action === 'listar') {
        $fechaIni = trim($_GET['fecha_ini'] ?? date('Y-m-01'));
        $fechaFin = trim($_GET['fecha_fin'] ?? date('Y-m-d'));
        $origen = trim($_GET['origen'] ?? '');
        $destino = trim($_GET['destino'] ?? '');

        $sql = "
            SELECT t.id, t.fecha, t.almacen_origen, t.almacen_destino, t.producto_id,
                   t.cantidad, t.notas, t.estado, t.usuario, p.nombre AS producto, p.codigo
            FROM transferencias t
            LEFT JOIN productos p ON p.id = t.producto_id
            WHERE t.franquicia_clave = ?
              AND t.fecha BETWEEN ? AND ?
        ";

        $params = [Session::$franquicia, $fechaIni, $fechaFin];
        if ($origen !== '') {
            $sql .= ' AND t.almacen_origen = ?';
            $params[] = $origen;
        }
        if ($destino !== '') {
            $sql .= ' AND t.almacen_destino = ?';
            $params[] = $destino;
        }

        $sql .= ' ORDER BY t.fecha DESC, t.id DESC LIMIT 500';
        $st = $db->prepare($sql);
        $st->execute($params);
        echo json_encode(['ok' => true, 'data' => $st->fetchAll()]);
        exit;
    }

    if ($action === 'registrar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $origen = trim($_POST['origen'] ?? '');
        $destino = trim($_POST['destino'] ?? '');
        $productoId = (int)($_POST['producto_id'] ?? 0);
        $cantidad = (float)($_POST['cantidad'] ?? 0);
        $fecha = trim($_POST['fecha'] ?? date('Y-m-d'));
        $notas = trim($_POST['notas'] ?? '');

        if ($origen === '' || $destino === '' || $productoId <= 0 || $cantidad <= 0) {
            echo json_encode(['ok' => false, 'message' => 'Datos incompletos']);
            exit;
        }
        if ($origen === $destino) {
            echo json_encode(['ok' => false, 'message' => 'Origen y destino no pueden ser iguales']);
            exit;
        }

        $db->beginTransaction();

        $disponible = stockDisponible($db, $origen, $productoId);
        if ($disponible < $cantidad) {
            throw new RuntimeException('Existencias insuficientes en almacén origen');
        }

        $ins = $db->prepare('INSERT INTO transferencias (franquicia_clave, almacen_origen, almacen_destino, producto_id, cantidad, fecha, notas, estado, usuario, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())');
        $ins->execute([
            Session::$franquicia,
            $origen,
            $destino,
            $productoId,
            $cantidad,
            $fecha,
            $notas,
            'en_transito',
            Session::$username,
        ]);

        $upOrigen = $db->prepare('UPDATE inventario SET existencias = existencias - ? WHERE franquicia_clave = ? AND producto_id = ?');
        $upOrigen->execute([$cantidad, $origen, $productoId]);
        incrementarInventario($db, $destino, $productoId, $cantidad);

        $db->commit();
        echo json_encode(['ok' => true, 'message' => 'Transferencia registrada']);
        exit;
    }

    echo json_encode(['ok' => false, 'message' => 'Acción no válida']);
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode(['ok' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
