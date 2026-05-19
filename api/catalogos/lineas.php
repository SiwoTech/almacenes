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
            SELECT id, nombre, activo
            FROM lineas
            ORDER BY nombre ASC
        ");
        Response::ok($stmt->fetchAll());

    // ── POST — guardar (crear o editar) ────────────────────
    } elseif ($method === 'POST' && $action === 'guardar') {

        Session::requireAdmin();

        $id     = (int)($_POST['id']     ?? 0);
        $nombre = trim($_POST['nombre']  ?? '');

        if (!$nombre) {
            Response::error('El nombre es obligatorio.', 422);
        }

        if ($id > 0) {
            $stmt = $db->prepare("
                UPDATE lineas SET nombre = :nombre WHERE id = :id
            ");
            $stmt->execute([':nombre' => $nombre, ':id' => $id]);
            Response::ok(null, 'Línea actualizada.');
        } else {
            $stmt = $db->prepare("
                INSERT INTO lineas (nombre) VALUES (:nombre)
            ");
            $stmt->execute([':nombre' => $nombre]);
            Response::ok(['id' => $db->lastInsertId()], 'Línea creada.');
        }

    // ── POST — toggle activo ───────────────────────────────
    } elseif ($method === 'POST' && $action === 'toggle') {

        Session::requireAdmin();

        $id = (int)($_POST['id'] ?? 0);
        if (!$id) Response::error('ID inválido.', 422);

        // Verificar productos asignados antes de desactivar
        $stmt = $db->prepare("
            SELECT COUNT(*) FROM productos WHERE linea_id = :id AND activo = 1
        ");
        $stmt->execute([':id' => $id]);
        if ($stmt->fetchColumn() > 0) {
            Response::error('No se puede desactivar: tiene productos activos asignados.', 409);
        }

        $stmt = $db->prepare("
            UPDATE lineas SET activo = NOT activo WHERE id = :id
        ");
        $stmt->execute([':id' => $id]);
        Response::ok(null, 'Estatus actualizado.');

    } else {
        Response::error('Acción no válida.', 400);
    }

} catch (PDOException $e) {
    Response::error('Error BD: ' . $e->getMessage(), 500);
}