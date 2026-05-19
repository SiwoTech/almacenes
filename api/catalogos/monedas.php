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
            SELECT id, nombre, simbolo, tipo_cambio
            FROM monedas
            ORDER BY nombre ASC
        ");
        Response::ok($stmt->fetchAll());

    // ── POST — guardar (crear o editar) ────────────────────
    } elseif ($method === 'POST' && $action === 'guardar') {

        Session::requireAdmin();

        $id          = (int)($_POST['id']           ?? 0);
        $nombre      = trim($_POST['nombre']         ?? '');
        $simbolo     = trim($_POST['simbolo']        ?? '');
        $tipo_cambio = (float)($_POST['tipo_cambio'] ?? 1);

        if (!$nombre || !$simbolo) {
            Response::error('Nombre y símbolo son obligatorios.', 422);
        }
        if ($tipo_cambio <= 0) {
            Response::error('El tipo de cambio debe ser mayor a 0.', 422);
        }

        if ($id > 0) {
            $stmt = $db->prepare("
                UPDATE monedas
                SET nombre = :nombre, simbolo = :simbolo, tipo_cambio = :tipo_cambio
                WHERE id = :id
            ");
            $stmt->execute([
                ':nombre'      => $nombre,
                ':simbolo'     => $simbolo,
                ':tipo_cambio' => $tipo_cambio,
                ':id'          => $id,
            ]);
            Response::ok(null, 'Moneda actualizada.');
        } else {
            $stmt = $db->prepare("
                INSERT INTO monedas (nombre, simbolo, tipo_cambio)
                VALUES (:nombre, :simbolo, :tipo_cambio)
            ");
            $stmt->execute([
                ':nombre'      => $nombre,
                ':simbolo'     => $simbolo,
                ':tipo_cambio' => $tipo_cambio,
            ]);
            Response::ok(['id' => $db->lastInsertId()], 'Moneda creada.');
        }

    // ── POST — eliminar ────────────────────────────────────
    } elseif ($method === 'POST' && $action === 'eliminar') {

        Session::requireAdmin();

        $id = (int)($_POST['id'] ?? 0);
        if (!$id) Response::error('ID inválido.', 422);

        $stmt = $db->prepare("
            SELECT COUNT(*) FROM productos WHERE moneda_id = :id
        ");
        $stmt->execute([':id' => $id]);
        if ($stmt->fetchColumn() > 0) {
            Response::error('No se puede eliminar: tiene productos asignados.', 409);
        }

        $stmt = $db->prepare("DELETE FROM monedas WHERE id = :id");
        $stmt->execute([':id' => $id]);
        Response::ok(null, 'Moneda eliminada.');

    } else {
        Response::error('Acción no válida.', 400);
    }

} catch (PDOException $e) {
    Response::error('Error BD: ' . $e->getMessage(), 500);
}