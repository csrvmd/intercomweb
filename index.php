<?php
require_once 'utils.php';
$config = cargar_configuracion();
$user_ip = $_SERVER['REMOTE_ADDR'];
$salas = obtener_salas($config);
$sala_actual = isset($_GET['sala']) ? $_GET['sala'] : (count($salas) ? $salas[0]['clave'] : 'SALA_1');

// Verifica acceso por IP
if (!$config || !usuario_ip_permitida($config, $user_ip)) {
    echo "<html><body style='text-align:center;padding:5em;background:#cccccc;
    font-family:Open Sans,Arial'><h2>ACCESO DENEGADO</h2>
    <p>Tu equipo ($user_ip) no está registrado.</p></body></html>";
    exit;
}

// Nombre de usuario (por IP) en la sala actual
$nombre_usuario = obtener_usuario_por_ip($config, $user_ip, $sala_actual);

// Función para consultar estado de presencia por IP (lee archivos de /presence)
function estado_presencia($ip) {
    $f = __DIR__ . '/presence/' . $ip . '.txt';
    if (file_exists($f)) {
        $last = intval(file_get_contents($f));
        return (time() - $last <= 60) ? "green" : "gray";
    }
    return "gray";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?php echo "INTERCOM " . htmlspecialchars($sala_actual); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="icono.png" type="image/png">
    <link rel="stylesheet" href="style.css">
    <script>
        window.__salaActivo = "<?php echo htmlspecialchars($sala_actual); ?>";
        window.__miIP = "<?php echo htmlspecialchars($user_ip); ?>";
        window.__miUser = "<?php echo htmlspecialchars($nombre_usuario); ?>";
    </script>
    <script src="audio.js"></script>
</head>
<body style="background:#cccccc;" data-ip="<?php echo htmlspecialchars($user_ip); ?>" data-user="<?php echo htmlspecialchars($nombre_usuario); ?>" data-sala="<?php echo htmlspecialchars($sala_actual); ?>">
<div id="header" style="display:flex;align-items:center;padding:1em;">
    <img src="logo.svg" alt="Empresa" style="width:200px;height:auto;margin-right:2em;">
    <h1><?php echo htmlspecialchars($config['GENERAL']['titulo']); ?></h1>
    <div style="margin-left:auto;display:flex;align-items:center;">
        <img src="altavoz.png" id="audio_config" title="Configurar audio" style="width:40px;cursor:pointer;margin-right:20px;">
        <a href="config.php"><img src="conf.png" alt="Config." title="Configuración" style="width:40px;"></a>
    </div>
</div>
<div style="width:100%;text-align:center;margin-bottom:1.2em;">
    <?php foreach ($salas as $s) { 
        echo "<a class='sala-tab' href='?sala={$s['clave']}'" . ($s['clave']==$sala_actual?" style='font-weight:bold;'":"") . ">".htmlspecialchars($s['nombre'])."</a>";
    } ?>
</div>
<div class="botonera">
<?php
// Botón de conferencia (primero)
echo "<button class='icombtn green' data-conf='1' style='background:#1976d2;color:#fff;' data-ip='ALL' data-user='CONFERENCIA' data-index='0' data-sala='{$sala_actual}' title='Llamar a todos en la sala'>TODOS</button>";
if (1%8==0) echo "<br>";

// Render 40 botones
for ($i=1; $i<=40; $i++) {
    $b_key = "boton_$i";
    if (isset($config[$sala_actual][$b_key]) && $config[$sala_actual][$b_key]) {
        list($nombre,$ip) = explode(',',$config[$sala_actual][$b_key]);
        $nombre = trim($nombre);
        $nombre_btn = substr($nombre,0,6);
        $estado = estado_presencia($ip); // verde/gris por presencia
        echo "<button class='icombtn $estado' ".
             "data-ip='$ip' data-user='$nombre' data-index='$i' data-sala='$sala_actual'>$nombre_btn</button>";
    } else {
        $estado = "gray";
        $nombre_btn = "$i USER";
        echo "<button class='icombtn $estado' data-index='$i' data-sala='$sala_actual'>$nombre_btn</button>";
    }
    if ($i%8==0) echo "<br>";
}
?>
</div>
<script src="audio.js"></script>
<script>
// fetch de presence cada 10s para esta IP
setInterval(()=> {
    fetch('presence.php', {method:'POST', body:new URLSearchParams({ip:window.__miIP})});
}, 10000);

// recargar la página si hay cambios de presencia cada 20s
setInterval(() => {
    fetch('presence.php?ip=' + window.__miIP)
        .then(r => r.text())
        .then(state => {
            // Si no estás online, forzar gris (puedes personalizar aquí)
            // También podrías recargar para ver cambios de presencia en otros usuarios
        });
}, 20000);
</script>
</body>
</html>