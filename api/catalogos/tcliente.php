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
            SELECT id, nombre, descripcion, activo
            FROM tipos_cliente
            ORDER BY nombre ASC
        ");
        Response::ok($stmt->fetchAll());

    // ── POST — guardar (crear o editar) ────────────────────
    } elseif ($method === 'POST' && $action === 'guardar') {

        Session::requireAdmin();

        $id          = (int)($_POST['id']           ?? 0);
        $nombre      = trim($_POST['nombre']         ?? '');
        $descripcion = trim($_POST['descripcion']    ?? '');

        if (!$nombre) {
            Response::error('El nombre es obligatorio.', 422);
        }

        if ($id > 0) {
            $stmt = $db->prepare("
                UPDATE tipos_cliente
                SET nombre = :nombre, descripcion = :descripcion
                WHERE id = :id
            ");
            $stmt->execute([
                ':nombre'      => $nombre,
                ':descripcion' => $descripcion ?: null,
                ':id'          => $id,
            ]);
            Response::ok(null, 'Tipo de cliente actualizado.');
        } else {
            $stmt = $db->prepare("
                INSERT INTO tipos_cliente (nombre, descripcion)
                VALUES (:nombre, :descripcion)
            ");
            $stmt->execute([
                ':nombre'      => $nombre,
                ':descripcion' => $descripcion ?: null,
            ]);
            Response::ok(['id' => $db->lastInsertId()], 'Tipo de cliente creado.');
        }

    // ── POST — toggle activo ───────────────────────────────
    } elseif ($method === 'POST' && $action === 'toggle') {

        Session::requireAdmin();

        $id = (int)($_POST['id'] ?? 0);
        if (!$id) Response::error('ID inválido.', 422);

        // Verificar que no tenga listas de precios activas
        $stmt = $db->prepare("
            SELECT COUNT(*) FROM listas_precios_base
            WHERE tipo_cliente_id = :id AND activo = 1
        ");
        $stmt->execute([':id' => $id]);
        if ($stmt->fetchColumn() > 0) {
            Response::error('No se puede desactivar: tiene listas de precios activas.', 409);
        }

        $stmt = $db->prepare("
            UPDATE tipos_cliente SET activo = NOT activo WHERE id = :id
        ");
        $stmt->execute([':id' => $id]);
        Response::ok(null, 'Estatus actualizado.');

    } else {
        Response::error('Acción no válida.', 400);
    }

} catch (PDOException $e) {
    Response::error('Error BD: ' . $e->getMessage(), 500);
}