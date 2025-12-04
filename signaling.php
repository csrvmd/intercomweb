<?php
// Este archivo sirve mensajes de señalización (ofertas/respuestas ICE) vía Ajax POST desde audio.js
require_once 'utils.php';
session_start();

$IP = $_SERVER['REMOTE_ADDR'];
$data = @file_get_contents('php://input');
if (!$data) exit;
$config = cargar_configuracion();
if (!usuario_ip_permitida($config, $IP)) exit;

// Simple ejemplo: guardar señalización en archivos temporales únicos por sala y usuario
$params = json_decode($data, true);
$sala = isset($params['sala']) ? $params['sala'] : 'SALA_1';
$user = isset($params['user']) ? $params['user'] : $IP;
$action = isset($params['action']) ? $params['action'] : '';

$signal_dir = __DIR__."/signals";
if (!is_dir($signal_dir)) mkdir($signal_dir, 0700, true);

if ($action === "send") {
    $target = $params['target'];
    $fname = "$signal_dir/{$sala}_{$user}_to_{$target}.json";
    file_put_contents($fname, json_encode($params['message']));
    echo "ok";
} elseif ($action === "receive") {
    $target = $params['target'];
    $fname = "$signal_dir/{$sala}_{$target}_to_{$user}.json";
    if (file_exists($fname)) {
        echo file_get_contents($fname);
        unlink($fname);
    } else {
        echo "null";
    }
}