<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/db.php';

Session::init();
Session::requireAny();

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$db     = DB::get('almacenes');

try {
    switch ($action) {

        // ── Listar stock ──────────────────────────────────
        case 'listar':
            $esCWO = Session::isCWO();

            $sql = "
                SELECT
                    i.id,
                    i.franquicia_clave,
                    p.codigo,
                    p.nombre        AS producto,
                    p.tipo,
                    l.nombre        AS linea,
                    um.abrev        AS unidad,
                    i.existencias,
                    i.stock_minimo,
                    i.stock_maximo,
                    i.ultima_actualizacion,
                    CASE
                        WHEN i.existencias <= 0              THEN 'sin_stock'
                        WHEN i.existencias <= i.stock_minimo THEN 'stock_bajo'
                        WHEN i.existencias >= i.stock_maximo THEN 'stock_alto'
                        ELSE 'stock_ok'
                    END AS estado_stock
                FROM inventario i
                JOIN productos p           ON p.id  = i.producto_id
                LEFT JOIN lineas l         ON l.id  = p.linea_id
                LEFT JOIN unidades_medida um ON um.id = p.unidad_medida_id
                WHERE p.activo = 1
            ";

            $params = [];
            if (!$esCWO) {
                $sql .= " AND i.franquicia_clave = ?";
                $params[] = Session::$franquicia;
            }

            $sql .= " ORDER BY i.franquicia_clave, p.tipo, p.nombre";

            $stmt = $db->prepare($sql);
            $stmt->execute($params);

            echo json_encode(['ok' => true, 'data' => $stmt->fetchAll()]);
            break;

        // ── Almacenes activos ─────────────────────────────
        case 'almacenes':
            $rows = $db->query("
                SELECT clave, tipo FROM almacenes
                WHERE activo = 1
                ORDER BY FIELD(tipo,'matriz','sucursal','franquicia','bodega'), clave
            ")->fetchAll();

            echo json_encode(['ok' => true, 'data' => $rows]);
            break;

        // ── Productos para carga inicial ──────────────────
        case 'productos_inicial':
            if (!Session::isCWO() || !Session::isAdmin()) {
                Session::forbidden();
            }

            $clave = trim($_GET['clave'] ?? '');
            if (!$clave) {
                echo json_encode(['ok' => false, 'message' => 'Falta clave de almacén']);
                exit;
            }

            $stmt = $db->prepare("
                SELECT
                    p.id,
                    p.codigo,
                    p.nombre,
                    p.tipo,
                    um.abrev                       AS unidad,
                    COALESCE(i.existencias,  0)    AS existencias,
                    COALESCE(i.stock_minimo, 0)    AS stock_minimo,
                    COALESCE(i.stock_maximo, 0)    AS stock_maximo,
                    IF(i.id IS NOT NULL, 1, 0)     AS tiene_registro
                FROM productos p
                LEFT JOIN unidades_medida um ON um.id = p.unidad_medida_id
                LEFT JOIN inventario i
                       ON i.producto_id    = p.id
                      AND i.franquicia_clave = ?
                WHERE p.activo = 1
                  AND p.ctrl_almacen = 1
                ORDER BY p.tipo, p.nombre
            ");
            $stmt->execute([$clave]);

            echo json_encode(['ok' => true, 'data' => $stmt->fetchAll()]);
            break;

        // ── Guardar carga inicial ─────────────────────────
        case 'guardar_inicial':
            if (!Session::isCWO() || !Session::isAdmin()) {
                Session::forbidden();
            }

            $body  = json_decode(file_get_contents('php://input'), true);
            $clave = trim($body['clave'] ?? '');
            $items = $body['items']      ?? [];

            if (!$clave || !is_array($items) || !count($items)) {
                echo json_encode(['ok' => false, 'message' => 'Datos incompletos']);
                exit;
            }

            // Verificar almacén
            $chk = $db->prepare("SELECT id FROM almacenes WHERE clave = ? AND activo = 1");
            $chk->execute([$clave]);
            if (!$chk->fetch()) {
                echo json_encode(['ok' => false, 'message' => 'Almacén no válido']);
                exit;
            }

            $db->beginTransaction();
            $guardados = 0;

            $upsert = $db->prepare("
                INSERT INTO inventario
                    (franquicia_clave, producto_id, existencias, stock_minimo, stock_maximo)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    existencias  = VALUES(existencias),
                    stock_minimo = VALUES(stock_minimo),
                    stock_maximo = VALUES(stock_maximo)
            ");

            foreach ($items as $item) {
                $pid   = (int)($item['producto_id']  ?? 0);
                $exist = (float)($item['existencias']  ?? 0);
                $min   = (float)($item['stock_minimo'] ?? 0);
                $max   = (float)($item['stock_maximo'] ?? 0);

                if (!$pid) continue;
                $upsert->execute([$clave, $pid, $exist, $min, $max]);
                $guardados++;
            }

            $db->commit();

            echo json_encode([
                'ok'        => true,
                'message'   => "{$guardados} productos actualizados en {$clave}",
                'guardados' => $guardados,
            ]);
            break;

        default:
            echo json_encode(['ok' => false, 'message' => 'Acción no válida']);
    }

} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    echo json_encode(['ok' => false, 'message' => 'Error: ' . $e->getMessage()]);
}