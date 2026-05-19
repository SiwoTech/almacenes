<?php
$code = $_GET['code'] ?? '400';
$mensajes = [
    '401' => 'No autorizado — Accede desde el menú principal de Orange.',
    '403' => 'Sin permisos — Tu rol no tiene acceso a este módulo.',
    '404' => 'Página no encontrada.',
    '400' => 'Solicitud incorrecta.',
];
$msg = $mensajes[$code] ?? 'Error desconocido.';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Error <?= $code ?></title>
    <link rel="stylesheet" href="/almacenes/assets/css/almacenes.css">
</head>
<body>
<div class="container" style="text-align:center; padding-top: 80px;">
    <div style="font-size: 4rem;">⚠️</div>
    <h1 style="color:#ff6a00; margin: 16px 0;">Error <?= htmlspecialchars($code) ?></h1>
    <p style="color:#666; font-size:1rem;"><?= htmlspecialchars($msg) ?></p>
</div>
</body>
</html>