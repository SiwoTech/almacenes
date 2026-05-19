<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/db.php';

Session::init();
header('Content-Type: application/json');

$db = DB::get('almacenes');
$action = $_GET['action'] ?? '';

try {
    if ($action === 'listar') {
        Session::requireCWO();
        $sql = "SELECT p.id AS producto_id, p.codigo, p.nombre AS producto, tc.id AS tipo_costeo_id, tc.nombre AS tipo_costeo, cp.costo_mp, cp.costo_mo, cp.costo_gi, cp.costo_total, cp.notas FROM productos p LEFT JOIN costos_producto cp ON cp.producto_id = p.id LEFT JOIN tipos_costeo tc ON tc.id = cp.tipo_costeo_id WHERE p.activo = 1 ORDER BY p.nombre";
        $rows = $db->query($sql)->fetchAll();
        echo json_encode(['ok' => true, 'data' => $rows]);
        exit;
    }

    if ($action === 'catalogos') {
        Session::requireCWO();
        $tipos = $db->query('SELECT id, nombre FROM tipos_costeo ORDER BY nombre')->fetchAll();
        echo json_encode(['ok' => true, 'data' => ['tipos' => $tipos]]);
        exit;
    }

    if ($action === 'guardar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        Session::requireCWOAdmin();

        $productoId = (int)($_POST['producto_id'] ?? 0);
        $tipoCosteoId = (int)($_POST['tipo_costeo_id'] ?? 0);
        $costoMp = (float)($_POST['costo_mp'] ?? 0);
        $costoMo = (float)($_POST['costo_mo'] ?? 0);
        $costoGi = (float)($_POST['costo_gi'] ?? 0);
        $costoTotal = (float)($_POST['costo_total'] ?? ($costoMp + $costoMo + $costoGi));
        $notas = trim($_POST['notas'] ?? '');

        if ($productoId <= 0 || $tipoCosteoId <= 0) {
            echo json_encode(['ok' => false, 'message' => 'Datos incompletos']);
            exit;
        }

        $sql = 'INSERT INTO costos_producto (producto_id, tipo_costeo_id, costo_mp, costo_mo, costo_gi, costo_total, notas, actualizado_por, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE costo_mp=VALUES(costo_mp), costo_mo=VALUES(costo_mo), costo_gi=VALUES(costo_gi), costo_total=VALUES(costo_total), notas=VALUES(notas), actualizado_por=VALUES(actualizado_por), updated_at=NOW()';
        $st = $db->prepare($sql);
        $st->execute([$productoId, $tipoCosteoId, $costoMp, $costoMo, $costoGi, $costoTotal, $notas, Session::$username]);

        echo json_encode(['ok' => true, 'message' => 'Costo guardado']);
        exit;
    }

    echo json_encode(['ok' => false, 'message' => 'Acción no válida']);
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
