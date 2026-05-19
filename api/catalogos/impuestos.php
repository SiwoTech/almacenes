<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/response.php';

Session::init();
Session::requireAny();

$db     = DB::get('almacenes');
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {

    // ── GET — listar ───────────────────────────────────────
    if ($method === 'GET' && $action === 'listar') {

        $stmt = $db->query("
            SELECT id, nombre, porcentaje
            FROM esquemas_impuesto
            ORDER BY porcentaje ASC
        ");
        Response::ok($stmt->fetchAll());

    // ── POST — guardar (crear o editar) ────────────────────
    } elseif ($method === 'POST' && $action === 'guardar') {

        Session::requireAdmin();

        $id         = (int)($_POST['id']          ?? 0);
        $nombre     = trim($_POST['nombre']        ?? '');
        $porcentaje = (float)($_POST['porcentaje'] ?? 0);

        if (!$nombre) {
            Response::error('El nombre es obligatorio.', 422);
        }
        if ($porcentaje < 0 || $porcentaje > 100) {
            Response::error('El porcentaje debe estar entre 0 y 100.', 422);
        }

        if ($id > 0) {
            $stmt = $db->prepare("
                UPDATE esquemas_impuesto
                SET nombre = :nombre, porcentaje = :porcentaje
                WHERE id = :id
            ");
            $stmt->execute([
                ':nombre'     => $nombre,
                ':porcentaje' => $porcentaje,
                ':id'         => $id,
            ]);
            Response::ok(null, 'Esquema actualizado.');
        } else {
            $stmt = $db->prepare("
                INSERT INTO esquemas_impuesto (nombre, porcentaje)
                VALUES (:nombre, :porcentaje)
            ");
            $stmt->execute([
                ':nombre'     => $nombre,
                ':porcentaje' => $porcentaje,
            ]);
            Response::ok(['id' => $db->lastInsertId()], 'Esquema creado.');
        }

    // ── POST — eliminar ────────────────────────────────────
    } elseif ($method === 'POST' && $action === 'eliminar') {

        Session::requireAdmin();

        $id = (int)($_POST['id'] ?? 0);
        if (!$id) Response::error('ID inválido.', 422);

        $stmt = $db->prepare("
            SELECT COUNT(*) FROM productos WHERE esquema_impuesto_id = :id
        ");
        $stmt->execute([':id' => $id]);
        if ($stmt->fetchColumn() > 0) {
            Response::error('No se puede eliminar: tiene productos asignados.', 409);
        }

        $stmt = $db->prepare("DELETE FROM esquemas_impuesto WHERE id = :id");
        $stmt->execute([':id' => $id]);
        Response::ok(null, 'Esquema eliminado.');

    } else {
        Response::error('Acción no válida.', 400);
    }

} catch (PDOException $e) {
    Response::error('Error BD: ' . $e->getMessage(), 500);
}