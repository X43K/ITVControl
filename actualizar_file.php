<?php
session_start();

if (!isset($_SESSION['tipo']) || !in_array($_SESSION['tipo'], ['Administrador','SuperAdministrador'])) {
    http_response_code(403);
    die('No autorizado.');
}

if (!isset($_GET['archivo'])) {
    http_response_code(400);
    die('Archivo no especificado.');
}

$archivo = $_GET['archivo'];
$owner = 'X43K';
$repo = 'ITVControl';
$branch = 'main';
$raw_url = "https://raw.githubusercontent.com/$owner/$repo/$branch/$archivo";

$options = [
    "http" => [
        "method" => "GET",
        "header" => "User-Agent: ITVControl-Updater\r\n"
    ]
];
$context = stream_context_create($options);

$contenido = @file_get_contents($raw_url, false, $context);
if ($contenido === false) {
    http_response_code(500);
    die('Error al descargar el archivo.');
}

// Crear directorio si no existe
$dir = dirname($archivo);
if (!is_dir($dir)) mkdir($dir, 0755, true);

// Sobrescribir archivo
file_put_contents($archivo, $contenido);
echo 'ok';
?>