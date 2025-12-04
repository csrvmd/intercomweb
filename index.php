<?php
require_once 'utils.php';
$config = cargar_configuracion();
$user_ip = $_SERVER['REMOTE_ADDR'];

// Verifica acceso por IP
if (!$config || !usuario_ip_permitida($config, $user_ip)) {
    echo "<html><body style='text-align:center;padding:5em;background:#cccccc;
    font-family:Open Sans,Arial'><h2>ACCESO DENEGADO</h2>
    <p>Tu equipo ($user_ip) no está registrado.</p></body></html>";
    exit;
}

// Detectar sala seleccionada (por parámetro GET)
$salas = obtener_salas($config);
$sala_actual = isset($_GET['sala']) ? $_GET['sala'] : (count($salas) ? $salas[0]['clave'] : 'SALA_1');
$sala = false;
foreach ($salas as $s) if ($s['clave'] === $sala_actual) $sala = $s;
if (!$sala) $sala = $salas[0];

// Nombre de usuario (por IP) en la sala actual
$nombre_usuario = obtener_usuario_por_ip($config, $user_ip, $sala_actual);

// Obtener conectados en la sala activa
function obtener_ips_conectados($config, $sala_clave) {
    $conectados = [];
    global $user_ip; // simplificación; en real deberían ir por sesión
    if (isset($config[$sala_clave])) {
        foreach ($config[$sala_clave] as $k => $v) {
            if (strpos($k, 'boton_')===0 && $v) {
                $data = explode(',', $v);
                if (count($data)==2) {
                    $conectados[$data[1]] = $data[0];
                }
            }
        }
    }
    return $conectados;
}
$ips_conectados = obtener_ips_conectados($config, $sala_actual);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?php echo "INTERCOM " . htmlspecialchars($sala['nombre']); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="icono.png" type="image/png">
    <link rel="stylesheet" href="style.css">
    <script>
        // Para JS: pasar datos de usuario/sala
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
        <a href="salas.php"><img src="conf.png" alt="Config." title="Configuración" style="width:40px;"></a>
    </div>
</div>
<div style="width:100%;text-align:center;margin-bottom:1.2em;">
    <?php foreach ($salas as $s) { 
        echo "<a class='sala-tab' href='?sala={$s['clave']}'" . ($s['clave']==$sala_actual?" style='font-weight:bold;'":"") . ">".htmlspecialchars($s['nombre'])."</a>";
    } ?>
</div>
<div class="botonera">
<?php
// Botón de conferencia (1er botón, arriba izquierda)
echo "<button class='icombtn green' data-conf='1' style='background:#1976d2;color:#fff;' data-ip='ALL' data-user='CONFERENCIA' data-index='0' data-sala='{$sala_actual}' title='Llamar a todos en la sala'>TODOS</button>";
if (1%8==0) echo "<br>"; // Si lo deseas, ajusta aquí

// Renderiza 40 botones (5 filas x 8 columnas)
for ($i=1; $i<=40; $i++) {
    $b_key = "boton_$i";
    if (isset($config[$sala_actual][$b_key]) && $config[$sala_actual][$b_key]) {
        list($nombre,$ip) = explode(',',$config[$sala_actual][$b_key]);
        $nombre = trim($nombre);
        $nombre_btn = substr($nombre,0,6);
        // Estado visual
        $activo = array_key_exists($ip, $ips_conectados);
        $estado = ($activo ? "green" : "gray");
        // Detecta si el usuario está en línea (simple) para verde/rojo; para rojo deberás ajustar en JS cuando el botón está en uso.
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
</body>
</html>