<?php

header('Content-Type: application/json; charset=utf-8');

$CLAVES = [];

if (file_exists('sync_offline_claves.json')) {
    $CLAVES = json_decode(
        file_get_contents('sync_offline_claves.json'),
        true
    );
}

if (
    !isset($_GET['key']) ||
    !isset($CLAVES[$_GET['key']])
) {
    http_response_code(403);

    echo json_encode([
        'error' => 'Acceso denegado'
    ]);

    exit;
}

$FLOTAS = $CLAVES[$_GET['key']];

$vehiculos = [];
$citas = [];

if (file_exists('vehiculos.json')) {
    $vehiculos = json_decode(
        file_get_contents('vehiculos.json'),
        true
    );
}

if (file_exists('citas.json')) {
    $citas = json_decode(
        file_get_contents('citas.json'),
        true
    );
}

$vehiculos = array_values(array_filter($vehiculos, function($v) use ($FLOTAS) {
    return isset($v['flota']) && in_array($v['flota'], $FLOTAS);
}));

$citas = array_values(array_filter($citas, function($c) use ($FLOTAS) {
    return isset($c['flota']) && in_array($c['flota'], $FLOTAS);
}));

echo json_encode([
    'fecha_sync' => date('Y-m-d H:i:s'),
    'vehiculos' => $vehiculos,
    'citas' => $citas
], JSON_UNESCAPED_UNICODE);