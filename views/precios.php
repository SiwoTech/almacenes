<?php
if (!Session::puedeVerCostos()) {
    Session::forbidden();
}

$subs = ['lista' => 'Lista de Precios'];
if (Session::puedeModificarCostos()) {
    $subs['actualizar'] = 'Actualizar Precios';
}

$sub = array_key_exists($_GET['sub'] ?? '', $subs) ? $_GET['sub'] : array_key_first($subs);
?>

<div class="sub-tabs">
    <?php foreach ($subs as $key => $label): ?>
    <a href="?<?= http_build_query(array_merge($_GET, ['sub' => $key])) ?>"
       class="sub-tab <?= $sub === $key ? 'active' : '' ?>"><?= $label ?></a>
    <?php endforeach; ?>
</div>

<div style="margin-top:20px;">
<?php
$archivo = __DIR__ . "/precios/{$sub}.php";
if (file_exists($archivo)) include $archivo;
else echo '<div class="alert alert-warning">⚠️ Módulo en construcción...</div>';
?>
</div>

<?php $js_file = $_SERVER['DOCUMENT_ROOT'] . "/almacenes/assets/js/precios/{$sub}.js"; if (file_exists($js_file)): ?>
<script src="/almacenes/assets/js/precios/<?= $sub ?>.js?v=<?= time() ?>"></script>
<?php endif; ?>
