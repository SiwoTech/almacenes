<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/db.php';

Session::init();
Session::requireAdmin();

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$db     = DB::get('almacenes');

try {
    switch ($action) {

        // ── Listar ────────────────────────────────────────
        case 'listar':
            // Query 1 — almacenes
            $almacenes = $db->query("
                SELECT id, clave, tipo, activo,
                       DATE_FORMAT(created_at, '%d/%m/%Y') AS creado
                FROM almacenes
                ORDER BY
                    FIELD(tipo,'matriz','sucursal','franquicia','bodega'),
                    clave
            ")->fetchAll(PDO::FETCH_ASSOC);

            // Query 2 — info franquicias desde cwofran
            $claves = array_column($almacenes, 'clave');
            $in     = implode(',', array_fill(0, count($claves), '?'));

            $dbFran  = DB::get('cwofran');
            $franMap = [];

            $stmt = $dbFran->prepare("
                SELECT franquicia, nombre_contacto, ciudad, estado
                FROM franquicias
                WHERE franquicia IN ({$in})
            ");
            $stmt->execute($claves);

            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $f) {
                $franMap[$f['franquicia']] = $f;
            }

            // Merge
            foreach ($almacenes as &$a) {
                $f = $franMap[$a['clave']] ?? [];
                $a['nombre_contacto'] = $f['nombre_contacto'] ?? null;
                $a['ciudad']          = $f['ciudad']          ?? null;
                $a['estado']          = $f['estado']          ?? null;
            }
            unset($a);

            echo json_encode(['ok' => true, 'data' => $almacenes]);
            break;

        // ── Guardar (insert / update) ─────────────────────
        case 'guardar':
            Session::requireCWOAdmin();

            $id    = (int)($_POST['id']    ?? 0);
            $clave = strtoupper(trim($_POST['clave'] ?? ''));
            $tipo  = trim($_POST['tipo']   ?? '');

            $tiposValidos = ['matriz','sucursal','franquicia','bodega'];

            if (!$clave || !preg_match('/^[A-Z0-9_\-]+$/', $clave)) {
                echo json_encode(['ok' => false, 'message' => 'Clave inválida']);
                exit;
            }
            if (!in_array($tipo, $tiposValidos, true)) {
                echo json_encode(['ok' => false, 'message' => 'Tipo inválido']);
                exit;
            }

            if ($id) {
                $stmt = $db->prepare("
                    UPDATE almacenes SET tipo = ? WHERE id = ?
                ");
                $stmt->execute([$tipo, $id]);
                echo json_encode(['ok' => true, 'message' => 'Almacén actualizado']);
            } else {
                $existe = $db->prepare("SELECT id FROM almacenes WHERE clave = ?");
                $existe->execute([$clave]);
                if ($existe->fetch()) {
                    echo json_encode(['ok' => false, 'message' => "La clave {$clave} ya existe"]);
                    exit;
                }
                $stmt = $db->prepare("
                    INSERT INTO almacenes (clave, tipo) VALUES (?, ?)
                ");
                $stmt->execute([$clave, $tipo]);
                echo json_encode(['ok' => true, 'message' => 'Almacén creado']);
            }
            break;

        // ── Toggle activo ─────────────────────────────────
        case 'toggle':
            Session::requireCWOAdmin();

            $id = (int)($_POST['id'] ?? 0);
            if (!$id) {
                echo json_encode(['ok' => false, 'message' => 'ID inválido']);
                exit;
            }

            $alm = $db->prepare("SELECT clave, activo FROM almacenes WHERE id = ?");
            $alm->execute([$id]);
            $row = $alm->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                echo json_encode(['ok' => false, 'message' => 'No encontrado']);
                exit;
            }
            if ($row['clave'] === 'CWO') {
                echo json_encode(['ok' => false, 'message' => 'No se puede desactivar la matriz']);
                exit;
            }

            $nuevo = $row['activo'] ? 0 : 1;
            $db->prepare("UPDATE almacenes SET activo = ? WHERE id = ?")
               ->execute([$nuevo, $id]);

            echo json_encode([
                'ok'      => true,
                'activo'  => $nuevo,
                'message' => $nuevo ? 'Almacén activado' : 'Almacén desactivado'
            ]);
            break;

        default:
            echo json_encode(['ok' => false, 'message' => 'Acción no válida']);
    }

} catch (Exception $e) {
    echo json_encode(['ok' => false, 'message' => 'Error: ' . $e->getMessage()]);
}