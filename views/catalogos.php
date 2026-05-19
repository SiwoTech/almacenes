<?php
// Validar sub-módulo permitido
$subs = [
    'unidades'  => 'Unidades de medida',
    'lineas'    => 'Líneas',
    'tcosteo'   => 'Tipos de costeo',
    'impuestos' => 'Esquemas de impuesto',
    'monedas'   => 'Monedas',
    'tcliente'  => 'Tipos de cliente',
    'tgasto'    => 'Tipos de gasto',
    'productos' => 'Productos',
];

// Solo permite valores del array, evita path traversal
$sub = array_key_exists($_GET['sub'] ?? '', $subs)
    ? $_GET['sub']
    : 'unidades';
?>

<!-- Sub-tabs catálogos -->
<div class="sub-tabs">
    <?php foreach ($subs as $key => $label): ?>
    <a href="?<?= http_build_query(array_merge($_GET, ['sub' => $key])) ?>"
       class="sub-tab <?= $sub === $key ? 'active' : '' ?>">
        <?= $label ?>
    </a>
    <?php endforeach; ?>
</div>

<!-- Contenido del sub-módulo -->
<div style="margin-top:20px;">
<?php
    $archivo = __DIR__ . "/catalogos/{$sub}.php";
    if (file_exists($archivo)) {
        include $archivo;
    } else {
        echo '<div class="alert alert-warning">⚠️ Módulo en construcción...</div>';
    }
?>
</div>

<!-- JS del sub-módulo activo (solo el que corresponde) -->
<?php
$js_file = $_SERVER['DOCUMENT_ROOT'] . "/almacenes/assets/js/catalogos/{$sub}.js";
if (file_exists($js_file)): ?>
<script src="/almacenes/assets/js/catalogos/<?= $sub ?>.js?v=<?= time() ?>"></script>
<?php endif; ?>