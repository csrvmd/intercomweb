<?php
// guardará/consultará el estado de presencia de los usuarios por IP
define('PRESENCE_DIR', __DIR__ . '/presence/');

// Asegurarse de que el directorio existe
if (!is_dir(PRESENCE_DIR)) {
    mkdir(PRESENCE_DIR, 0755, true);
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === "POST") {
    // Registrar presencia (ping)
    $ip = isset($_POST['ip']) ? $_POST['ip'] : $_SERVER['REMOTE_ADDR'];
    $time = time();
    file_put_contents(PRESENCE_DIR . $ip . '.txt', $time);
    echo "OK";
    exit;
}

if ($method === "GET") {
    // Consultar presencia
    $ip = isset($_GET['ip']) ? $_GET['ip'] : $_SERVER['REMOTE_ADDR'];
    $f = PRESENCE_DIR . $ip . '.txt';
    if (file_exists($f)) {
        $last = intval(file_get_contents($f));
        echo (time() - $last <= 60) ? "online" : "offline";
    } else {
        echo "offline";
    }
    exit;
}

// Opcional: limpiar archivos antiguos (no requerido, pero recomendado hacerlo periódicamente)