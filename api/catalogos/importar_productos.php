<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/response.php';

Session::init();
Session::requireAdmin();

$db     = DB::get('almacenes');
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// ── Tipos válidos ──────────────────────────────────────────
const TIPOS_VALIDOS = [
    'materia_prima', 'producto_terminado',
    'refaccion', 'maquina', 'insumo',
    'uniforme', 'herramienta', 'envase',
    'servicio', 'franquicia', 'contable',
    'otro'
];

try {

    // ── POST — previsualizar ───────────────────────────────
    if ($method === 'POST' && $action === 'previsualizar') {

        $resultado = parsearCSV();
        Response::ok($resultado);

    // ── POST — confirmar importación ───────────────────────
    } elseif ($method === 'POST' && $action === 'confirmar') {

        $filas = json_decode($_POST['filas'] ?? '[]', true);
        if (!$filas) Response::error('No hay filas para importar.', 422);

        // Cargar catálogos a memoria para resolver nombres → ids
        $cats = cargarCatalogos($db);

        $insertados  = 0;
        $errores     = [];

        $db->beginTransaction();

        foreach ($filas as $i => $fila) {

            $num = $i + 2; // número de fila real en CSV (1=header)

            // Resolver FK por nombre
            $linea_id            = resolverFK($cats['lineas'],    $fila['linea']      ?? '');
            $unidad_id           = resolverFK($cats['unidades'],   $fila['unidad']     ?? '');
            $tcosteo_id          = resolverFK($cats['tcosteo'],    $fila['tipo_costeo']?? '');

            $codigo = trim($fila['codigo'] ?? '');
            $nombre = trim($fila['nombre'] ?? '');
            $tipo   = trim($fila['tipo']   ?? '');

            if (!$codigo || !$nombre || !in_array($tipo, TIPOS_VALIDOS)) {
                $errores[] = "Fila $num: datos inválidos (código, nombre o tipo).";
                continue;
            }

            // Verificar si ya existe
            $st = $db->prepare("SELECT id FROM productos WHERE codigo = :c");
            $st->execute([':c' => $codigo]);
            if ($st->fetch()) {
                $errores[] = "Fila $num: código '$codigo' ya existe, se omite.";
                continue;
            }

            $st = $db->prepare("
                INSERT INTO productos (
                    codigo, nombre, tipo,
                    linea_id, unidad_medida_id, tipo_costeo_id,
                    costo_base, stock_minimo, stock_maximo,
                    codigo_barras, codigo_sat, unidad_sat, peso_kg,
                    ctrl_almacen, es_manufactura, requiere_lote, requiere_serie,
                    activo, created_by, updated_by
                ) VALUES (
                    :codigo, :nombre, :tipo,
                    :linea_id, :unidad_id, :tcosteo_id,
                    :costo_base, :stock_min, :stock_max,
                    :barras, :csat, :usat, :peso,
                    :ctrl, :manuf, :lote, :serie,
                    1, :cb, :ub
                )
            ");

            $st->execute([
				':codigo'     => $codigo,
				':nombre'     => $nombre,
				':tipo'       => $tipo,
				':linea_id'   => $linea_id,
				':unidad_id'  => $unidad_id,
				':tcosteo_id' => $tcosteo_id,
				':costo_base' => (float)($fila['costo_base']  ?? 0),
				':stock_min'  => (float)($fila['stock_minimo'] ?? 0),
				':stock_max'  => (float)($fila['stock_maximo'] ?? 0),
				':barras'     => trim($fila['codigo_barras'] ?? '') ?: null,
				':csat'       => trim($fila['codigo_sat']    ?? '') ?: null,
				':usat'       => trim($fila['unidad_sat']    ?? '') ?: null,
				':peso'       => trim($fila['peso_kg']       ?? '') !== '' ? (float)$fila['peso_kg'] : null,
				':ctrl'       => (int)($fila['ctrl_almacen']   ?? 1),
				':manuf'      => (int)($fila['es_manufactura'] ?? 0),
				':lote'       => (int)($fila['requiere_lote']  ?? 0),
				':serie'      => (int)($fila['requiere_serie'] ?? 0),
				':cb'         => null,
				':ub'         => null,
			]);

            $insertados++;
        }

        $db->commit();

        Response::ok([
            'insertados' => $insertados,
            'errores'    => $errores,
        ], "$insertados producto(s) importado(s).");

    } else {
        Response::error('Acción no válida.', 400);
    }

} catch (PDOException $e) {
    if ($db->inTransaction()) $db->rollBack();
    Response::error('Error BD: ' . $e->getMessage(), 500);
}

// ══════════════════════════════════════════════════════════
// Funciones auxiliares
// ══════════════════════════════════════════════════════════

function parsearCSV(): array {
    if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== 0) {
        Response::error('No se recibió el archivo.', 422);
    }

    $tmp  = $_FILES['archivo']['tmp_name'];
    $ext  = strtolower(pathinfo($_FILES['archivo']['name'], PATHINFO_EXTENSION));

    if ($ext !== 'csv') {
        Response::error('Solo se aceptan archivos CSV.', 422);
    }

    $handle = fopen($tmp, 'r');
    if (!$handle) Response::error('No se pudo leer el archivo.', 500);

    // Leer encabezados
    $headers = fgetcsv($handle, 0, ',');
    if (!$headers) Response::error('El archivo está vacío.', 422);

    // Normalizar headers (quitar BOM, espacios)
    $headers = array_map(fn($h) => trim(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $h)), $headers);

    $columnas_requeridas = ['codigo', 'nombre', 'tipo'];
    foreach ($columnas_requeridas as $col) {
        if (!in_array($col, $headers)) {
            Response::error("Falta columna obligatoria: '$col'.", 422);
        }
    }

    $filas   = [];
    $errores = [];
    $num     = 1;

    while (($row = fgetcsv($handle, 0, ',')) !== false) {
        $num++;

        // Ignorar filas vacías
        if (empty(array_filter($row, fn($v) => trim($v) !== ''))) continue;

        $data = [];
        foreach ($headers as $idx => $col) {
            $data[$col] = trim($row[$idx] ?? '');
        }

        // Validaciones básicas
        $errs = [];
        if (empty($data['codigo'])) $errs[] = 'código vacío';
        if (empty($data['nombre'])) $errs[] = 'nombre vacío';
        if (!in_array($data['tipo'] ?? '', TIPOS_VALIDOS)) {
            $errs[] = "tipo inválido ({$data['tipo']}) — válidos: " . implode(', ', TIPOS_VALIDOS);
        }
        if (isset($data['costo_base']) && $data['costo_base'] !== '' && !is_numeric($data['costo_base'])) {
            $errs[] = 'costo_base no es número';
        }

        $filas[] = [
            'num'    => $num,
            'data'   => $data,
            'errores'=> $errs,
            'ok'     => empty($errs),
        ];

        if (!empty($errs)) {
            $errores[] = "Fila $num: " . implode(', ', $errs);
        }
    }

    fclose($handle);

    return [
        'filas'   => $filas,
        'errores' => $errores,
        'total'   => count($filas),
        'validas' => count(array_filter($filas, fn($f) => $f['ok'])),
    ];
}

function cargarCatalogos($db): array {
    $lineas   = $db->query("SELECT id, LOWER(nombre) AS nombre FROM lineas")->fetchAll(PDO::FETCH_KEY_PAIR);
    $unidades = $db->query("SELECT id, LOWER(abrev) AS abrev FROM unidades_medida")->fetchAll(PDO::FETCH_KEY_PAIR);
    $tcosteo  = $db->query("SELECT id, LOWER(nombre) AS nombre FROM tipos_costeo")->fetchAll(PDO::FETCH_KEY_PAIR);
    return [
        'lineas'   => array_flip($lineas),
        'unidades' => array_flip($unidades),
        'tcosteo'  => array_flip($tcosteo),
    ];
}

function resolverFK(array $cat, string $valor): ?int {
    if ($valor === '') return null;
    return $cat[strtolower(trim($valor))] ?? null;
}