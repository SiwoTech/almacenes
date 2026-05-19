<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/db.php';

Session::init();
Session::requireAny();

header('Content-Type: application/json');

$db = DB::get('almacenes');
$action = $_GET['action'] ?? '';
const EPSILON_DE_DIFERENCIA = 0.000001;

try {
    if ($action !== 'sincronizar' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['ok' => false, 'message' => 'Acción no válida']);
        exit;
    }

    $body = json_decode(file_get_contents('php://input'), true);
    $clave = trim($body['clave'] ?? '');
    $items = $body['items'] ?? [];

    if ($clave === '' || !is_array($items)) {
        echo json_encode(['ok' => false, 'message' => 'Datos incompletos']);
        exit;
    }

    $db->beginTransaction();

    $insMov = $db->prepare('INSERT INTO movimientos (franquicia_clave, tipo, producto_id, almacen_clave, cantidad, costo_unitario, precio_venta, referencia, tipo_cliente_id, tipo_gasto_id, motivo, fecha, notas, usuario, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())');
    $upInv = $db->prepare('UPDATE inventario SET existencias = existencias + ? WHERE franquicia_clave = ? AND producto_id = ?');
    $insInv = $db->prepare('INSERT INTO inventario (franquicia_clave, producto_id, existencias) VALUES (?, ?, ?)');

    $ajustados = 0;
    $sinDiferencia = 0;

    foreach ($items as $item) {
        $productoId = (int)($item['producto_id'] ?? 0);
        $contado = (float)($item['contado'] ?? 0);
        $sistema = (float)($item['sistema'] ?? 0);
        $diferencia = isset($item['diferencia']) ? (float)$item['diferencia'] : ($contado - $sistema);

        if ($productoId <= 0) {
            continue;
        }

        if (abs($diferencia) < EPSILON_DE_DIFERENCIA) {
            $sinDiferencia++;
            continue;
        }

        $insMov->execute([
            Session::$franquicia,
            'ajuste',
            $productoId,
            $clave,
            abs($diferencia),
            null,
            null,
            'conteo_fisico',
            null,
            null,
            'conteo_fisico',
            date('Y-m-d'),
            'Conteo físico PWA',
            Session::$username,
        ]);

        $upInv->execute([$diferencia, $clave, $productoId]);
        if ($upInv->rowCount() === 0) {
            $insInv->execute([$clave, $productoId, max(0, $diferencia)]);
        }

        $ajustados++;
    }

    $db->commit();
    echo json_encode(['ok' => true, 'ajustados' => $ajustados, 'sin_diferencia' => $sinDiferencia]);
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode(['ok' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
