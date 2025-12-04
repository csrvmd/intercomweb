<?php
require_once 'utils.php';
session_start();

function is_logged_in() {
    return isset($_SESSION['admin']) && $_SESSION['admin'] === true;
}

// Login
if (isset($_POST['clave'])) {
    $cfg = cargar_configuracion();
    if ($cfg && trim($_POST['clave']) === trim($cfg['GENERAL']['contraseña'])) {
        $_SESSION['admin'] = true;
    } else {
        $error = "Clave incorrecta.";
    }
}

// Restaurar backup
if (is_logged_in() && isset($_POST['restore']) && file_exists($_POST['restore'])) {
    copy($_POST['restore'], CONFIG_FILE);
    header("Location: config.php?restored=1");
    exit;
}

// Guardar nueva configuración
if (is_logged_in() && isset($_POST['nconfig'])) {
    if (guardar_configuracion($_POST['nconfig'])) {
        header("Location: config.php?saved=1");
        exit;
    } else {
        $error = "Error al guardar la configuración.";
    }
}

// Logout
if (isset($_GET['logout'])) {
    unset($_SESSION['admin']);
    session_destroy();
    header("Location: config.php");
    exit;
}

$backups = glob(BACKUP_DIR . "config_*.txt");
$backups = array_reverse($backups);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Configuración Intercom</title>
    <link rel="stylesheet" href="style.css">
</head>
<body style="background:#cccccc;">
<div style="max-width:800px;margin:2em auto;padding:2em;background:#fff;border-radius:12px;">
    <img src="logo.svg" alt="logo" style="width:120px;">
    <h2>Administración</h2>

<?php if (!is_logged_in()): ?>
    <form method="post">
        <label>Contraseña:</label>
        <input type="password" name="clave" autocomplete="off">
        <button type="submit">Acceder</button>
    </form>
    <?php if (!empty($error)) echo "<p style='color:red;'>$error</p>"; ?>
<?php else: ?>
    <form method="post">
        <label>Editar configuración (<b>config.txt</b>):</label><br>
        <textarea name="nconfig" style="width:100%;height:340px;font-family:monospace;"><?php
        echo htmlspecialchars(file_get_contents(CONFIG_FILE));
        ?></textarea><br>
        <button type="submit">Guardar cambios</button>
        <a style="margin-left:2em;color:#EA3232;" href="?logout=1">Salir</a>
        <?php if (isset($_GET['saved'])) echo "<span style='color:green;margin-left:1em;'>Guardado correctamente.</span>"; ?>
        <?php if (isset($_GET['restored'])) echo "<span style='color:blue;margin-left:1em;'>Backup restaurado.</span>"; ?>
    </form>
    <hr>
    <h3>Restaurar copia de seguridad</h3>
    <form method="post">
        <select name="restore">
            <?php foreach($backups as $b) {
                echo "<option value='".htmlspecialchars($b)."'>".basename($b)."</option>";
            } ?>
        </select>
        <button type="submit">Restaurar seleccionado</button>
    </form>
<?php endif; ?>
</div>
</body>
</html>