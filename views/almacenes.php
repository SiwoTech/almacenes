<?php
Session::requireAny();

$subs = [
    'lista'       => 'Almacenes',
    'inventario'  => 'Inventario global',
];

// Solo CWO admin ve inventario global
if (!Session::isCWO() || !Session::isAdmin()) {
    unset($subs['inventario']);
}

$sub = array_key_exists($_GET['sub'] ?? '', $subs)
    ? $_GET['sub']
    : 'lista';
?>

<!-- Sub-tabs almacenes -->
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
    $archivo = __DIR__ . "/almacenes/{$sub}.php";
    if (file_exists($archivo)) {
        include $archivo;
    } else {
        echo '<div class="alert alert-warning">⚠️ Módulo en construcción...</div>';
    }
?>
</div>

<!-- JS del sub-módulo -->
<?php
$js_file = $_SERVER['DOCUMENT_ROOT'] . "/almacenes/assets/js/almacenes/{$sub}.js";
if (file_exists($js_file)): ?>
<script src="/almacenes/assets/js/almacenes/<?= $sub ?>.js?v=<?= time() ?>"></script>
<?php endif; ?>