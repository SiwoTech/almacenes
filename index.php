<?php
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/db.php';

Session::init();
Session::requireAny();

$a = Session::$franquicia;
$b = Session::$username;
$c = Session::$rol;

$tab = $_GET['tab'] ?? 'dashboard';

$allowedTabs = ['dashboard','inventario','movimientos','produccion','precios','costeo','catalogos','almacenes'];
if (!in_array($tab, $allowedTabs, true)) $tab = 'dashboard';

// ── Proteger tabs por permisos ─────────────────────────
if ($tab === 'produccion' && !Session::puedeProducir())        $tab = 'dashboard';
if ($tab === 'precios'    && !Session::puedeVerCostos())       $tab = 'dashboard';
if ($tab === 'costeo'     && !Session::puedeVerCostos())       $tab = 'dashboard';
if ($tab === 'catalogos'  && !Session::puedeGestionarCatalogo()) $tab = 'dashboard';
if ($tab === 'almacenes'  && !Session::isAdmin())              $tab = 'dashboard';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Almacenes · <?= htmlspecialchars($a) ?></title>
    <link rel="stylesheet" href="/almacenes/assets/css/almacenes.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>

<?php include __DIR__ . '/includes/header-navigation.php'; ?>

<div class="container">

    <h1>Almacenes
        <span class="franquicia-badge">
            <i class="bi bi-geo-alt-fill"></i>
            <?= htmlspecialchars($a) ?>
        </span>
    </h1>

    <?php
    $viewFile = __DIR__ . "/views/{$tab}.php";
    if (file_exists($viewFile)) {
        require $viewFile;
    } else {
        echo '<p class="text-muted">Módulo en construcción.</p>';
    }
    ?>

</div>

<script src="/almacenes/assets/js/base.js"></script>
<?php
$js_tab = $_SERVER['DOCUMENT_ROOT'] . "/almacenes/assets/js/{$tab}.js";
if (file_exists($js_tab)): ?>
<script src="/almacenes/assets/js/<?= $tab ?>.js"></script>
<?php endif; ?>

</body>
</html>