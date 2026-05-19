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

        $tipo  = $_GET['tipo']  ?? '';
        $linea = (int)($_GET['linea'] ?? 0);
        $activo = $_GET['activo'] ?? '1';

        $where  = ['1=1'];
        $params = [];

        if ($tipo) {
            $where[]          = 'p.tipo = :tipo';
            $params[':tipo']  = $tipo;
        }
        if ($linea > 0) {
            $where[]           = 'p.linea_id = :linea';
            $params[':linea']  = $linea;
        }
        if ($activo !== 'todos') {
            $where[]            = 'p.activo = :activo';
            $params[':activo']  = (int)$activo;
        }

        $sql = "
            SELECT
                p.id, p.codigo, p.nombre, p.tipo, p.activo,
                p.costo_base, p.costo_promedio, p.ultimo_costo,
                p.stock_minimo, p.stock_maximo,
                p.ctrl_almacen, p.es_manufactura, p.solo_matriz,
                p.requiere_lote, p.requiere_serie,
                p.imagen,
                l.nombre  AS linea,
                um.abrev  AS unidad,
                tc.nombre AS tipo_costeo,
                ei.porcentaje AS impuesto_pct,
                mo.simbolo    AS moneda_simbolo
            FROM productos p
            LEFT JOIN lineas         l  ON l.id  = p.linea_id
            LEFT JOIN unidades_medida um ON um.id = p.unidad_medida_id
            LEFT JOIN tipos_costeo   tc ON tc.id  = p.tipo_costeo_id
            LEFT JOIN esquemas_impuesto ei ON ei.id = p.esquema_impuesto_id
            LEFT JOIN monedas        mo ON mo.id  = p.moneda_id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY p.nombre ASC
        ";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        Response::ok($stmt->fetchAll());

    // ── GET — detalle (un producto) ────────────────────────
    } elseif ($method === 'GET' && $action === 'detalle') {

        $id = (int)($_GET['id'] ?? 0);
        if (!$id) Response::error('ID inválido.', 422);

        $stmt = $db->prepare("
            SELECT p.*,
                l.nombre  AS linea_nombre,
                um.nombre AS unidad_nombre,
                um.abrev  AS unidad_abrev
            FROM productos p
            LEFT JOIN lineas          l  ON l.id  = p.linea_id
            LEFT JOIN unidades_medida um ON um.id = p.unidad_medida_id
            WHERE p.id = :id
        ");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        if (!$row) Response::error('Producto no encontrado.', 404);
        Response::ok($row);

    // ── GET — catálogos para el formulario ─────────────────
    } elseif ($method === 'GET' && $action === 'catalogos') {

        $lineas = $db->query("SELECT id, nombre FROM lineas WHERE activo=1 ORDER BY nombre")->fetchAll();
        $unidades = $db->query("SELECT id, nombre, abrev FROM unidades_medida ORDER BY nombre")->fetchAll();
        $tcosteo  = $db->query("SELECT id, nombre FROM tipos_costeo ORDER BY nombre")->fetchAll();
        $impuestos = $db->query("SELECT id, nombre, porcentaje FROM esquemas_impuesto ORDER BY porcentaje")->fetchAll();
        $monedas  = $db->query("SELECT id, nombre, simbolo FROM monedas ORDER BY nombre")->fetchAll();

        Response::ok([
            'lineas'    => $lineas,
            'unidades'  => $unidades,
            'tcosteo'   => $tcosteo,
            'impuestos' => $impuestos,
            'monedas'   => $monedas,
        ]);

    // ── POST — guardar (crear o editar) ────────────────────
    } elseif ($method === 'POST' && $action === 'guardar') {

        Session::requireAdmin();

        $id     = (int)($_POST['id']     ?? 0);
        $codigo = trim($_POST['codigo']  ?? '');
        $nombre = trim($_POST['nombre']  ?? '');
        $tipo   = trim($_POST['tipo']    ?? '');

        $tipos_validos = [
			'materia_prima',
			'producto_terminado',
			'refaccion',
			'maquina',
			'insumo',
			'uniforme',
			'herramienta',
			'envase',
			'servicio',
			'franquicia',
			'contable',
			'otro'
		];
        if (!$codigo || !$nombre) {
            Response::error('Código y nombre son obligatorios.', 422);
        }
        if (!in_array($tipo, $tipos_validos)) {
            Response::error('Tipo de producto no válido.', 422);
        }

        $campos = [
            'codigo'              => $codigo,
            'nombre'              => $nombre,
            'descripcion'         => trim($_POST['descripcion']   ?? '') ?: null,
            'tipo'                => $tipo,
            'linea_id'            => (int)($_POST['linea_id']            ?? 0) ?: null,
            'unidad_medida_id'    => (int)($_POST['unidad_medida_id']    ?? 0) ?: null,
            'tipo_costeo_id'      => (int)($_POST['tipo_costeo_id']      ?? 0) ?: null,
            'esquema_impuesto_id' => (int)($_POST['esquema_impuesto_id'] ?? 0) ?: null,
            'moneda_id'           => (int)($_POST['moneda_id']           ?? 0) ?: null,
            'costo_base'          => (float)($_POST['costo_base']        ?? 0),
            'stock_minimo'        => (float)($_POST['stock_minimo']      ?? 0),
            'stock_maximo'        => (float)($_POST['stock_maximo']      ?? 0),
            'ctrl_almacen'        => isset($_POST['ctrl_almacen'])   ? 1 : 0,
            'es_manufactura'      => isset($_POST['es_manufactura']) ? 1 : 0,
            'solo_matriz'         => isset($_POST['solo_matriz'])    ? 1 : 0,
            'requiere_lote'       => isset($_POST['requiere_lote'])  ? 1 : 0,
            'requiere_serie'      => isset($_POST['requiere_serie']) ? 1 : 0,
            'codigo_barras'       => trim($_POST['codigo_barras']    ?? '') ?: null,
            'codigo_sat'          => trim($_POST['codigo_sat']       ?? '') ?: null,
            'unidad_sat'          => trim($_POST['unidad_sat']       ?? '') ?: null,
            'peso_kg'             => trim($_POST['peso_kg']          ?? '') !== '' ? (float)$_POST['peso_kg'] : null,
        ];

        if ($id > 0) {
            // Verificar código único excluyendo este
            $st = $db->prepare("SELECT id FROM productos WHERE codigo = :c AND id != :id");
            $st->execute([':c' => $codigo, ':id' => $id]);
            if ($st->fetch()) Response::error('El código ya existe en otro producto.', 409);

            $sets = implode(', ', array_map(fn($k) => "`$k` = :$k", array_keys($campos)));
            $stmt = $db->prepare("UPDATE productos SET $sets, updated_by = :ub WHERE id = :id");
            $params = $campos;
            $params['ub'] = Session::userId();
            $params['id'] = $id;
            $stmt->execute($params);
            Response::ok(null, 'Producto actualizado.');

        } else {
            // Verificar código único
            $st = $db->prepare("SELECT id FROM productos WHERE codigo = :c");
            $st->execute([':c' => $codigo]);
            if ($st->fetch()) Response::error('El código ya existe.', 409);

            $cols   = implode(', ', array_map(fn($k) => "`$k`", array_keys($campos)));
            $phs    = implode(', ', array_map(fn($k) => ":$k", array_keys($campos)));
            $stmt   = $db->prepare("
                INSERT INTO productos ($cols, created_by, updated_by)
                VALUES ($phs, :cb, :ub)
            ");
            $params = $campos;
            $params['cb'] = Session::userId();
            $params['ub'] = Session::userId();
            $stmt->execute($params);
            Response::ok(['id' => $db->lastInsertId()], 'Producto creado.');
        }

    // ── POST — toggle activo ───────────────────────────────
    } elseif ($method === 'POST' && $action === 'toggle') {

        Session::requireAdmin();

        $id = (int)($_POST['id'] ?? 0);
        if (!$id) Response::error('ID inválido.', 422);

        $stmt = $db->prepare("
            UPDATE productos SET activo = NOT activo WHERE id = :id
        ");
        $stmt->execute([':id' => $id]);
        Response::ok(null, 'Estatus actualizado.');

    } else {
        Response::error('Acción no válida.', 400);
    }

} catch (PDOException $e) {
    Response::error('Error BD: ' . $e->getMessage(), 500);
}