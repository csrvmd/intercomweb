<?php
require_once 'utils.php';
$config = cargar_configuracion();
$user_ip = $_SERVER['REMOTE_ADDR'];
if (!$config || !usuario_ip_permitida($config, $user_ip)) {
    header("Location: index.php");
    exit;
}

$salas = obtener_salas($config);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Salas - Intercom</title>
    <link rel="stylesheet" href="style.css">
</head>
<body style="background:#cccccc;">
<div style="max-width:650px;margin:4em auto;text-align:center;">
    <img src="logo.svg" alt="logo" style="width:120px;">
    <h2>Salas disponibles</h2>
    <?php foreach ($salas as $s) {
        echo "<div style='margin-bottom:2em;'><a class='sala-tab' style='display:inline-block;' href='index.php?sala={$s['clave']}'>".htmlspecialchars($s['nombre'])."</a><br><span>{$s['titulo']}</span></div>";
    } ?>
</div>
</body>
</html>