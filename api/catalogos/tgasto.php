<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/response.php';

Session::init();
Session::requireAny();

$db     = DB::get('almacenes');
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

$bases_validas = [
    'unidades_producidas',
    'unidades_enviadas',
    'horas_maquina',
    'peso_producido',
    'manual'
];

try {

    // ── GET — listar ───────────────────────────────────────
    if ($method === 'GET' && $action === 'listar') {

        $stmt = $db->query("
            SELECT id, nombre, descripcion, base_prorrateo, activo
            FROM tipos_gasto
            ORDER BY nombre ASC
        ");
        Response::ok($stmt->fetchAll());

    // ── POST — guardar (crear o editar) ────────────────────
    } elseif ($method === 'POST' && $action === 'guardar') {

        Session::requireAdmin();

        $id             = (int)($_POST['id']              ?? 0);
        $nombre         = trim($_POST['nombre']            ?? '');
        $descripcion    = trim($_POST['descripcion']       ?? '');
        $base_prorrateo = trim($_POST['base_prorrateo']    ?? '');

        if (!$nombre) {
            Response::error('El nombre es obligatorio.', 422);
        }
        if (!in_array($base_prorrateo, $bases_validas)) {
            Response::error('Base de prorrateo no válida.', 422);
        }

        if ($id > 0) {
            $stmt = $db->prepare("
                UPDATE tipos_gasto
                SET nombre = :nombre,
                    descripcion = :descripcion,
                    base_prorrateo = :base_prorrateo
                WHERE id = :id
            ");
            $stmt->execute([
                ':nombre'         => $nombre,
                ':descripcion'    => $descripcion ?: null,
                ':base_prorrateo' => $base_prorrateo,
                ':id'             => $id,
            ]);
            Response::ok(null, 'Tipo de gasto actualizado.');
        } else {
            $stmt = $db->prepare("
                INSERT INTO tipos_gasto (nombre, descripcion, base_prorrateo)
                VALUES (:nombre, :descripcion, :base_prorrateo)
            ");
            $stmt->execute([
                ':nombre'         => $nombre,
                ':descripcion'    => $descripcion ?: null,
                ':base_prorrateo' => $base_prorrateo,
            ]);
            Response::ok(['id' => $db->lastInsertId()], 'Tipo de gasto creado.');
        }

    // ── POST — toggle activo ───────────────────────────────
    } elseif ($method === 'POST' && $action === 'toggle') {

        Session::requireAdmin();

        $id = (int)($_POST['id'] ?? 0);
        if (!$id) Response::error('ID inválido.', 422);

        // Verificar gastos activos en períodos
        $stmt = $db->prepare("
            SELECT COUNT(*) FROM gastos_periodo
            WHERE tipo_gasto_id = :id
        ");
        $stmt->execute([':id' => $id]);
        if ($stmt->fetchColumn() > 0) {
            Response::error('No se puede desactivar: tiene gastos registrados en períodos.', 409);
        }

        $stmt = $db->prepare("
            UPDATE tipos_gasto SET activo = NOT activo WHERE id = :id
        ");
        $stmt->execute([':id' => $id]);
        Response::ok(null, 'Estatus actualizado.');

    } else {
        Response::error('Acción no válida.', 400);
    }

} catch (PDOException $e) {
    Response::error('Error BD: ' . $e->getMessage(), 500);
}