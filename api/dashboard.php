<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/response.php';

Session::init();
Session::requireAny();

$franquicia = Session::$franquicia;
$db = DB::get('almacenes');

try {
    // ── 1. KPIs Inventario ─────────────────────────────────
    $stmt = $db->prepare("
        SELECT
            COUNT(*)                                                      AS total_productos,
            SUM(CASE WHEN estado_stock = 'sin_stock'  THEN 1 ELSE 0 END) AS sin_stock,
            SUM(CASE WHEN estado_stock = 'stock_bajo' THEN 1 ELSE 0 END) AS stock_bajo,
            SUM(CASE WHEN estado_stock = 'stock_alto' THEN 1 ELSE 0 END) AS stock_alto,
            SUM(CASE WHEN estado_stock = 'stock_ok'   THEN 1 ELSE 0 END) AS stock_ok
        FROM v_inventario
        WHERE franquicia_clave = :franquicia
    ");
    $stmt->execute([':franquicia' => $franquicia]);
    $kpiInventario = $stmt->fetch();

    // ── 2. KPIs Movimientos (mes actual) ───────────────────
    $stmt = $db->prepare("
        SELECT
            COUNT(*)                                                       AS total_mes,
            SUM(CASE WHEN estatus = 'en_transito'             THEN 1 ELSE 0 END) AS en_transito,
            SUM(CASE WHEN tipo    = 'entrada_compra'          THEN 1 ELSE 0 END) AS entradas,
            SUM(CASE WHEN tipo LIKE 'salida%'                 THEN 1 ELSE 0 END) AS salidas
        FROM movimientos
        WHERE franquicia_clave       = :franquicia
          AND MONTH(fecha_movimiento) = MONTH(NOW())
          AND YEAR(fecha_movimiento)  = YEAR(NOW())
    ");
    $stmt->execute([':franquicia' => $franquicia]);
    $kpiMovimientos = $stmt->fetch();

    // ── 3. KPIs Producción (mes actual) ────────────────────
    // Columnas reales: fecha_inicio, estatus => borrador|en_proceso|terminada|cancelada
    $stmt = $db->prepare("
        SELECT
            COUNT(*)                                                            AS total_mes,
            SUM(CASE WHEN estatus = 'borrador'   THEN 1 ELSE 0 END)            AS borrador,
            SUM(CASE WHEN estatus = 'en_proceso' THEN 1 ELSE 0 END)            AS en_proceso,
            SUM(CASE WHEN estatus = 'terminada'  THEN 1 ELSE 0 END)            AS terminadas,
            SUM(CASE WHEN estatus = 'cancelada'  THEN 1 ELSE 0 END)            AS canceladas
        FROM ordenes_produccion
        WHERE franquicia_clave    = :franquicia
          AND MONTH(fecha_inicio)  = MONTH(NOW())
          AND YEAR(fecha_inicio)   = YEAR(NOW())
    ");
    $stmt->execute([':franquicia' => $franquicia]);
    $kpiProduccion = $stmt->fetch();

    // ── 4. Últimos 5 movimientos ───────────────────────────
    $stmt = $db->prepare("
        SELECT
            m.folio,
            m.tipo,
            m.estatus,
            m.fecha_movimiento,
            m.franquicia_origen_clave,
            m.franquicia_destino_clave,
            COUNT(md.id)     AS num_productos,
            SUM(md.cantidad) AS total_unidades
        FROM movimientos m
        JOIN movimientos_detalle md ON md.movimiento_id = m.id
        WHERE m.franquicia_clave = :franquicia
        GROUP BY
            m.id, m.folio, m.tipo, m.estatus,
            m.fecha_movimiento,
            m.franquicia_origen_clave,
            m.franquicia_destino_clave
        ORDER BY m.fecha_movimiento DESC
        LIMIT 5
    ");
    $stmt->execute([':franquicia' => $franquicia]);
    $ultMovimientos = $stmt->fetchAll();

    // ── 5. Top 5 stock crítico ─────────────────────────────
    $stmt = $db->prepare("
        SELECT codigo, producto, unidad,
               existencias, stock_minimo, estado_stock
        FROM v_inventario
        WHERE franquicia_clave = :franquicia
          AND estado_stock IN ('sin_stock','stock_bajo')
        ORDER BY existencias ASC
        LIMIT 5
    ");
    $stmt->execute([':franquicia' => $franquicia]);
    $stockCritico = $stmt->fetchAll();

    // ── 6. Período activo (solo admon/admin) ───────────────
    $periodoActivo = null;
    if (in_array(Session::$rol, [Session::ROL_ADMIN, Session::ROL_ADMON])) {
        $stmt = $db->prepare("
            SELECT nombre, fecha_inicio, fecha_fin, estatus
            FROM periodos_costeo
            WHERE franquicia_clave = :franquicia
              AND estatus IN ('abierto','en_revision')
            ORDER BY fecha_inicio DESC
            LIMIT 1
        ");
        $stmt->execute([':franquicia' => $franquicia]);
        $periodoActivo = $stmt->fetch();
    }

    Response::ok([
        'kpi_inventario'  => $kpiInventario,
        'kpi_movimientos' => $kpiMovimientos,
        'kpi_produccion'  => $kpiProduccion,
        'ult_movimientos' => $ultMovimientos,
        'stock_critico'   => $stockCritico,
        'periodo_activo'  => $periodoActivo,
    ]);

} catch (PDOException $e) {
    Response::error('Error en base de datos: ' . $e->getMessage(), 500);
}