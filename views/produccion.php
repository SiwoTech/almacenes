<?php
if (!Session::puedeProducir()) Session::forbidden();

$subs = [
    'formulas'  => 'Fórmulas',
    'ordenes'   => 'Órdenes de Producción',
    'historial' => 'Historial',
];

$sub = array_key_exists($_GET['sub'] ?? '', $subs)
    ? $_GET['sub']
    : 'formulas';
?>

<div class="sub-tabs">
    <?php foreach ($subs as $key => $label): ?>
    <a href="?<?= http_build_query(array_merge($_GET, ['sub' => $key])) ?>"
       class="sub-tab <?= $sub === $key ? 'active' : '' ?>">
        <?= $label ?>
    </a>
    <?php endforeach; ?>
</div>

<div style="margin-top:20px;">
<?php
    $archivo = __DIR__ . "/produccion/{$sub}.php";
    if (file_exists($archivo)) {
        include $archivo;
    } else {
        echo '<div class="alert alert-warning">⚠️ Módulo en construcción...</div>';
    }
?>
</div>

<?php
$js_file = $_SERVER['DOCUMENT_ROOT'] . "/almacenes/assets/js/produccion/{$sub}.js";
if (file_exists($js_file)): ?>
<script src="/almacenes/assets/js/produccion/<?= $sub ?>.js?v=<?= time() ?>"></script>
<?php endif; ?>
