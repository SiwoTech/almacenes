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
            SELECT id, nombre, abrev
            FROM unidades_medida
            ORDER BY nombre ASC
        ");
        Response::ok($stmt->fetchAll());

    // ── POST — guardar (crear o editar) ────────────────────
    } elseif ($method === 'POST' && $action === 'guardar') {

        Session::requireAdmin();

        $id     = (int)($_POST['id']     ?? 0);
        $nombre = trim($_POST['nombre']  ?? '');
        $abrev  = trim($_POST['abrev']   ?? '');

        if (!$nombre || !$abrev) {
            Response::error('Nombre y abreviatura son obligatorios.', 422);
        }

        if ($id > 0) {
            $stmt = $db->prepare("
                UPDATE unidades_medida
                SET nombre = :nombre, abrev = :abrev
                WHERE id = :id
            ");
            $stmt->execute([':nombre' => $nombre, ':abrev' => $abrev, ':id' => $id]);
            Response::ok(null, 'Unidad actualizada.');
        } else {
            $stmt = $db->prepare("
                INSERT INTO unidades_medida (nombre, abrev)
                VALUES (:nombre, :abrev)
            ");
            $stmt->execute([':nombre' => $nombre, ':abrev' => $abrev]);
            Response::ok(['id' => $db->lastInsertId()], 'Unidad creada.');
        }

    // ── POST — eliminar ────────────────────────────────────
    } elseif ($method === 'POST' && $action === 'eliminar') {

        Session::requireAdmin();

        $id = (int)($_POST['id'] ?? 0);
        if (!$id) Response::error('ID inválido.', 422);

        // Verificar que no tenga productos asignados
        $stmt = $db->prepare("
            SELECT COUNT(*) FROM productos WHERE unidad_medida_id = :id
        ");
        $stmt->execute([':id' => $id]);
        if ($stmt->fetchColumn() > 0) {
            Response::error('No se puede eliminar: tiene productos asignados.', 409);
        }

        $stmt = $db->prepare("DELETE FROM unidades_medida WHERE id = :id");
        $stmt->execute([':id' => $id]);
        Response::ok(null, 'Unidad eliminada.');

    } else {
        Response::error('Acción no válida.', 400);
    }

} catch (PDOException $e) {
    Response::error('Error BD: ' . $e->getMessage(), 500);
}