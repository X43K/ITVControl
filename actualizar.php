<?php
session_name('ITVCONTROL_SESSID');
session_start();

// Solo administradores o superadministradores
if (!isset($_SESSION['tipo']) || !in_array($_SESSION['tipo'], ['Administrador','SuperAdministrador'])) {
    die('No autorizado.');
}

// Datos del repositorio
$owner = 'X43K';
$repo = 'ITVControl';
$branch = 'main';
$api_url = "https://api.github.com/repos/$owner/$repo/git/trees/$branch?recursive=1";

$options = [
    "http" => [
        "method" => "GET",
        "header" => "User-Agent: ITVControl-Updater\r\n"
    ]
];
$context = stream_context_create($options);

// Obtener lista de archivos
$contenido = file_get_contents($api_url, false, $context);
if ($contenido === false) die('No se pudo obtener la lista de archivos del repositorio.');

$data = json_decode($contenido, true);
if (!isset($data['tree'])) die('Respuesta inválida de GitHub.');

$archivos = [];
foreach ($data['tree'] as $item) {
    if ($item['type'] === 'blob') {
        $ext = strtolower(pathinfo($item['path'], PATHINFO_EXTENSION));
        if ($ext !== 'json' && $ext !== 'log') $archivos[] = $item['path'];
    }
}
$total = count($archivos);
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Actualizando ITVControl</title>
<link rel="icon" href="images/logo.webp">
<link rel="manifest" href="manifest.json">
<link rel="apple-touch-icon" sizes="180x180" href="images/logo.webp">
<link rel="stylesheet" href="style.css">

<style>
body { font-family: Arial, sans-serif; margin:20px; }
.progress-container { width:100%; background:#eee; border-radius:8px; overflow:hidden; margin-top:20px; }
.progress-bar { width:0%; height:25px; background:#4a90e2; text-align:center; color:white; line-height:25px; font-weight:bold; transition: width 0.3s; }
#log { margin-top:15px; max-height:400px; overflow-y:auto; border:1px solid #ccc; padding:10px; background:#f8f8f8; }
#log div { margin-bottom:3px; }
.success { color:green; }
.error { color:red; }
.summary { margin-top:15px; font-weight:bold; }
button { background:#4a90e2; color:white; border:none; padding:8px 16px; margin-right:10px; border-radius:6px; cursor:pointer; font-weight:bold; }
button:hover { background:#357ABD; }
</style>
</head>
<body>
<h2>Actualizando ITVControl...</h2>

<div class="progress-container">
    <div class="progress-bar" id="progress-bar">0%</div>
</div>

<div id="log"></div>
<div class="summary" id="summary"></div>

<div id="buttons" style="margin-top:15px; display:none;">
    <button id="retry-btn">Reintentar errores</button>
    <button onclick="window.location.href='index.php'">Volver al inicio</button>
</div>

<script>
let archivos = <?php echo json_encode($archivos); ?>;
const total = archivos.length;
let actualizados = 0;
let errores = 0;
let fallidos = [];

const log = document.getElementById('log');
const barra = document.getElementById('progress-bar');
const resumen = document.getElementById('summary');
const botonesDiv = document.getElementById('buttons');
const retryBtn = document.getElementById('retry-btn');

function actualizarArchivo(indice) {
    if (indice >= archivos.length) {
        barra.style.width = '100%';
        barra.textContent = 'Actualización completada';
        resumen.innerHTML = `Archivos actualizados: ${actualizados} / ${total} <br>Errores: ${errores}`;
        botonesDiv.style.display = 'block';
        retryBtn.style.display = errores > 0 ? 'inline-block' : 'none';
        return;
    }

    const archivo = archivos[indice];
    log.innerHTML += `<div>Actualizando: ${archivo}...</div>`;
    log.scrollTop = log.scrollHeight;

    fetch('actualizar_file.php?archivo=' + encodeURIComponent(archivo))
        .then(r => r.text())
        .then(res => {
            if(res.trim() === 'ok') {
                actualizados++;
                log.innerHTML += `<div class="success">✔ ${archivo} actualizado correctamente.</div>`;
            } else {
                errores++;
                fallidos.push(archivo);
                log.innerHTML += `<div class="error">✖ Error actualizando ${archivo}</div>`;
            }

            barra.style.width = Math.round((actualizados + errores)/total*100) + '%';
            barra.textContent = Math.round((actualizados + errores)/total*100) + '%';
            actualizarArchivo(indice + 1);
        })
        .catch(err => {
            errores++;
            fallidos.push(archivo);
            log.innerHTML += `<div class="error">✖ Error actualizando ${archivo}</div>`;
            barra.style.width = Math.round((actualizados + errores)/total*100) + '%';
            barra.textContent = Math.round((actualizados + errores)/total*100) + '%';
            actualizarArchivo(indice + 1);
        });
}

// Iniciar actualización
actualizarArchivo(0);

// Reintentar solo los errores
retryBtn.addEventListener('click', () => {
    if (fallidos.length === 0) return;
    archivos = [...fallidos];
    fallidos = [];
    actualizados = 0;
    errores = 0;
    log.innerHTML = '';
    barra.style.width = '0%';
    barra.textContent = '0%';
    botonesDiv.style.display = 'none';
    actualizarArchivo(0);
});
</script>
</body>
</html>