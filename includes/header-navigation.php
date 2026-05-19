<?php
$a = $_GET['a'] ?? '';
$b = $_GET['b'] ?? '';
$c = $_GET['c'] ?? '';

$current_page = basename($_SERVER['PHP_SELF']);
$tab_active   = $_GET['tab'] ?? 'dashboard';

$base_params = "?a=" . urlencode($a)
             . "&b=" . urlencode($b)
             . "&c=" . urlencode($c);
?>
<div class="nav-text-buttons">

    <a href="/almacenes/index.php<?= $base_params ?>&tab=dashboard"
       class="nav-text-btn <?= $tab_active === 'dashboard'   ? 'active' : '' ?>">
       Dashboard
    </a>

    <a href="/almacenes/index.php<?= $base_params ?>&tab=inventario"
       class="nav-text-btn <?= $tab_active === 'inventario'  ? 'active' : '' ?>">
       Inventario
    </a>

    <a href="/almacenes/index.php<?= $base_params ?>&tab=movimientos"
       class="nav-text-btn <?= $tab_active === 'movimientos' ? 'active' : '' ?>">
       Movimientos
    </a>

    <?php if (Session::puedeProducir()): ?>
    <a href="/almacenes/index.php<?= $base_params ?>&tab=produccion"
       class="nav-text-btn <?= $tab_active === 'produccion'  ? 'active' : '' ?>">
       Producción
    </a>
    <?php endif; ?>

    <?php if (Session::puedeVerCostos()): ?>
    <a href="/almacenes/index.php<?= $base_params ?>&tab=precios"
       class="nav-text-btn <?= $tab_active === 'precios'     ? 'active' : '' ?>">
       Precios
    </a>
    <a href="/almacenes/index.php<?= $base_params ?>&tab=costeo"
       class="nav-text-btn <?= $tab_active === 'costeo'      ? 'active' : '' ?>">
       Costeo
    </a>
    <?php endif; ?>

    <?php if (Session::puedeGestionarCatalogo()): ?>
    <a href="/almacenes/index.php<?= $base_params ?>&tab=catalogos"
       class="nav-text-btn <?= $tab_active === 'catalogos'   ? 'active' : '' ?>">
       Catálogos
    </a>
    <?php endif; ?>

    <?php if (Session::isAdmin()): ?>
    <a href="/almacenes/index.php<?= $base_params ?>&tab=almacenes"
       class="nav-text-btn <?= $tab_active === 'almacenes'   ? 'active' : '' ?>">
       Almacenes
    </a>
    <?php endif; ?>

</div>