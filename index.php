<?php
/**
 * ============================================================
 *  ITVControl v8.0  -  aplicacion en un unico archivo
 * ============================================================
 *  Un solo punto de entrada. Las rutas antiguas (citas.php,
 *  usuarios.php, etc.) se reescriben a index.php?p=... desde
 *  el .htaccess, asi que los enlaces y formularios existentes
 *  siguen funcionando sin cambios.
 *
 *  Los archivos de datos (usuarios.json, citas.json,
 *  vehiculos.json, estaciones.json) NO se guardan en el
 *  repositorio: se crean solos con datos de muestra la
 *  primera vez que se ejecuta la aplicacion.
 * ============================================================
 */

// ------------------------- SESION -------------------------
if (session_status() === PHP_SESSION_NONE) {
    session_name('ITVCONTROL_SESSID');
    session_start();
}

// Todas las rutas relativas (json, logs, imagenes) se resuelven
// junto a index.php, sea cual sea el directorio de trabajo.
@chdir(__DIR__);

// ---------------------- UTILIDADES ------------------------
function itvc_write_json($file, $data)
{
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    return @file_put_contents($file, $json, LOCK_EX) !== false;
}

function itvc_ensure_data()
{
    // Usuarios de muestra (cambiar la contrasena tras la primera entrada)
    $usuarios = [
        [
            'usuario' => 'admin',
            'contraseña' => '$2y$10$QbBi8YvGkZ35pI8CjxaY1eSXUWbiKPW.7LyFs5Ee/CD2DWJYtHiFW',
            'tipo' => 'SuperAdministrador',
        ],
        [
            'usuario' => 'usuario',
            'contraseña' => '$2y$10$lJuS1W4RFge8vv8Hbtq84em/Oj2x0C6aecu/Nos0OHBySreWvjekq',
            'tipo' => 'Usuario',
            'flota' => 'TEST',
        ],
    ];

    // Vehiculos de muestra
    $vehiculos = [
        [
            'vehiculo' => 'VehiculoTest',
            'matricula' => '1234TST',
            'tipo' => 'Autobuses y microbuses',
            'estado' => 'ACTIVO',
            'caducidad_itv' => '2026-08-04',
            'flota' => 'TEST',
        ],
    ];

    // Estaciones de muestra
    $estaciones = [
        [
            'nombre' => 'Test',
            'flotas' => ['TEST'],
        ],
    ];

    $defaults = [
        'usuarios.json'   => $usuarios,
        'citas.json'      => [],
        'vehiculos.json'  => $vehiculos,
        'estaciones.json' => $estaciones,
    ];

    foreach ($defaults as $file => $data) {
        if (!file_exists($file)) {
            itvc_write_json($file, $data);
        }
    }

    // Claves de sincronizacion offline: objeto vacio
    if (!file_exists('sync_offline_claves.json')) {
        @file_put_contents('sync_offline_claves.json', '{}', LOCK_EX);
    }

    // Manifest PWA: se mantiene versionado en el repositorio, pero si
    // falta (por ejemplo al desplegar solo index.php) se regenera.
    if (!file_exists('manifest.json')) {
        itvc_write_json('manifest.json', [
            'name' => 'ITVControl',
            'icons' => [
                ['src' => 'images/logo-192.png', 'sizes' => '192x192', 'type' => 'image/png'],
                ['src' => 'images/logo-512.png', 'sizes' => '512x512', 'type' => 'image/png'],
            ],
        ]);
    }
}

itvc_ensure_data();

// ------------------------- RUTAS --------------------------
$ITVC_ROUTES = [
    'acceso-bloqueado'    => true,
    'acceso-restringido'  => true,
    'actualizar'          => true,
    'actualizar_file'     => true,
    'calendario_citas'    => true,
    'citas'               => true,
    'check_bloqueo'       => true,
    'desbloquear_usuario' => true,
    'editar_cita'         => true,
    'editar_usuario'      => true,
    'editar_vehiculo'     => true,
    'eliminar_cita'       => true,
    'eliminar_usuario'    => true,
    'eliminar_vehiculo'   => true,
    'estaciones'          => true,
    'imprimir'            => true,
    'imprimir_caducidades'=> true,
    'imprimir_citas'      => true,
    'index'               => true,
    'ips_bloqueadas'      => true,
    'login'               => true,
    'logout'              => true,
    'usuarios'            => true,
    'vehiculos'           => true,
];

$p = isset($_GET['p']) ? (string) $_GET['p'] : 'index';
if (!isset($ITVC_ROUTES[$p])) {
    $p = 'index';
}

switch ($p) {

// ===================== acceso-bloqueado =====================
case 'acceso-bloqueado':
http_response_code(403); // Mantener código 403

// Detectar IP real
$ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';

// Opcional: país usando ip-api.com
$pais = "Desconocido";
$codigoPais = "";
$geo = @json_decode(file_get_contents("http://ip-api.com/json/$ip"));
if ($geo && $geo->status === "success") {
    $pais = $geo->country;
    $codigoPais = strtolower($geo->countryCode);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Acceso Bloqueado</title>

<link rel="icon" href="images/logo.webp">
<link rel="manifest" href="manifest.json">
<link rel="apple-touch-icon" sizes="180x180" href="images/logo.webp">
<link rel="stylesheet" href="style.css">

<style>
body{
    font-family: Arial, sans-serif;
    background: linear-gradient(135deg,#2c3e50,#4ca1af);
    height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
    color:white;
    text-align:center;
}
.container{
    background: rgba(0,0,0,0.7);
    padding:40px;
    border-radius:12px;
    max-width:550px;
    width:90%;
}
h1{font-size:60px;color:#ff4c4c;}
.ip-box{
    margin-top:20px;
    padding:12px;
    background:rgba(255,0,0,0.2);
    border-radius:8px;
    font-size:16px;
}
.btn{
    display:inline-block;
    margin-top:25px;
    padding:10px 20px;
    background:#ff4c4c;
    color:white;
    text-decoration:none;
    border-radius:20px;
}
</style>
</head>
<body>

<div class="container">
<img src="images/logo.webp" width="40" class="logo" alt="Logo">
<h1>🚫 Acceso Bloqueado</h1>
<p class="ip-box">
Su dirección IP ha sido bloqueada por seguridad.<br>
IP detectada: <strong><?php echo htmlspecialchars($ip); ?></strong>
<?php if($codigoPais): ?>
<img src="https://flagcdn.com/24x18/<?php echo $codigoPais; ?>.png" alt="Bandera">
<br>País: <?php echo htmlspecialchars($pais); ?>
<?php endif; ?>
</p>

<p>No podrá acceder a esta sección hasta que se desbloquee por un administrador.</p>
</div>

</body>
</html>
<?php break;

// ===================== acceso-restringido =====================
case 'acceso-restringido':
http_response_code(403);

// CONFIGURACIÓN
$maxIntentos = 3;
$logFile = __DIR__ . "/ips_bloqueadas.log";
$bloqueadasFile = __DIR__ . "/ips_permanentemente_bloqueadas.log";

// Obtener IP real
function getRealIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    } else {
        return $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
    }
}

$ip = getRealIP();
$fecha = date("Y-m-d H:i:s");
$archivo = $_SERVER['REQUEST_URI'] ?? 'UNKNOWN';
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN';
$usuario = $_SESSION['usuario'] ?? 'ANONIMO'; // Usuario logueado

// Verificar si ya está bloqueada permanentemente
$ipsBloqueadas = file_exists($bloqueadasFile) ? file($bloqueadasFile, FILE_IGNORE_NEW_LINES) : [];
$bloqueado = in_array($ip, $ipsBloqueadas);

// Contar intentos anteriores
$intentos = 0;
if (file_exists($logFile)) {
    $lineas = file($logFile);
    foreach ($lineas as $linea) {
        if (strpos($linea, "IP: $ip ") !== false) {
            $intentos++;
        }
    }
}

// Si supera límite y no está aún en bloqueadas → añadir
if ($intentos >= $maxIntentos && !$bloqueado) {
    file_put_contents($bloqueadasFile, $ip . PHP_EOL, FILE_APPEND | LOCK_EX);
    $bloqueado = true;
}

// Registrar intento actual
$registro = "[$fecha] IP: $ip | Usuario: $usuario | Archivo: $archivo | UA: $userAgent" . PHP_EOL;
file_put_contents($logFile, $registro, FILE_APPEND | LOCK_EX);

// Obtener país
$pais = "Desconocido";
$codigoPais = "";
$geo = @json_decode(file_get_contents("http://ip-api.com/json/$ip"));
if ($geo && $geo->status === "success") {
    $pais = $geo->country;
    $codigoPais = strtolower($geo->countryCode);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>403 - Acceso Restringido</title>

<link rel="icon" href="images/logo.webp">
<link rel="manifest" href="manifest.json">
<link rel="apple-touch-icon" sizes="180x180" href="images/logo.webp">
<link rel="stylesheet" href="style.css">

<meta http-equiv="refresh" content="7;url=index.php?p=logout">
<style>
body{font-family: Arial, sans-serif;background:linear-gradient(135deg,#0f2027,#203a43,#2c5364);height:100vh;display:flex;justify-content:center;align-items:center;color:white;}
.container{background:rgba(0,0,0,0.8);padding:40px;border-radius:12px;text-align:center;width:90%;max-width:600px;}
h1{color:#ff4c4c;font-size:60px;}
.ip-box{margin-top:20px;padding:12px;background:rgba(255,0,0,0.2);border:1px solid #ff4c4c;border-radius:8px;}
.bloqueado{margin-top:20px;padding:12px;background:rgba(255,0,0,0.4);border:1px solid red;border-radius:8px;font-weight:bold;}
.btn{display:inline-block;margin-top:25px;padding:10px 20px;background:#ff4c4c;color:white;text-decoration:none;border-radius:20px;}
</style>
</head>
<body>
<div class="container">
<img src="images/logo.webp" width="40" class="logo" alt="Logo">
<h1>403</h1>
<h2>Acceso Restringido</h2>
<div class="ip-box">
⚠ Su intento ha sido registrado.<br>
IP detectada: <strong><?php echo htmlspecialchars($ip); ?></strong>
<?php if($codigoPais): ?>
<img src="https://flagcdn.com/24x18/<?php echo $codigoPais; ?>.png">
<?php endif; ?>
<br>
País: <?php echo htmlspecialchars($pais); ?>
<br>
Usuario: <strong><?php echo htmlspecialchars($usuario); ?></strong>
</div>

<?php if($bloqueado): ?>
<div class="bloqueado">
🚫 SU DIRECCIÓN IP HA SIDO BLOQUEADA PERMANENTEMENTE<br>
Intentos detectados: <?php echo $intentos; ?>
</div>
<?php else: ?>
<a href="index.php?p=logout" class="btn">Volver al inicio</a>
<?php endif; ?>

</div>
</body>
</html>
<?php break;

// ===================== actualizar =====================
case 'actualizar':
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

// Archivos que no forman parte de la aplicacion en ejecucion
$no_actualizables = ['json', 'log', 'md'];
$archivos = [];
foreach ($data['tree'] as $item) {
    if ($item['type'] === 'blob') {
        $path = $item['path'];
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (in_array($ext, $no_actualizables, true) && $path !== 'manifest.json') continue;
        if ($path === 'LICENSE' || $path === '.gitignore') continue;
        $archivos[] = $path;
    }
}

// index.php se actualiza el ultimo: si la descarga se corta, la app sigue viva
usort($archivos, function ($a, $b) {
    if ($a === 'index.php') return 1;
    if ($b === 'index.php') return -1;
    return strcmp($a, $b);
});

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

    fetch('index.php?p=actualizar_file&archivo=' + encodeURIComponent(archivo))
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
<?php break;

// ===================== actualizar_file =====================
case 'actualizar_file':
if (!isset($_SESSION['tipo']) || !in_array($_SESSION['tipo'], ['Administrador','SuperAdministrador'])) {
    http_response_code(403);
    die('No autorizado.');
}

if (!isset($_GET['archivo'])) {
    http_response_code(400);
    die('Archivo no especificado.');
}

$archivo = (string) $_GET['archivo'];

// Solo rutas relativas limpias dentro de la aplicacion
if ($archivo === '' || strpos($archivo, '..') !== false || $archivo[0] === '/' || strpos($archivo, '\\') !== false) {
    http_response_code(400);
    die('Archivo no valido.');
}
if (!preg_match('#^[A-Za-z0-9_\-./]+$#', $archivo)) {
    http_response_code(400);
    die('Archivo no valido.');
}

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

// No dejar que un index.php corrupto deje la app inservible
if ($archivo === 'index.php' && strpos(ltrim($contenido), '<?php') !== 0) {
    http_response_code(500);
    die('El archivo descargado no es valido.');
}

// Crear directorio si no existe
$dir = dirname($archivo);
if ($dir !== '.' && !is_dir($dir)) mkdir($dir, 0755, true);

// Escritura atomica: primero a un temporal y luego rename
$tmp = $archivo . '.tmp-' . getmypid();
if (@file_put_contents($tmp, $contenido, LOCK_EX) === false || !@rename($tmp, $archivo)) {
    @unlink($tmp);
    http_response_code(500);
    die('No se pudo escribir el archivo.');
}

echo 'ok';
?>
<?php break;

// ===================== calendario_citas =====================
case 'calendario_citas':
if (!isset($_SESSION['usuario'])) {
    header('Location: index.php?p=login');
    exit();
}

$is_colab = isset($_SESSION['tipo']) && in_array($_SESSION['tipo'], ['Colaborador', 'Administrador', 'SuperAdministrador']);
$is_admin = isset($_SESSION['tipo']) && in_array($_SESSION['tipo'], ['Administrador', 'SuperAdministrador']);
$is_superadmin = isset($_SESSION['tipo']) && $_SESSION['tipo'] === 'SuperAdministrador';

$flota_usuario = $_SESSION['flota'] ?? null;

// =====================
// CARGAR CITAS Y VEHÍCULOS
// =====================
$citas_file = 'citas.json';
if (!file_exists($citas_file)) file_put_contents($citas_file, json_encode([]));
$citas = json_decode(file_get_contents($citas_file), true);

$vehiculos_file = 'vehiculos.json';
if (!file_exists($vehiculos_file)) die("El archivo de vehículos no existe.");
$vehiculos = json_decode(file_get_contents($vehiculos_file), true);
usort($vehiculos, fn($a,$b) => strnatcasecmp($a['vehiculo'], $b['vehiculo']));

// =====================
// CARGAR ESTACIONES
// =====================
$estaciones_file = 'estaciones.json';
if (!file_exists($estaciones_file)) {
    file_put_contents($estaciones_file, json_encode(['Tambre','Sionlla','Cacheiras'], JSON_PRETTY_PRINT));
}
$estaciones = json_decode(file_get_contents($estaciones_file), true);

// =====================
// CARGAR FLOTAS EXISTENTES (para SuperAdmin)
// =====================
$usuarios_file = 'usuarios.json';
$flotas_existentes = [];
if ($is_superadmin && file_exists($usuarios_file)) {
    $usuarios = json_decode(file_get_contents($usuarios_file), true);
    foreach ($usuarios as $u) {
        if (!empty($u['flota']) && !in_array($u['flota'],$flotas_existentes)) {
            $flotas_existentes[] = $u['flota'];
        }
    }
}

// =====================
// FUNCIONES
// =====================
function formatear_fecha($fecha) {
    $f = DateTime::createFromFormat('Y-m-d', $fecha);
    return $f ? $f->format('d/m/Y') : $fecha;
}
function mostrarVehiculo($matricula, $vehiculos) {
    if ($matricula === '') return 'Sin asignar';
    foreach ($vehiculos as $v) {
        if ($v['matricula'] === $matricula) return $v['vehiculo'].' - '.$v['matricula'];
    }
    return $matricula;
}

// =====================
// VERSION Y AUTOR
// =====================
$version = 'v.1.0';
$autor = 'Desconocido';
if (file_exists('version.xk')) {
    $lineas = file('version.xk', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (isset($lineas[0])) $version = trim($lineas[0]);
    if (isset($lineas[1])) $autor = trim($lineas[1]);
}

// =====================
// FILTRAR CITAS FUTURAS POR FLOTA
// =====================
$ahora = new DateTime();
$citas_futuras = array_filter($citas, function($cita) use($ahora, $is_superadmin, $flota_usuario) {
    $dt = DateTime::createFromFormat('Y-m-d H:i', ($cita['fecha_cita'] ?? '') . ' ' . ($cita['hora_cita'] ?? '00:00'));
    if (!$dt || $dt < $ahora) return false;
    if (!$is_superadmin) {
        return strtoupper($cita['flota'] ?? '') === strtoupper($flota_usuario ?? '');
    }
    return true; // SuperAdmin ve todas
});

usort($citas_futuras, fn($a,$b) => strtotime($a['fecha_cita'].' '.$a['hora_cita']) <=> strtotime($b['fecha_cita'].' '.$b['hora_cita']));

// =====================
// AGRUPAR CITAS POR MES Y DÍA
// =====================
$citas_por_mes = [];
foreach ($citas_futuras as $c) {
    $dt = DateTime::createFromFormat('Y-m-d', $c['fecha_cita']);
    if ($dt) {
        $mes = $dt->format('Y-m');
        $dia = (int)$dt->format('j');
        if (!isset($citas_por_mes[$mes])) $citas_por_mes[$mes] = [];
        if (!isset($citas_por_mes[$mes][$dia])) $citas_por_mes[$mes][$dia] = [];
        $citas_por_mes[$mes][$dia][] = $c;
    }
}
ksort($citas_por_mes);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>ITVControl</title>
<link rel="icon" href="images/logo.webp">
<link rel="manifest" href="manifest.json">
<link rel="apple-touch-icon" sizes="180x180" href="images/logo.webp">
<link rel="stylesheet" href="style.css">

<meta http-equiv="refresh" content="60">

<style>
/* ===== BASE ===== */
body { margin:15px; font-family:Arial,sans-serif; }

/* ===== USUARIO ===== */
.user-info{
    position:fixed; top:10px; right:15px; text-align:right; font-size:14px;
    background:rgba(255,255,255,0.6); padding:5px 10px; border-radius:8px;
}
.user-info strong{display:block;}
.user-info small{color:#4a90e2;font-weight:bold;}

/* ===== MODO OSCURO ===== */
@media (prefers-color-scheme: dark) {
    body{background:#000;color:#ddd;}
    h1,h2,h3,h4,p,strong{color:#ddd;}
    th{background:#1c75bc;color:#fff;}
    td{color:#ddd;border-color:#555;}
    tr:nth-child(even) td { background:#111827; }
    tr:hover td { background:#1e293b; }

    input, select { background:#111;color:#fff;border:1px solid #555; }
    input[type="submit"] { background: linear-gradient(135deg,#1c75bc,#0066cc); border:1px solid #005bb5; color:#fff; }
    input[type="submit"]:hover { background: linear-gradient(135deg,#005bb5,#1c75bc); }

    .menu a img:not([alt="Logo"]){filter: invert(1) hue-rotate(180deg);}
    h1 img{filter:none;}
    .user-info{background:rgba(0,0,0,0.5);}
    .user-info small{color:#3399ff;}
}

/* ===== CALENDARIO ===== */
.calendario-mes {
    margin-bottom: 50px;
}
.calendario-mes h2 {
    text-align:center;
    margin:20px 0 10px 0;
    color:#000; /* modo claro */
}
table.calendario {
    width:100%;
    border-collapse:collapse;
    margin-bottom:20px;
}
.calendario th, .calendario td {
    border:1px solid #ccc;
    width:14.28%;
    vertical-align:top;
    text-align:left;
    padding:6px;
}
.calendario th {
    background:#333;
    color:#fff;
    text-align:center;
    font-weight:700;
    height:24px; /* altura ajustada a texto */
}
.calendario td.vacio {
    background:#f0f0f0;
}
.calendario .dia {
    font-weight:bold;
    font-size:16px;
    margin-bottom:6px;
}
.cita {
    background:#4a90e2;
    color:#fff;
    font-size:16px;
    border-radius:4px;
    padding:4px 6px;
    margin-bottom:6px;
    display:block;
    word-wrap:break-word;
}


/* ===== COLOR SABADOS/DOMINGOS ===== */
.calendario td:nth-child(6) {
    background-color: #fff59d; /* sábado amarillo */
}

.calendario td:nth-child(7) {
    background-color: #ff8a80; /* domingo rojo */
}

/* MODO OSCURO */
@media (prefers-color-scheme: dark) {
    .calendario td:nth-child(6) {
        background-color: #665c00; /* amarillo oscuro */
    }
    .calendario td:nth-child(7) {
        background-color: #7f1d1d; /* rojo oscuro */
    }
}

/* ===== CABECERA FINES DE SEMANA (AMARILLO/ROJO SUAVES) ===== */
.calendario th:nth-child(6) {
    background-color: #ffd54f; /* sábado amarillo suave */
    color:#000;
}

.calendario th:nth-child(7) {
    background-color: #ef5350; /* domingo rojo suave */
    color:#fff;
}

/* MODO OSCURO */
@media (prefers-color-scheme: dark) {
    .calendario th:nth-child(6) {
        background-color: #bfa134; /* amarillo más apagado */
        color:#000;
    }
    .calendario th:nth-child(7) {
        background-color: #b71c1c; /* rojo más profundo */
        color:#fff;
    }
}

/* ===== MODO OSCURO (ajustes pedidos) ===== */
@media (prefers-color-scheme: dark) {
    .calendario-mes h2 { color:#fff; } /* <-- ahora blanco en modo oscuro */
    .calendario th { background:#1c75bc; color:#fff; height:24px; } /* altura más baja */
    .calendario td { border-color:#555; color:#ddd; }
    .calendario td.vacio { background:#111827; }
    .cita { background:#1c75bc; color:#fff; }
}
/* ===== TIPOS DE CITA (SOLUCIÓN DEFINITIVA) ===== */
.cita.cita-segunda {
    background:#4a90e2;
}

/* ===== CITAS SIN ASIGNAR (AMARILLO CORREGIDO) ===== */
.cita.cita-primera-libre {
    background: #FFD54F; /* 1ª → más oscuro */
    color:#fff;
}

.cita.cita-segunda-libre {
    background: #FFF9C4; /* 2ª → más claro */
    color:#fff;
}

/* MODO OSCURO */
@media (prefers-color-scheme: dark) {
    .cita.cita-primera-libre {
        background: #bfa134; /* más intenso */
        color:#fff;
    }

    .cita.cita-segunda-libre {
        background: #665c00; /* más apagado */
        color:#fff;
    }
}

.cita.cita-primera {
    background:#00bcd4;
}

/* MODO OSCURO (pisar el .cita general) */
@media (prefers-color-scheme: dark) {
    .cita.cita-segunda {
        background:#4f6980;
    }
    .cita.cita-primera {
        background:#1c75bc;
    }
}
</style>
</head>
<body>

<div class="user-info">
    <strong><?=htmlspecialchars($_SESSION['usuario'])?> | <?=htmlspecialchars($_SESSION['tipo'])?></strong>
    <small><?= $is_superadmin ? "Todas las flotas" : ($flota_usuario ? strtoupper($flota_usuario) : "Sin flota asignada") ?></small>
    <div id="fecha-hora"></div>
</div>

<h1><img src="images/logo.webp" width="30" style="vertical-align: middle;"> Gestionar citas de ITV</h1>
<hr style="border:1px solid #4a90e2; margin:10px 0 20px 0;">

<div class="menu">
  <a title="index" href="index.php"><img src="images/index.webp" alt="index" width="80"></a>
  <a title="citas" href="index.php?p=citas"><img src="images/citas.webp" alt="citas" width="80"></a>
  <a title="vehiculos" href="index.php?p=vehiculos"><img src="images/vehiculos.webp" alt="vehiculos" width="80"></a>
 <?php if(in_array($_SESSION['tipo'], ['Administrador','SuperAdministrador'])): ?>
  <a title="estaciones" href="index.php?p=estaciones"><img src="images/estaciones.webp" alt="estaciones" width="80"></a>
  <a title="seguridad" href="index.php?p=ips_bloqueadas"><img src="images/secury.webp" alt="seguridad" width="80"></a>
  <a title="usuarios" href="index.php?p=usuarios"><img src="images/usuarios.webp" alt="usuarios" width="80"></a>
 <?php endif; ?>
  <a title="imprimir" href="index.php?p=imprimir"><img src="images/imprimir.webp" alt="imprimir" width="80"></a>
  <a title="logout" href="index.php?p=logout"><img src="images/logout.webp" alt="logout" width="80"></a>
</div>

<br><br><br>

<script>
function actualizarFechaHora(){
    const d=new Date();
    document.getElementById('fecha-hora').innerText =
        d.toLocaleDateString('es-ES')+' '+d.toLocaleTimeString('es-ES');
}
actualizarFechaHora();
setInterval(actualizarFechaHora,1000);
</script>

<h2 style="display:inline-flex; align-items:center; gap:8px; margin:0;">
  Calendario de Citas Futuras
  <button class="boton-vista" onclick="window.location.href='index.php?p=citas'">Cambiar a modo lista</button>
</h2>

<style>
.boton-vista {
  background:#4a90e2;
  color:#fff;
  border:none;
  padding:4px 10px;
  border-radius:6px;
  cursor:pointer;
  font-size:13px;
  transition:background 0.2s ease-in-out;
}
.boton-vista:hover {
  background:#1c75bc;
}
@media (prefers-color-scheme: dark) {
  .boton-vista {
    background: linear-gradient(135deg,#1c75bc,#0066cc);
    color:#fff;
    border:1px solid #005bb5;
  }
  .boton-vista:hover {
    background: linear-gradient(135deg,#005bb5,#1c75bc);
  }
}
</style>

<?php if (empty($citas_por_mes)): ?>
    <p>No hay citas futuras.</p>
<?php else: ?>

<?php
$meses_nombres = [
    '01'=>'ENERO','02'=>'FEBRERO','03'=>'MARZO','04'=>'ABRIL','05'=>'MAYO','06'=>'JUNIO',
    '07'=>'JULIO','08'=>'AGOSTO','09'=>'SEPTIEMBRE','10'=>'OCTUBRE','11'=>'NOVIEMBRE','12'=>'DICIEMBRE'
];

foreach ($citas_por_mes as $mes => $dias):
    $anio = substr($mes, 0, 4);
    $num_mes = substr($mes, 5, 2);
    ksort($dias);
    $primer_dia = new DateTime("$anio-$num_mes-01");
    $inicio_semana = (int)$primer_dia->format('N');
    $dias_mes = (int)$primer_dia->format('t');
?>
<div class="calendario-mes">
    <h2><?= $meses_nombres[$num_mes] . " " . $anio ?></h2>
    <table class="calendario">
        <thead>
            <tr>
                <th>Lunes</th><th>Martes</th><th>Miércoles</th><th>Jueves</th>
                <th>Viernes</th><th>Sábado</th><th>Domingo</th>
            </tr>
        </thead>
        <tbody>
            <tr>
<?php
$col = 1;

$hoy = new DateTime();

// Empezar desde HOY si es el mes actual
if ($anio == $hoy->format('Y') && $num_mes == $hoy->format('m')) {
    $primer_dia_visible = (int)$hoy->format('j');
} else {
    $primer_dia_visible = 1;
}

// Crear fecha de inicio real
$primer_dia = new DateTime("$anio-$num_mes-$primer_dia_visible");
$inicio_semana = (int)$primer_dia->format('N');

// Celdas vacías antes del inicio
for ($v = 1; $v < $inicio_semana; $v++, $col++) {
    echo "<td class='vacio'></td>";
}

// Bucle de días (EMPIEZA en el día válido)
for ($d = $primer_dia_visible; $d <= $dias_mes; $d++, $col++) {
    $citas_dia = $dias[$d] ?? [];
    echo "<td>";
    echo "<div class='dia'>$d</div>";
    foreach ($citas_dia as $c) {
        $veh = htmlspecialchars(mostrarVehiculo($c['vehiculo'] ?? '', $vehiculos));
        $hora = htmlspecialchars($c['hora_cita'] ?? '');
        $tipo = htmlspecialchars($c['tipo_cita'] ?? '');
        $est = htmlspecialchars($c['estacion_cita'] ?? '');
        $tipo_formateado = ($tipo === 'Segunda') ? '2ª' : '1ª';
        $sin_asignar = empty($c['vehiculo']);

if ($sin_asignar) {
    $clase_tipo = ($tipo === 'Segunda') ? 'cita-segunda-libre' : 'cita-primera-libre';
} else {
    $clase_tipo = ($tipo === 'Segunda') ? 'cita-segunda' : 'cita-primera';
}

        echo "<div class='cita {$clase_tipo}'>{$hora} {$est} {$tipo_formateado}<br>{$veh}</div>";
    }
    echo "</td>";
    if ($col % 7 == 0 && $d < $dias_mes) echo "</tr><tr>";
}

// Relleno final
while ($col % 7 != 1) { echo "<td class='vacio'></td>"; $col++; }
?>
            </tr>
        </tbody>
    </table>
</div>
<?php endforeach; ?>

<?php endif; ?>

<h4 class="small version-title" style="margin-top:12px; text-align:left;"><?= htmlspecialchars($version) ?></h4>
<p class="small version-author" style="text-align:left;"><?= htmlspecialchars($autor) ?></p>

</body>
</html>
<?php break;

// ===================== citas =====================
case 'citas':
if (!isset($_SESSION['usuario'])) {
    header('Location: index.php?p=login');
    exit();
}

$is_colab = isset($_SESSION['tipo']) && in_array($_SESSION['tipo'], ['Colaborador', 'Administrador', 'SuperAdministrador']);
$is_admin = isset($_SESSION['tipo']) && in_array($_SESSION['tipo'], ['Administrador', 'SuperAdministrador']);
$is_superadmin = isset($_SESSION['tipo']) && $_SESSION['tipo'] === 'SuperAdministrador';

$flota_usuario = $_SESSION['flota'] ?? null;

// =====================
// CARGAR CITAS Y VEHÍCULOS
// =====================
$citas_file = 'citas.json';
if (!file_exists($citas_file)) file_put_contents($citas_file, json_encode([]));
$citas = json_decode(file_get_contents($citas_file), true);

$vehiculos_file = 'vehiculos.json';
if (!file_exists($vehiculos_file)) die("El archivo de vehículos no existe.");
$vehiculos = json_decode(file_get_contents($vehiculos_file), true);
usort($vehiculos, fn($a,$b) => strnatcasecmp($a['vehiculo'], $b['vehiculo']));

// =====================
// CARGAR ESTACIONES
// =====================
$estaciones_file = 'estaciones.json';
if (!file_exists($estaciones_file)) {
    file_put_contents($estaciones_file, json_encode(['Tambre','Sionlla','Cacheiras'], JSON_PRETTY_PRINT));
}
$estaciones = json_decode(file_get_contents($estaciones_file), true);

// =====================
// CARGAR FLOTAS EXISTENTES (para SuperAdmin)
// =====================
$usuarios_file = 'usuarios.json';
$flotas_existentes = [];
if ($is_superadmin && file_exists($usuarios_file)) {
    $usuarios = json_decode(file_get_contents($usuarios_file), true);
    foreach ($usuarios as $u) {
        if (!empty($u['flota']) && !in_array($u['flota'],$flotas_existentes)) {
            $flotas_existentes[] = $u['flota'];
        }
    }
}

// =====================
// VEHÍCULOS DISPONIBLES PARA EL FORMULARIO
// =====================
if ($is_superadmin) {
    $vehiculos_disponibles = $vehiculos;
} else {
    $flota_usuario_upper = strtoupper($flota_usuario ?? '');
    $vehiculos_disponibles = array_filter($vehiculos, function($v) use ($flota_usuario_upper) {
        return isset($v['flota']) && strtoupper($v['flota']) === $flota_usuario_upper;
    });
}

// =====================
// PROCESAR FORMULARIO
// =====================
if ($is_colab && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fecha_cita'])) {

    $fecha_cita = $_POST['fecha_cita'] ?? '';
    $hora_cita = $_POST['hora_cita'] ?? '';
    $estacion_cita = $_POST['estacion_cita'] ?? '';
    $tipo_cita = $_POST['tipo_cita'] ?? 'Primera';
    $vehiculo = $_POST['vehiculo'] ?? '';

    if ($is_superadmin) {
        $flota_cita = $_POST['flota'] ?? '';
        if (empty($flota_cita)) $error = "Debes seleccionar una flota para la cita.";
    } else {
        $flota_cita = $flota_usuario ?? '';
    }

    if (empty($error) && $fecha_cita && $hora_cita && $estacion_cita) {

        if ($vehiculo !== '') {
            $vehiculo_valido = false;
            foreach ($vehiculos as $v) {
                if ($v['matricula'] === $vehiculo) {
                    $vehiculo_valido = true;
                    break;
                }
            }
            if (!$vehiculo_valido) $error = "Vehículo no permitido o no encontrado.";
        }

        if (empty($error)) {

            $anio = substr(date('Y'), -3);
            $prefijo = 'AA';
            $ultimo_num = 0;

            foreach ($citas as $c) {
                if (isset($c['id_cita']) && preg_match('/^'.$anio.$prefijo.'(\d{3})$/', $c['id_cita'], $m)) {
                    $num = intval($m[1]);
                    if ($num > $ultimo_num) $ultimo_num = $num;
                }
            }

            $id_cita = $anio.$prefijo.str_pad($ultimo_num + 1,3,'0',STR_PAD_LEFT);

            $nueva_cita = [
                'fecha_cita' => $fecha_cita,
                'hora_cita' => $hora_cita,
                'estacion_cita' => $estacion_cita,
                'tipo_cita' => $tipo_cita,
                'vehiculo' => $vehiculo,
                'flota' => $flota_cita,
                'id_cita' => $id_cita
            ];

            $citas[] = $nueva_cita;
            file_put_contents($citas_file, json_encode($citas, JSON_PRETTY_PRINT));

            header('Location: index.php?p=citas');
            exit();
        }

    } else {
        $error = "Todos los campos son obligatorios.";
    }
}

// =====================
// FUNCIONES
// =====================
function formatear_fecha($fecha) {
    $f = DateTime::createFromFormat('Y-m-d', $fecha);
    return $f ? $f->format('d/m/Y') : $fecha;
}

function mostrarVehiculo($matricula, $vehiculos) {
    if ($matricula === '') return 'Sin asignar';
    foreach ($vehiculos as $v) {
        if ($v['matricula'] === $matricula) return $v['vehiculo'].' - '.$v['matricula'];
    }
    return $matricula;
}

// =====================
// FILTRAR CITAS FUTURAS
// =====================
$ahora = new DateTime();

$citas = array_filter($citas, function($cita) use($ahora, $is_superadmin, $flota_usuario) {

    $dt = DateTime::createFromFormat('Y-m-d H:i', $cita['fecha_cita'].' '.$cita['hora_cita']);

    if (!$dt || $dt < $ahora) return false;

    if (!$is_superadmin) {
        return strtoupper($cita['flota'] ?? '') === strtoupper($flota_usuario ?? '');
    }

    return true;
});

usort($citas, fn($a,$b) => strtotime($a['fecha_cita'].' '.$a['hora_cita']) <=> strtotime($b['fecha_cita'].' '.$b['hora_cita']));

// =====================
// VERSION Y AUTOR
$version = 'v.1.0'; $autor = 'Desconocido';
if(file_exists('version.xk')){
    $lineas = file('version.xk', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if(isset($lineas[0])) $version=$lineas[0];
    if(isset($lineas[1])) $autor=$lineas[1];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>ITVControl</title>
<link rel="icon" href="images/logo.webp">
<link rel="manifest" href="manifest.json">
<link rel="apple-touch-icon" sizes="180x180" href="images/logo.webp">
<link rel="stylesheet" href="style.css">
<style>
/* ===== BASE ===== */
body { margin:15px; font-family:Arial,sans-serif; }

/* ===== USUARIO ===== */
.user-info{
    position:fixed; top:10px; right:15px; text-align:right; font-size:14px;
    background:rgba(255,255,255,0.6); padding:5px 10px; border-radius:8px;
}
.user-info strong{display:block;}
.user-info small{color:#4a90e2;font-weight:bold;}

/* ===== TABLA ===== */
table { border-collapse: collapse; width: 100%; table-layout:auto; }
th, td { border:1px solid #ccc; padding:8px; vertical-align:top; }
th { background:#eee; }

/* Columnas iguales a index.php */
th:nth-child(1), td:nth-child(1){white-space:normal;word-break:keep-all;width:1%;max-width:100%;}
th:nth-child(2), td:nth-child(2){white-space:nowrap;}
th:nth-child(3), td:nth-child(3){white-space:normal;word-break:keep-all;}
th:nth-child(4), td:nth-child(4){white-space:normal;word-break:keep-all;width:auto;min-width:80px;}
th:nth-child(5), td:nth-child(5){white-space:nowrap;}
th:nth-child(6), td:nth-child(6){white-space:normal;word-break:keep-all;width:auto;min-width:80px;}
th:nth-child(7), td:nth-child(7){white-space:nowrap;}

tr:nth-child(even) td { background:#f8f8f8; }
tr:hover td { background:#dce6f1; }

/* FORMULARIO */
input, select { padding:5px; margin:2px 0; border-radius:4px; border:1px solid #ccc; background:#fff; color:#000; }
input[type="submit"] { cursor:pointer; background:#4a90e2; color:#fff; border:none; padding:6px 12px; border-radius:4px; }
input[type="submit"]:hover { background:#1c75bc; }

/* DIAS */
.dia-rojo { color:red; font-weight:bold; }
.dia-normal { color:inherit; font-weight:bold; }

/* MODO OSCURO */
@media (prefers-color-scheme: dark) {
    body{background:#000;color:#ddd;}
    h1,h2,h3,h4,p,strong{color:#ddd;}
    th{background:#1c75bc;color:#fff;}
    td{color:#ddd;border-color:#555;}
    tr:nth-child(even) td { background:#111827; }
    tr:hover td { background:#1e293b; }

    input, select { background:#111;color:#fff;border:1px solid #555; }
    input[type="submit"] { background: linear-gradient(135deg,#1c75bc,#0066cc); border:1px solid #005bb5; color:#fff; }
    input[type="submit"]:hover { background: linear-gradient(135deg,#005bb5,#1c75bc); }

    .menu a img:not([alt="Logo"]){filter: invert(1) hue-rotate(180deg);}
    h1 img{filter:none;}
    .user-info{background:rgba(0,0,0,0.5);}
    .user-info small{color:#3399ff;}
}
.boton-editar{
    background:#4CAF50;
    color:white;
    padding:3px 7px;
    border-radius:4px;
    text-decoration:none;
}
.boton-eliminar{
    background:#cc0000;
    color:white;
    padding:3px 7px;
    border-radius:4px;
    text-decoration:none;
}
.boton-editar:hover { background:#45a049; }
.boton-eliminar:hover { background:#b30000; }
</style>
</head>
<body>

<div class="user-info">
    <strong><?=htmlspecialchars($_SESSION['usuario'])?> | <?=htmlspecialchars($_SESSION['tipo'])?></strong>
    <small><?= $is_superadmin ? "Todas las flotas" : ($flota_usuario ? strtoupper($flota_usuario) : "Sin flota asignada") ?></small>
    <div id="fecha-hora"></div>
</div>

<h1><img src="images/logo.webp" width="30" style="vertical-align: middle;"> Gestionar citas de ITV</h1>
<hr style="border:1px solid #4a90e2; margin:10px 0 20px 0;">

    <div class="menu">
      <a title="index" href="index.php"><img src="images/index.webp" alt="index" width="80"></a>
      <a title="citas" href="index.php?p=citas"><img src="images/citas.webp" alt="citas" width="80"></a>
      <a title="vehiculos" href="index.php?p=vehiculos"><img src="images/vehiculos.webp" alt="vehiculos" width="80"></a>
     <?php if(in_array($_SESSION['tipo'], ['Administrador','SuperAdministrador'])): ?>
      <a title="estaciones" href="index.php?p=estaciones"><img src="images/estaciones.webp" alt="estaciones" width="80"></a>
      <a title="seguridad" href="index.php?p=ips_bloqueadas"><img src="images/secury.webp" alt="seguridad" width="80"></a>
      <a title="usuarios" href="index.php?p=usuarios"><img src="images/usuarios.webp" alt="usuarios" width="80"></a>
     <?php endif; ?>
      <a title="imprimir" href="index.php?p=imprimir"><img src="images/imprimir.webp" alt="imprimir" width="80"></a>
      <a title="logout" href="index.php?p=logout"><img src="images/logout.webp" alt="logout" width="80"></a>
    </div>
  
    <br><br><br>

<script>
function actualizarFechaHora(){
    const d=new Date();
    document.getElementById('fecha-hora').innerText =
        d.toLocaleDateString('es-ES')+' '+d.toLocaleTimeString('es-ES');
}
actualizarFechaHora();
setInterval(actualizarFechaHora,1000);
</script>


<?php if(isset($error)): ?>
<p class="dia-rojo"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<?php if($is_colab): ?>
<h2>Añadir Cita</h2>

<form method="POST">
    <label>Fecha:</label><input type="date" name="fecha_cita" required><br>
    <label>Hora:</label><input type="time" name="hora_cita" required><br>
    <label>Estación:</label><select name="estacion_cita" required>
      <?php foreach($estaciones as $e): ?>
      <?php
      // Solo mostrar estaciones que incluyan la flota del usuario, salvo SuperAdmin
      if(!$is_superadmin && !in_array($flota_usuario, $e['flotas'] ?? [])) continue;
      ?>
      <option value="<?=htmlspecialchars($e['nombre'])?>"><?=htmlspecialchars($e['nombre'])?></option>
      <?php endforeach; ?>
     </select><br>
    <label>Tipo:</label><select name="tipo_cita">
        <option value="Primera">Primera</option>
        <option value="Segunda">Segunda</option>
    </select><br>
    <label>Vehículo:</label>
    <select name="vehiculo">
        <option value="">Sin asignar</option>
        <?php foreach($vehiculos_disponibles as $v): ?>
        <option value="<?= htmlspecialchars($v['matricula']) ?>"><?= htmlspecialchars($v['vehiculo'].' - '.$v['matricula']) ?></option>
        <?php endforeach; ?>
    </select><br>

    <?php if($is_superadmin): ?>
        <label>Flota:</label><select name="flota" required>
            <?php foreach($flotas_existentes as $f): ?>
                <option value="<?= htmlspecialchars($f) ?>"><?= htmlspecialchars($f) ?></option>
            <?php endforeach; ?>
        </select><br>
    <?php endif; ?>
    <br>

    <input type="submit" value="Añadir Cita" onclick="return comprobarCaducidadITV()">
</form>
<?php endif; ?>
  
  <h2 style="display:inline-flex; align-items:center; gap:8px; margin:0;">
  Lista de Citas Futuras
  <button class="boton-vista" onclick="window.location.href='index.php?p=calendario_citas'">Cambiar a modo calendario</button>
</h2>

<style>
.boton-vista {
  background:#4a90e2;
  color:#fff;
  border:none;
  padding:4px 10px;
  border-radius:6px;
  cursor:pointer;
  font-size:13px;
  transition:background 0.2s ease-in-out;
}
.boton-vista:hover {
  background:#1c75bc;
}
@media (prefers-color-scheme: dark) {
  .boton-vista {
    background: linear-gradient(135deg,#1c75bc,#0066cc);
    color:#fff;
    border:1px solid #005bb5;
  }
  .boton-vista:hover {
    background: linear-gradient(135deg,#005bb5,#1c75bc);
  }
}
</style>
  
<table>
<thead>
<tr>
    <th>ID</th>
    <th>Fecha</th>
    <th>Hora</th>
    <th>Estación</th>
    <th>Tipo</th>
    <th>Vehículo</th>
    <?php if ($is_admin): ?><th>Acciones</th><?php endif; ?>
</tr>
</thead>
<tbody>
<?php if (!empty($citas)): ?>
    <?php foreach ($citas as $cita): ?>
    <tr>
        <td><?= htmlspecialchars($cita['id_cita'] ?? '-') ?></td>
        <td><?= formatear_fecha($cita['fecha_cita']) ?></td>
        <td><?= htmlspecialchars($cita['hora_cita']) ?></td>
        <td><?= htmlspecialchars($cita['estacion_cita']) ?></td>
        <td><?= htmlspecialchars($cita['tipo_cita']) ?></td>
        <td><?= htmlspecialchars(mostrarVehiculo($cita['vehiculo'], $vehiculos)) ?></td>
      <?php if ($is_admin): ?>
        <td>
      <?php if($is_admin): ?>
            <a href="index.php?p=editar_cita&amp;id=<?= urlencode($cita['id_cita']) ?>" class="boton-editar">Editar</a>
             
            <a href="index.php?p=eliminar_cita&amp;id=<?= urlencode($cita['id_cita']) ?>" class="boton-eliminar">Eliminar</a>
        <?php endif; ?>
        </td>
        <?php endif; ?>
    </tr>
    <?php endforeach; ?>
<?php else: ?>
    <tr><td colspan="<?= $is_admin ? 7 : 6 ?>">No hay citas futuras.</td></tr>
<?php endif; ?>
</tbody>
</table>

<h4 class="small version-title" style="margin-top:12px; text-align:left;"><?= htmlspecialchars($version) ?></h4>
<p class="small version-author" style="text-align:left;"><?= htmlspecialchars($autor) ?></p>

<script>

function comprobarCaducidadITV() {

    const tipo = document.querySelector('[name="tipo_cita"]').value;

    if (tipo === "Segunda") {
        return true;
    }

    const vehiculo = document.querySelector('[name="vehiculo"]').value;
    const fechaCita = document.querySelector('[name="fecha_cita"]').value;

    if(!vehiculo || !fechaCita) return true;

    const vehiculos = <?php echo json_encode($vehiculos); ?>;

    for (let v of vehiculos) {

        if (v.matricula === vehiculo && v.caducidad_itv) {

            let itv = new Date(v.caducidad_itv);
            let cita = new Date(fechaCita);

            let diferencia = Math.round((cita - itv) / (1000*60*60*24));

            if (diferencia > 0) {
                mostrarAlertaCustom(
                    `⚠️ La fecha de la cita es posterior a la fecha de caducidad de la ITV del vehículo.<br>
                    Se está asignando <span style="color:red; font-weight:bold;">${diferencia} día(s)</span> después de la caducidad.<br><br>
                    ¿Deseas continuar?`,
                    function(confirmado){
                        if(confirmado) document.querySelector('form').submit();
                    }
                );
                return false;
            }

            let diferenciaAntes = Math.round((itv - cita) / (1000*60*60*24));

            if (diferenciaAntes > 30) {

                mostrarAlertaCustom(
                    `⚠️ La cita se ha asignado con más de 30 días de antelación respecto a la caducidad de la ITV.<br>
                    La próxima fecha de caducidad se adelantará <span style="color:red; font-weight:bold;">${diferenciaAntes} día(s)</span>.<br><br>
                    ¿Deseas continuar?`,
                    function(confirmado){
                        if(confirmado) document.querySelector('form').submit();
                    }
                );

                return false;
            }
        }
    }

    return true;
}

</script>

<script>
function mostrarAlertaCustom(mensaje, callback) {
    const modal = document.createElement('div');
    modal.style.position = 'fixed';
    modal.style.top = '0';
    modal.style.left = '0';
    modal.style.width = '100%';
    modal.style.height = '100%';
    modal.style.display = 'flex';
    modal.style.alignItems = 'center';
    modal.style.justifyContent = 'center';
    modal.style.zIndex = '9999';
    modal.style.background = 'rgba(0,0,0,0.5)';

    const caja = document.createElement('div');
    caja.style.padding = '20px';
    caja.style.borderRadius = '8px';
    caja.style.maxWidth = '400px';
    caja.style.textAlign = 'center';
    caja.style.fontSize = '14px';
    caja.style.lineHeight = '1.4';
    caja.style.color = '#000';
    caja.style.background = '#fff';
    caja.style.boxShadow = '0 2px 10px rgba(0,0,0,0.3)';

    // Detectar modo oscuro
    if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
        caja.style.color = '#ddd';
        caja.style.background = '#111';
        caja.style.boxShadow = '0 2px 10px rgba(255,255,255,0.2)';
    }

    caja.innerHTML = mensaje;

    const btnOk = document.createElement('button');
    btnOk.textContent = 'Aceptar';
    btnOk.style.margin = '10px';
    btnOk.style.padding = '5px 10px';
    btnOk.style.borderRadius = '4px';
    btnOk.style.border = 'none';
    btnOk.style.cursor = 'pointer';
    btnOk.style.background = '#4a90e2';
    btnOk.style.color = '#fff';
    btnOk.onclick = () => { document.body.removeChild(modal); callback(true); };

    const btnCancel = document.createElement('button');
    btnCancel.textContent = 'Cancelar';
    btnCancel.style.margin = '10px';
    btnCancel.style.padding = '5px 10px';
    btnCancel.style.borderRadius = '4px';
    btnCancel.style.border = 'none';
    btnCancel.style.cursor = 'pointer';
    btnCancel.style.background = '#ccc';
    btnCancel.style.color = '#000';
    btnCancel.onclick = () => { document.body.removeChild(modal); callback(false); };

    // Ajustar colores de botones en modo oscuro
    if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
        btnCancel.style.background = '#444';
        btnCancel.style.color = '#ddd';
    }

    caja.appendChild(btnOk);
    caja.appendChild(btnCancel);
    modal.appendChild(caja);
    document.body.appendChild(modal);
}
</script>
</body>
</html>
<?php break;

// ===================== desbloquear_usuario =====================
case 'desbloquear_usuario':
/* ================= SEGURIDAD ================= */

// Solo Administrador o SuperAdministrador
if (!isset($_SESSION['usuario']) || !in_array($_SESSION['tipo'], ['Administrador','SuperAdministrador'])) {
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['usuario'])) {

    $usuario_a_desbloquear = $_POST['usuario'];
    $usuarios_file = 'usuarios.json';

    if (file_exists($usuarios_file)) {

        $usuarios = json_decode(file_get_contents($usuarios_file), true);
        if (!is_array($usuarios)) $usuarios = [];

        foreach ($usuarios as &$usuario) {

            if ($usuario['usuario'] === $usuario_a_desbloquear) {

                /* ================= CONTROL DE FLOTA ================= */

                // Si es Administrador, solo puede desbloquear su flota
                if ($_SESSION['tipo'] === 'Administrador') {

                    $flota_admin = $_SESSION['flota'] ?? '';
                    $flota_usuario = $usuario['flota'] ?? '';

                    if ($flota_admin !== $flota_usuario) {
                        die("No tienes permiso para desbloquear este usuario.");
                    }
                }

                /* ================= DESBLOQUEAR ================= */

                $usuario['bloqueado'] = false;
                $usuario['intentos'] = 0;
                break;
            }
        }

        file_put_contents($usuarios_file, json_encode($usuarios, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}

header('Location: index.php?p=usuarios');
exit();
?>
<?php break;

// ===================== editar_cita =====================
case 'editar_cita':
// =====================
// VERIFICAR LOGIN Y PERMISOS
// =====================
if (!isset($_SESSION['usuario']) || !in_array($_SESSION['tipo'], ['Administrador', 'SuperAdministrador', 'Colaborador'])) {
    header('Location: index.php');
    exit();
}

$is_admin = in_array($_SESSION['tipo'], ['Administrador', 'SuperAdministrador']);
$is_superadmin = $_SESSION['tipo'] === 'SuperAdministrador';
$is_colab = in_array($_SESSION['tipo'], ['Colaborador', 'Administrador', 'SuperAdministrador']);

$flota_usuario = $_SESSION['flota'] ?? null;
$flota_texto = $is_superadmin ? "Todas las flotas" : ($flota_usuario ? strtoupper($flota_usuario) : "Sin flota asignada");

// =====================
// CARGAR CITAS Y VEHÍCULOS
// =====================
$citas_file = 'citas.json';
if (!file_exists($citas_file)) die("El archivo de citas no existe.");
$citas = json_decode(file_get_contents($citas_file), true);

$vehiculos_file = 'vehiculos.json';
if (!file_exists($vehiculos_file)) die("El archivo de vehículos no existe.");
$vehiculos = json_decode(file_get_contents($vehiculos_file), true);

// Filtrar vehículos según flota (excepto SuperAdmin)
if (!$is_superadmin && $flota_usuario) {
    $vehiculos = array_filter($vehiculos, fn($v) => isset($v['flota']) && strtoupper(trim($v['flota'])) === strtoupper(trim($flota_usuario)));
}

// Ordenar vehículos alfabéticamente
usort($vehiculos, fn($a, $b) => strnatcasecmp($a['vehiculo'], $b['vehiculo']));

// =====================
// CARGAR ESTACIONES
// =====================
$estaciones_file = 'estaciones.json';
if (!file_exists($estaciones_file)) {
    file_put_contents($estaciones_file, json_encode(['Tambre','Sionlla','Cacheiras'], JSON_PRETTY_PRINT));
}
$estaciones = json_decode(file_get_contents($estaciones_file), true);

// =====================
// BUSCAR CITA A EDITAR
// =====================
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: index.php?p=citas');
    exit();
}
$id_cita = $_GET['id'];

$cita_editar = null;
foreach ($citas as &$cita) {
    if (isset($cita['id_cita']) && $cita['id_cita'] === $id_cita) {
        $cita_editar = &$cita;
        break;
    }
}
if ($cita_editar === null) die("No se encontró la cita con el ID proporcionado.");

// =====================
// PROCESAR FORMULARIO
// =====================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $flota_cita = $is_superadmin ? ($_POST['flota_cita'] ?? '') : $flota_usuario;

    if (!empty($_POST['fecha_cita']) && !empty($_POST['hora_cita']) && !empty($_POST['estacion_cita']) && !empty($_POST['tipo_cita']) && (!empty($flota_cita))) {
        $cita_editar['fecha_cita'] = $_POST['fecha_cita'];
        $cita_editar['hora_cita'] = $_POST['hora_cita'];
        $cita_editar['estacion_cita'] = $_POST['estacion_cita'];
        $cita_editar['tipo_cita'] = $_POST['tipo_cita'];
        $cita_editar['vehiculo'] = $_POST['vehiculo'] ?? '';
        $cita_editar['flota'] = $flota_cita;

        if (file_put_contents($citas_file, json_encode($citas, JSON_PRETTY_PRINT))) {
            header('Location: index.php?p=citas');
            exit();
        } else {
            $error = "No se pudo guardar la cita. Verifique los permisos del archivo.";
        }
    } else {
        $error = $is_superadmin ? "Todos los campos, incluida la flota, son obligatorios." : "Todos los campos son obligatorios.";
    }
}

// =====================
// FUNCIONES
// =====================
function formatear_fecha($fecha) {
    $fecha_obj = DateTime::createFromFormat('Y-m-d', $fecha);
    return $fecha_obj ? $fecha_obj->format('d/m/Y') : $fecha;
}

// =====================
// CARGAR VERSION Y AUTOR
// =====================
$version_text = 'v.1.4';
$autor_text = 'B174M3 // XaeK';
if (file_exists('version.xk')) {
    $lines = file('version.xk', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $version_text = $lines[0] ?? $version_text;
    $autor_text = $lines[1] ?? $autor_text;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>ITVControl</title>
<link rel="icon" href="images/logo.webp">
<link rel="manifest" href="manifest.json">
<link rel="apple-touch-icon" sizes="180x180" href="images/logo.webp">
<link rel="stylesheet" href="style.css">

<style>
body{margin:15px;font-family:Arial,sans-serif;}
input, select{padding:4px; margin-top:4px; width:250px;}
input[type=submit]{padding:6px 12px;background:#004aad;color:#fff;border:none;cursor:pointer;}
input[type=submit]:hover{background:#0066ff;}
/* Colores de botones adicionales */
.negro{background:black;color:grey;}
.rojo_intenso{background:#cc0000;color:white;}
.naranja_intenso{background:#ff6600;color:white;}
.naranja_suave{background:#ffae0d;color:white;}
.azul{background:#3399ff;color:white;}
.verde{background:#4CAF50;color:white;}

/* Modo oscuro */
@media (prefers-color-scheme: dark){
    body{background:#000;color:#ddd;}
    input, select{background:#222;color:#ddd;border:1px solid #555;}
    input[type=submit]{background:#0066ff;color:#fff;}
    input[type=submit]:hover{background:#3399ff;}
    .menu img{filter: invert(1) hue-rotate(180deg);}
    h1 img{filter:none;}
}
h1, h2{color:inherit;}

/* ===== INFO DE USUARIO ===== */
.user-info{
    position: fixed;
    top: 10px;
    right: 15px;
    text-align: right;
    font-size: 14px;
    background: rgba(255,255,255,0.6); /* mismo fondo que el código 2 */
    padding: 5px 10px; /* relleno idéntico */
    border-radius: 8px;
}
.user-info strong{ display: block; }
.user-info small{ color: #4a90e2; font-weight: bold; }
  
/* ===== MODO OSCURO ===== */
@media (prefers-color-scheme: dark){
    .user-info{ background: rgba(0,0,0,0.5); }
    .user-info small{ color: #3399ff; }
    .user-info #fecha-hora{ color:#ddd; }
}
</style>
<script>
function validarCita(form) {
    return comprobarCaducidadITV();
}

function actualizarFechaHora(){
    const elem = document.getElementById('fecha-hora');
    if (!elem) return;
    const d = new Date();
    elem.textContent = d.toLocaleDateString('es-ES') + ' ' + d.toLocaleTimeString('es-ES');
}
window.addEventListener('load', actualizarFechaHora);
setInterval(actualizarFechaHora, 1000);
</script>
</head>
<body>

<!-- ===== USUARIO/TIPO/FLOTA/FECHA-HORA ===== -->
<div class="user-info">
    <strong><?= htmlspecialchars($_SESSION['usuario']) ?> | <?= htmlspecialchars($_SESSION['tipo']) ?></strong>
    <small><?= htmlspecialchars($flota_texto) ?></small>
    <div id="fecha-hora"></div>
</div>

<h1><img src="images/logo.webp" width="30" style="vertical-align: middle;"> Editar Cita </h1>
<hr style="border:1px solid #4a90e2; margin:10px 0 20px 0;">

<!-- ===== MENU ===== -->
<div class="menu">
  <a title="index" href="index.php"><img src="images/index.webp" alt="index" width="80"></a>
  <a title="citas" href="index.php?p=citas"><img src="images/citas.webp" alt="citas" width="80"></a>
  <a title="vehiculos" href="index.php?p=vehiculos"><img src="images/vehiculos.webp" alt="vehiculos" width="80"></a>
  <?php if($is_admin): ?>
    <a title="estaciones" href="index.php?p=estaciones"><img src="images/estaciones.webp" alt="estaciones" width="80"></a>
    <a title="seguridad" href="index.php?p=ips_bloqueadas"><img src="images/secury.webp" alt="seguridad" width="80"></a>
    <a title="usuarios" href="index.php?p=usuarios"><img src="images/usuarios.webp" alt="usuarios" width="80"></a>
  <?php endif; ?>
  <a title="imprimir" href="index.php?p=imprimir"><img src="images/imprimir.webp" alt="imprimir" width="80"></a>
  <a title="logout" href="index.php?p=logout"><img src="images/logout.webp" alt="logout" width="80"></a>
</div>

<br><br><br>

<!-- ===== FORMULARIO ===== -->
<form method="POST" onsubmit="return validarCita(this);">
    <label><strong>ID de Cita:</strong> <?= htmlspecialchars($cita_editar['id_cita']) ?></label><br>

    <label>Fecha de Cita:</label>
    <input type="date" name="fecha_cita" value="<?= htmlspecialchars($cita_editar['fecha_cita']) ?>" required><br>

    <label>Hora de Cita:</label>
    <input type="time" name="hora_cita" value="<?= htmlspecialchars($cita_editar['hora_cita']) ?>" required><br>

<label>Estación:</label>
<select name="estacion_cita" required>
    <?php
    // Aseguramos que $estaciones sea un array
    if (!is_array($estaciones)) $estaciones = [];

    foreach ($estaciones as $estacion_obj) {
        // Estacion es ahora un objeto con nombre y flotas
        $nombre_estacion = $estacion_obj['nombre'] ?? '';
        $flotas_estacion = $estacion_obj['flotas'] ?? [];

        // Solo mostrar estaciones de la flota del usuario o todas si SuperAdmin
        if (!$is_superadmin && $flota_usuario && !in_array(strtoupper(trim($flota_usuario)), array_map('strtoupper', $flotas_estacion))) {
            continue;
        }

        $selected = ($cita_editar['estacion_cita'] ?? '') === $nombre_estacion ? 'selected' : '';
        echo '<option value="'.htmlspecialchars($nombre_estacion).'" '.$selected.'>'.htmlspecialchars($nombre_estacion).'</option>';
    }
    ?>
</select><br>

    <label>Tipo de Cita:</label>
    <select name="tipo_cita">
        <option value="Primera" <?= ($cita_editar['tipo_cita'] ?? '')==='Primera'?'selected':'' ?>>Primera</option>
        <option value="Segunda" <?= ($cita_editar['tipo_cita'] ?? '')==='Segunda'?'selected':'' ?>>Segunda</option>
    </select><br>

<label>Vehículo:</label>
<select name="vehiculo">
    <option value="">Sin asignar</option>
    <?php
    $flota_usuario = $_SESSION['flota'] ?? '';
    foreach ($vehiculos as $vehiculo):
        // Mostrar solo vehículos de la flota del usuario o el vehículo ya asignado a la cita
        if (!$is_superadmin && isset($vehiculo['flota']) && strtoupper(trim($vehiculo['flota'])) !== strtoupper(trim($flota_usuario)) && $vehiculo['matricula'] !== ($cita_editar['vehiculo'] ?? '')) continue;
    ?>
        <option value="<?= htmlspecialchars($vehiculo['matricula']) ?>" <?= ($cita_editar['vehiculo'] ?? '') === $vehiculo['matricula'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($vehiculo['vehiculo']) ?> - <?= htmlspecialchars($vehiculo['matricula']) ?>
        </option>
    <?php endforeach; ?>
</select><br>

    <?php if ($is_superadmin): ?>
        <label>Flota:</label>
        <select name="flota_cita" required>
            <option value="">Seleccionar Flota</option>
            <?php
            $usuarios_file = 'usuarios.json';
            $flotas = [];
            if (file_exists($usuarios_file)) {
                $usuarios = json_decode(file_get_contents($usuarios_file), true);
                foreach ($usuarios as $u) {
                    if (isset($u['flota']) && !in_array($u['flota'], $flotas)) {
                        $flotas[] = $u['flota'];
                    }
                }
            }
            foreach ($flotas as $flota):
            ?>
                <option value="<?= htmlspecialchars($flota) ?>" <?= ($cita_editar['flota'] ?? '') === $flota ? 'selected' : '' ?>>
                    <?= htmlspecialchars($flota) ?>
                </option>
            <?php endforeach; ?>
        </select><br>
    <?php endif; ?>

    <input type="submit" value="Guardar Cambios">
</form>

<?php if (isset($error)): ?>
    <p class="rojo_intenso"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<h4 class="small" style="text-align:left; margin-top:12px;"><?= htmlspecialchars($version_text) ?></h4>
<p class="small" style="text-align:left;"><?= htmlspecialchars($autor_text) ?></p>


<script>
function comprobarCaducidadITV() {

    const tipo = document.querySelector('[name="tipo_cita"]').value;

    if (tipo === "Segunda") {
        return true;
    }

    const vehiculo = document.querySelector('[name="vehiculo"]').value;
    const fechaCita = document.querySelector('[name="fecha_cita"]').value;

    if (!vehiculo || !fechaCita) return true;

    const vehiculos = <?php echo json_encode(array_values($vehiculos)); ?>;

    for (let v of vehiculos) {

        if (v.matricula === vehiculo && v.caducidad_itv) {

            let itv = new Date(v.caducidad_itv);
            let cita = new Date(fechaCita);

            let diferencia = Math.round(
                (cita - itv) / (1000 * 60 * 60 * 24)
            );

            if (diferencia > 0) {

                mostrarAlertaCustom(
                    `⚠️ La fecha de la cita es posterior a la fecha de caducidad de la ITV del vehículo.<br>
                    Se está asignando <span style="color:red; font-weight:bold;">${diferencia} día(s)</span> después de la caducidad.<br><br>
                    ¿Deseas continuar?`,
                    function(confirmado) {
                        if (confirmado) {
                            document.querySelector('form').submit();
                        }
                    }
                );

                return false;
            }

            let diferenciaAntes = Math.round(
                (itv - cita) / (1000 * 60 * 60 * 24)
            );

            if (diferenciaAntes > 30) {

                mostrarAlertaCustom(
                    `⚠️ La cita se ha asignado con más de 30 días de antelación respecto a la caducidad de la ITV.<br>
                    La próxima fecha de caducidad se adelantará <span style="color:red; font-weight:bold;">${diferenciaAntes} día(s)</span>.<br><br>
                    ¿Deseas continuar?`,
                    function(confirmado) {
                        if (confirmado) {
                            document.querySelector('form').submit();
                        }
                    }
                );

                return false;
            }
        }
    }

    return true;
}


function mostrarAlertaCustom(mensaje, callback) {

    const modal = document.createElement('div');
    modal.style.position = 'fixed';
    modal.style.top = '0';
    modal.style.left = '0';
    modal.style.width = '100%';
    modal.style.height = '100%';
    modal.style.display = 'flex';
    modal.style.alignItems = 'center';
    modal.style.justifyContent = 'center';
    modal.style.zIndex = '9999';
    modal.style.background = 'rgba(0,0,0,0.5)';

    const caja = document.createElement('div');
    caja.style.padding = '20px';
    caja.style.borderRadius = '8px';
    caja.style.maxWidth = '400px';
    caja.style.textAlign = 'center';
    caja.style.fontSize = '14px';
    caja.style.lineHeight = '1.4';
    caja.style.color = '#000';
    caja.style.background = '#fff';
    caja.style.boxShadow = '0 2px 10px rgba(0,0,0,0.3)';

    // Detectar modo oscuro
    if (window.matchMedia &&
        window.matchMedia('(prefers-color-scheme: dark)').matches) {

        caja.style.color = '#ddd';
        caja.style.background = '#111';
        caja.style.boxShadow = '0 2px 10px rgba(255,255,255,0.2)';
    }

    caja.innerHTML = mensaje;

    const btnOk = document.createElement('button');
    btnOk.textContent = 'Aceptar';
    btnOk.style.margin = '10px';
    btnOk.style.padding = '5px 10px';
    btnOk.style.borderRadius = '4px';
    btnOk.style.border = 'none';
    btnOk.style.cursor = 'pointer';
    btnOk.style.background = '#4a90e2';
    btnOk.style.color = '#fff';

    btnOk.onclick = () => {
        document.body.removeChild(modal);
        callback(true);
    };

    const btnCancel = document.createElement('button');
    btnCancel.textContent = 'Cancelar';
    btnCancel.style.margin = '10px';
    btnCancel.style.padding = '5px 10px';
    btnCancel.style.borderRadius = '4px';
    btnCancel.style.border = 'none';
    btnCancel.style.cursor = 'pointer';
    btnCancel.style.background = '#ccc';
    btnCancel.style.color = '#000';

    btnCancel.onclick = () => {
        document.body.removeChild(modal);
        callback(false);
    };

    // Ajustar colores en modo oscuro
    if (window.matchMedia &&
        window.matchMedia('(prefers-color-scheme: dark)').matches) {

        btnCancel.style.background = '#444';
        btnCancel.style.color = '#ddd';
    }

    caja.appendChild(btnOk);
    caja.appendChild(btnCancel);
    modal.appendChild(caja);
    document.body.appendChild(modal);
}
</script>
</body>
</html>
<?php break;

// ===================== editar_usuario =====================
case 'editar_usuario':
/* ================= SEGURIDAD BASE ================= */
if (!isset($_SESSION['usuario']) || !in_array($_SESSION['tipo'], ['Administrador', 'SuperAdministrador'])) {
    header('Location: index.php');
    exit();
}

$is_superadmin = $_SESSION['tipo'] === 'SuperAdministrador';
$flota_admin = $_SESSION['flota'] ?? '';

$usuarios_file = 'usuarios.json';
if (!file_exists($usuarios_file)) {
    die("El archivo de usuarios no existe.");
}

$usuarios = json_decode(file_get_contents($usuarios_file), true);
if (!is_array($usuarios)) $usuarios = [];

/* ================= OBTENER USUARIO ================= */
if (!isset($_GET['usuario'])) {
    die("Usuario no especificado.");
}

$usuario_id = $_GET['usuario'];
$usuario_encontrado = false;

foreach ($usuarios as &$usuario) {
    if ($usuario['usuario'] === $usuario_id) {
        $usuario_encontrado = true;
        break;
    }
}

if (!$usuario_encontrado) {
    die("Usuario no encontrado.");
}

// Obtener flota del usuario
$flota_usuario = $_SESSION['flota'] ?? null;
$flota_texto = $is_superadmin ? "Todas las flotas" : ($flota_usuario ? strtoupper($flota_usuario) : "Sin flota asignada");

/* ================= CONTROL DE FLOTA ================= */
if ($_SESSION['tipo'] === 'Administrador') {
    $flota_usuario = $usuario['flota'] ?? '';

    if ($flota_admin !== $flota_usuario) {
        die("No tienes permiso para editar este usuario.");
    }

    if ($usuario['tipo'] === 'SuperAdministrador') {
        die("No tienes permiso para editar este usuario.");
    }
}

// =====================
// FUNCIONES
// =====================
function formatear_fecha($fecha) {
    $fecha_obj = DateTime::createFromFormat('Y-m-d', $fecha);
    return $fecha_obj ? $fecha_obj->format('d/m/Y') : $fecha;
}

/* ================= PROCESAR FORMULARIO ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nuevo_usuario = trim($_POST['usuario'] ?? '');
    $pass = $_POST['contraseña'] ?? '';
    $pass2 = $_POST['confirmar_contraseña'] ?? '';
    $nuevo_tipo = $_POST['tipo'] ?? '';

    if ($nuevo_usuario === '' || $nuevo_tipo === '') {
        $error = "Usuario y tipo son obligatorios.";
    } elseif ($pass !== $pass2) {
        $error = "Las contraseñas no coinciden.";
    } else {

        /* ===== Restricciones para Administrador ===== */
        if ($_SESSION['tipo'] === 'Administrador') {
            if ($nuevo_tipo === 'SuperAdministrador') {
                die("No tienes permiso para asignar ese tipo.");
            }
            $usuario['flota'] = $flota_admin; // forzar misma flota
        }

        /* ===== Actualizar datos ===== */
        $usuario['usuario'] = $nuevo_usuario;
        $usuario['tipo'] = $nuevo_tipo;

        if ($is_superadmin && isset($_POST['flota'])) {
            $usuario['flota'] = strtoupper(trim($_POST['flota']));
        }

        // SOLO actualizar la contraseña si se ha rellenado
        if ($pass !== '') {
            $usuario['contraseña'] = password_hash($pass, PASSWORD_DEFAULT);
        }

        if (file_put_contents($usuarios_file, json_encode($usuarios, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
            header('Location: index.php?p=usuarios');
            exit();
        } else {
            $error = "No se pudo actualizar el usuario.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>ITVControl</title>
<link rel="icon" href="images/logo.webp">
<link rel="manifest" href="manifest.json">
<link rel="apple-touch-icon" sizes="180x180" href="images/logo.webp">
<link rel="stylesheet" href="style.css">

<style>
body{margin:15px;font-family:Arial,sans-serif;}
input, select{padding:4px; margin-top:4px; width:250px;}
input[type=submit]{padding:6px 12px;background:#004aad;color:#fff;border:none;cursor:pointer;}
input[type=submit]:hover{background:#0066ff;}
.negro{background:black;color:grey;}
.rojo_intenso{background:#cc0000;color:white;}
.naranja_intenso{background:#ff6600;color:white;}
.naranja_suave{background:#ffae0d;color:white;}
.azul{background:#3399ff;color:white;}
.verde{background:#4CAF50;color:white;}
/* Modo oscuro */
@media (prefers-color-scheme: dark){
    body{background:#000;color:#ddd;}
    input, select{background:#222;color:#ddd;border:1px solid #555;}
    input[type=submit]{background:#0066ff;color:#fff;}
    input[type=submit]:hover{background:#3399ff;}
    .menu img{filter: invert(1) hue-rotate(180deg);}
    h1 img{filter:none;}
}
h1, h2{color:inherit;}

/* ===== INFO DE USUARIO ===== */
.user-info{
    position: fixed;
    top: 10px;
    right: 15px;
    text-align: right;
    font-size: 14px;
    background: rgba(255,255,255,0.6); /* mismo fondo que el código 2 */
    padding: 5px 10px; /* relleno idéntico */
    border-radius: 8px;
}
.user-info strong{ display: block; }
.user-info small{ color: #4a90e2; font-weight: bold; }

/* ===== MODO OSCURO ===== */
@media (prefers-color-scheme: dark){
    .user-info{ background: rgba(0,0,0,0.5); }
    .user-info small{ color: #3399ff; }
}
  
</style>
</head>
<body>

<div class="user-info">
    <strong><?= htmlspecialchars($_SESSION['usuario']) ?> | <?= htmlspecialchars($_SESSION['tipo']) ?></strong>
    <small><?= htmlspecialchars($flota_texto) ?></small>
    <div id="fecha-hora"></div>
</div>

<h1><img src="images/logo.webp" width="30" style="vertical-align: middle;"> Editar Usuario </h1>
<hr style="border:1px solid #4a90e2; margin:10px 0 20px 0;">

    <div class="menu">
      <a title="index" href="index.php"><img src="images/index.webp" alt="index" width="80"></a>
      <a title="citas" href="index.php?p=citas"><img src="images/citas.webp" alt="citas" width="80"></a>
      <a title="vehiculos" href="index.php?p=vehiculos"><img src="images/vehiculos.webp" alt="vehiculos" width="80"></a>
     <?php if(in_array($_SESSION['tipo'], ['Administrador','SuperAdministrador'])): ?>
      <a title="estaciones" href="index.php?p=estaciones"><img src="images/estaciones.webp" alt="estaciones" width="80"></a>
      <a title="seguridad" href="index.php?p=ips_bloqueadas"><img src="images/secury.webp" alt="seguridad" width="80"></a>
      <a title="usuarios" href="index.php?p=usuarios"><img src="images/usuarios.webp" alt="usuarios" width="80"></a>
     <?php endif; ?>
      <a title="imprimir" href="index.php?p=imprimir"><img src="images/imprimir.webp" alt="imprimir" width="80"></a>
      <a title="logout" href="index.php?p=logout"><img src="images/logout.webp" alt="logout" width="80"></a>
    </div>
  
    <br><br><br>
  
<h1>Editar Usuario: <?= htmlspecialchars($usuario['usuario']) ?></h1>

<?php if (isset($error)): ?>
<p class="rojo_intenso"><strong><?= htmlspecialchars($error) ?></strong></p>
<?php endif; ?>

<form method="POST" style="max-width:400px;">
    Usuario:<br>
    <input type="text" name="usuario" value="<?= htmlspecialchars($usuario['usuario']) ?>" required><br><br>

    Contraseña (dejar vacía para mantener la actual):<br>
    <input type="password" name="contraseña"><br><br>

    Confirmar Contraseña:<br>
    <input type="password" name="confirmar_contraseña"><br><br>

    Tipo:<br>
    <select name="tipo">
        <option value="Usuario" <?= $usuario['tipo'] === 'Usuario' ? 'selected' : '' ?>>Usuario</option>
        <option value="Colaborador" <?= $usuario['tipo'] === 'Colaborador' ? 'selected' : '' ?>>Colaborador</option>
        <option value="Administrador" <?= $usuario['tipo'] === 'Administrador' ? 'selected' : '' ?>>Administrador</option>
        <?php if ($is_superadmin): ?>
            <option value="SuperAdministrador" <?= $usuario['tipo'] === 'SuperAdministrador' ? 'selected' : '' ?>>SuperAdministrador</option>
        <?php endif; ?>
    </select><br><br>

    <?php if ($is_superadmin): ?>
    Flota:<br>
    <input type="text" name="flota" value="<?= htmlspecialchars($usuario['flota'] ?? '') ?>" required style="text-transform:uppercase;"><br><br>
    <?php else: ?>
    <strong>Flota:</strong> <?= htmlspecialchars($usuario['flota'] ?? '') ?><br><br>
    <?php endif; ?>

    <input type="submit" value="Actualizar Usuario">
</form>

<script>
function actualizarFechaHora(){
    const d=new Date();
    document.getElementById('fecha-hora').innerText =
        d.toLocaleDateString('es-ES')+' '+d.toLocaleTimeString('es-ES');
}
actualizarFechaHora();
setInterval(actualizarFechaHora,1000);
</script>
  
</body>
</html>
<?php break;

// ===================== editar_vehiculo =====================
case 'editar_vehiculo':
// Verificar login y permisos
if (!isset($_SESSION['usuario']) || !in_array($_SESSION['tipo'], ['Administrador','SuperAdministrador','Colaborador'])) {
    header('Location: index.php'); exit();
}

$is_admin = in_array($_SESSION['tipo'], ['Administrador','SuperAdministrador']);
$is_superadmin = $_SESSION['tipo'] === 'SuperAdministrador';
$is_colab = in_array($_SESSION['tipo'], ['Colaborador','Administrador','SuperAdministrador']);

// Obtener flota del usuario
$flota_usuario = $_SESSION['flota'] ?? null;
$flota_texto = $is_superadmin ? "Todas las flotas" : ($flota_usuario ? strtoupper($flota_usuario) : "Sin flota asignada");

// Obtener matrícula del vehículo
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: index.php?p=vehiculos'); exit();
}
$id_vehiculo = $_GET['id'];

// Cargar vehículos
$vehiculos_file = 'vehiculos.json';
if (!file_exists($vehiculos_file)) die("El archivo de vehículos no existe.");
$vehiculos = json_decode(file_get_contents($vehiculos_file), true);

// Buscar vehículo a editar
$vehiculo_editar = null;
foreach ($vehiculos as &$vehiculo) {
    if ($vehiculo['matricula'] === $id_vehiculo) {
        $vehiculo_editar = &$vehiculo; break;
    }
}
if ($vehiculo_editar === null) die("No se encontró el vehículo: " . htmlspecialchars($id_vehiculo));

// Cargar flotas de usuarios
$usuarios_file = 'usuarios.json';
$flotas = [];
if (file_exists($usuarios_file)) {
    $usuarios = json_decode(file_get_contents($usuarios_file), true);
    foreach ($usuarios as $u) {
        if (isset($u['flota']) && !in_array($u['flota'],$flotas)) {
            $flotas[] = $u['flota'];
        }
    }
}

// Funciones
function calcular_dias_restantes($caducidad_itv) {
    $fecha_actual = new DateTime('today');
    $fecha_caducidad = new DateTime($caducidad_itv);
    $fecha_caducidad->setTime(0,0,0);
    $intervalo = $fecha_actual->diff($fecha_caducidad);
    return (int)$intervalo->format('%r%a');
}
function obtener_color_y_texto($vehiculo) {
    $estado = $vehiculo['estado'];
    $dias_restantes = calcular_dias_restantes($vehiculo['caducidad_itv']);
    if ($estado==='ITV RECHAZADA') return ['color'=>'rojo_intenso','texto_dias'=>'ITV RECHAZADA'];
    if ($estado==='BAJA') return ['color'=>'negro','texto_dias'=>'-'];
    if ($dias_restantes<0) return ['color'=>'rojo_intenso','texto_dias'=>'Caducada hace '.abs($dias_restantes).' día'.(abs($dias_restantes)==1?'':'s')];
    if ($dias_restantes<=1) return ['color'=>'rojo_intenso','texto_dias'=>$dias_restantes.' día'.($dias_restantes==1?'':'s')];
    if ($dias_restantes<10) return ['color'=>'naranja_intenso','texto_dias'=>$dias_restantes.' días'];
    if ($dias_restantes<=20) return ['color'=>'naranja_suave','texto_dias'=>$dias_restantes.' días'];
    if ($dias_restantes<=35) return ['color'=>'azul','texto_dias'=>$dias_restantes.' días'];
    return ['color'=>'verde','texto_dias'=>$dias_restantes.' días'];
}
function formatear_fecha($fecha) {
    $d = DateTime::createFromFormat('Y-m-d',$fecha);
    return $d ? $d->format('d/m/Y') : $fecha;
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (!empty($_POST['estado']) && !empty($_POST['caducidad_itv'])) {
        $vehiculo_editar['estado'] = $_POST['estado'];
        $vehiculo_editar['caducidad_itv'] = $_POST['caducidad_itv'];

        // Solo SuperAdmin puede cambiar flota
        if ($is_superadmin && isset($_POST['flota']) && in_array($_POST['flota'],$flotas)) {
            $vehiculo_editar['flota'] = $_POST['flota'];
        }

        if (file_put_contents($vehiculos_file,json_encode($vehiculos,JSON_PRETTY_PRINT))) {
            header('Location: index.php?p=vehiculos'); exit();
        } else $error="No se pudieron guardar los cambios.";
    } else $error="Todos los campos son obligatorios.";
}

// Cargar versión y autor
$version_file='version.xk';
$version='v.1.0'; $autor='';
if(file_exists($version_file)){
    $lines=file($version_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if(isset($lines[0])) $version=$lines[0];
    if(isset($lines[1])) $autor=$lines[1];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>ITVControl</title>
<link rel="icon" href="images/logo.webp">
<link rel="manifest" href="manifest.json">
<link rel="apple-touch-icon" sizes="180x180" href="images/logo.webp">
<link rel="stylesheet" href="style.css">

<style>
body{margin:15px;font-family:Arial,sans-serif;}
input, select{padding:4px; margin-top:4px; width:250px;}
input[type=submit]{padding:6px 12px;background:#004aad;color:#fff;border:none;cursor:pointer;}
input[type=submit]:hover{background:#0066ff;}
.negro{background:black;color:grey;}
.rojo_intenso{background:#cc0000;color:white;}
.naranja_intenso{background:#ff6600;color:white;}
.naranja_suave{background:#ffae0d;color:white;}
.azul{background:#3399ff;color:white;}
.verde{background:#4CAF50;color:white;}
/* Modo oscuro */
@media (prefers-color-scheme: dark){
    body{background:#000;color:#ddd;}
    input, select{background:#222;color:#ddd;border:1px solid #555;}
    input[type=submit]{background:#0066ff;color:#fff;}
    input[type=submit]:hover{background:#3399ff;}
    .menu img{filter: invert(1) hue-rotate(180deg);}
    h1 img{filter:none;}
}
h1, h2{color:inherit;}

/* ===== INFO DE USUARIO ===== */
.user-info{
    position: fixed;
    top: 10px;
    right: 15px;
    text-align: right;
    font-size: 14px;
    background: rgba(255,255,255,0.6); /* mismo fondo que el código 2 */
    padding: 5px 10px; /* relleno idéntico */
    border-radius: 8px;
}
.user-info strong{ display: block; }
.user-info small{ color: #4a90e2; font-weight: bold; }

/* ===== MODO OSCURO ===== */
@media (prefers-color-scheme: dark){
    .user-info{ background: rgba(0,0,0,0.5); }
    .user-info small{ color: #3399ff; }
}
  
</style>
</head>
<body>

<div class="user-info">
    <strong><?= htmlspecialchars($_SESSION['usuario']) ?> | <?= htmlspecialchars($_SESSION['tipo']) ?></strong>
    <small><?= htmlspecialchars($flota_texto) ?></small>
    <div id="fecha-hora"></div>
</div>

<h1><img src="images/logo.webp" width="30" style="vertical-align: middle;"> Página Principal - Gestión de ITV</h1>
<hr style="border:1px solid #4a90e2; margin:10px 0 20px 0;">

    <div class="menu">
      <a title="index" href="index.php"><img src="images/index.webp" alt="index" width="80"></a>
      <a title="citas" href="index.php?p=citas"><img src="images/citas.webp" alt="citas" width="80"></a>
      <a title="vehiculos" href="index.php?p=vehiculos"><img src="images/vehiculos.webp" alt="vehiculos" width="80"></a>
     <?php if(in_array($_SESSION['tipo'], ['Administrador','SuperAdministrador'])): ?>
      <a title="estaciones" href="index.php?p=estaciones"><img src="images/estaciones.webp" alt="estaciones" width="80"></a>
      <a title="seguridad" href="index.php?p=ips_bloqueadas"><img src="images/secury.webp" alt="seguridad" width="80"></a>
      <a title="usuarios" href="index.php?p=usuarios"><img src="images/usuarios.webp" alt="usuarios" width="80"></a>
     <?php endif; ?>
      <a title="imprimir" href="index.php?p=imprimir"><img src="images/imprimir.webp" alt="imprimir" width="80"></a>
      <a title="logout" href="index.php?p=logout"><img src="images/logout.webp" alt="logout" width="80"></a>
    </div>
  
    <br><br><br>
  
<h2>Editar Vehículo</h2>
<?php if(isset($error)): ?><p style="color:red;"><?= htmlspecialchars($error) ?></p><?php endif; ?>

<form method="POST" style="max-width:400px;">
    <label>Vehículo:</label>
    <input 
        type="text" 
        name="vehiculo"
        value="<?= htmlspecialchars($vehiculo_editar['vehiculo']) ?>" 
        <?= !$is_superadmin ? 'readonly' : '' ?>
        style="font-size: calc(1em + 4px);"
    >
  <br>
    <label>Matrícula:</label>
    <input 
        type="text" 
        name="matricula"
        value="<?= htmlspecialchars($vehiculo_editar['matricula']) ?>" 
        <?= !$is_superadmin ? 'readonly' : '' ?>
        style="font-size: calc(1em + 4px);"
    >
  <br>
    <label>Estado:</label>
    <select name="estado">
        <option value="ACTIVO" <?= $vehiculo_editar['estado']==='ACTIVO'?'selected':'' ?>>ACTIVO</option>
        <option value="ITV RECHAZADA" <?= $vehiculo_editar['estado']==='ITV RECHAZADA'?'selected':'' ?>>ITV RECHAZADA</option>
        <option value="BAJA" <?= $vehiculo_editar['estado']==='BAJA'?'selected':'' ?>>BAJA</option>
    </select>
 <br>
    <label>Caducidad ITV:</label>
    <input type="date" name="caducidad_itv" value="<?= htmlspecialchars($vehiculo_editar['caducidad_itv']) ?>" required>
  <br>
    <?php if($is_superadmin): ?>
    <label>Flota:</label>
    <select name="flota">
        <?php foreach($flotas as $f): ?>
        <option value="<?= htmlspecialchars($f) ?>" <?= ($vehiculo_editar['flota'] ?? '') === $f ? 'selected':'' ?>><?= htmlspecialchars($f) ?></option>
        <?php endforeach; ?>
    </select>
    <br>
    <?php endif; ?>
    <input type="submit" value="Guardar Cambios">
</form>

<h4 class="small" style="margin-top:12px;text-align:left;"><?= htmlspecialchars($version) ?></h4>
<p class="small" style="text-align:left;"><?= htmlspecialchars($autor) ?></p>

<script>
function actualizarFechaHora(){
    const d=new Date();
    document.getElementById('fecha-hora').innerText =
        d.toLocaleDateString('es-ES')+' '+d.toLocaleTimeString('es-ES');
}
actualizarFechaHora();
setInterval(actualizarFechaHora,1000);
</script>
</body>
</html>
<?php break;

// ===================== eliminar_cita =====================
case 'eliminar_cita':
// Verificar que sea administrador
if (!isset($_SESSION['usuario']) || !in_array($_SESSION['tipo'], ['Administrador', 'SuperAdministrador'])) {
    header('Location: index.php');
    exit();
}

// Verificar que se reciba el ID de la cita
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("ID de cita no válido.");
}

$id_cita = $_GET['id'];

// Cargar citas desde JSON
$citas_file = 'citas.json';
if (!file_exists($citas_file)) {
    die("El archivo de citas no existe.");
}
$citas = json_decode(file_get_contents($citas_file), true);

// Buscar la cita
$cita_encontrada = null;
foreach ($citas as $index => $cita) {
    if (isset($cita['id_cita']) && $cita['id_cita'] === $id_cita) {
        $cita_encontrada = ['index' => $index, 'cita' => $cita];
        break;
    }
}

if (!$cita_encontrada) {
    die("No se encontró la cita solicitada.");
}

// Procesar confirmación
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['confirmar']) && $_POST['confirmar'] === 'sí') {
        unset($citas[$cita_encontrada['index']]);
        $citas = array_values($citas); // Reindexar

        if (file_put_contents($citas_file, json_encode($citas, JSON_PRETTY_PRINT))) {
            header('Location: index.php?p=citas');
            exit();
        } else {
            $error = "No se pudo eliminar la cita. Verifique los permisos del archivo.";
        }
    } else {
        header('Location: index.php?p=citas');
        exit();
    }
}

// Función para formatear fecha
function formatear_fecha($fecha) {
    $fecha_obj = DateTime::createFromFormat('Y-m-d', $fecha);
    return $fecha_obj ? $fecha_obj->format('d/m/Y') : $fecha;
}

// Cargar versión y autor desde version.xk
$version_text = 'v.1.4';
$autor_text = 'B174M3 // XaeK';
if (file_exists('version.xk')) {
    $lines = file('version.xk', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $version_text = $lines[0] ?? $version_text;
    $autor_text = $lines[1] ?? $autor_text;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Eliminar Cita</title>

<link rel="icon" href="images/logo.webp">
<link rel="manifest" href="manifest.json">
<link rel="apple-touch-icon" sizes="180x180" href="images/logo.webp">
<link rel="stylesheet" href="style.css">
<style>
body { margin:15px; font-family:Arial,sans-serif; }

/* Botones */
button { padding:8px 15px; margin-right:10px; border-radius:4px; cursor:pointer; }
button[type="submit"] { border:1px solid #555; background:#ccc; color:#000; }
button[type="submit"]:hover { background:#bbb; }

/* Mensajes de error */
.rojo_intenso { color:#cc0000; }

/* ===== MODO OSCURO AUTOMÁTICO ===== */
@media (prefers-color-scheme: dark) {
    body { background:#000; color:#ddd; }
    h1,h2,h3,h4,p,strong { color:#ddd; }
    button { background:#222; color:#fff; border:1px solid #666; }
    button:hover { background:#333; }
    .rojo_intenso { color:#f33; }
}
</style>
</head>
<body>

<br>

<h1>Eliminar Cita</h1>

<br>

<?php if (isset($error)): ?>
    <p class="rojo_intenso"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<p>¿Estás seguro de que deseas eliminar la cita <strong><?= htmlspecialchars($cita_encontrada['cita']['id_cita']) ?></strong>  
del <strong><?= formatear_fecha($cita_encontrada['cita']['fecha_cita']) ?></strong> a las  
<strong><?= htmlspecialchars($cita_encontrada['cita']['hora_cita']) ?></strong> en la estación  
<strong><?= htmlspecialchars($cita_encontrada['cita']['estacion_cita']) ?></strong>?</p>

<br>

<form method="POST">
    <button type="submit" name="confirmar" value="sí">Sí, eliminar</button>
    <button type="submit" name="confirmar" value="no">Cancelar</button>
</form>

<h4 class="small" style="margin-top:12px; text-align:left;"><?= htmlspecialchars($version_text) ?></h4>
<p class="small" style="text-align:left;"><?= htmlspecialchars($autor_text) ?></p>

</body>
</html>
<?php break;

// ===================== eliminar_usuario =====================
case 'eliminar_usuario':
/* ================= SEGURIDAD BASE ================= */

if (
    !isset($_SESSION['usuario']) ||
    !in_array($_SESSION['tipo'], ['Administrador', 'SuperAdministrador'])
) {
    header('Location: index.php');
    exit();
}

$is_superadmin = $_SESSION['tipo'] === 'SuperAdministrador';
$flota_admin = $_SESSION['flota'] ?? '';

$usuarios_file = 'usuarios.json';
if (!file_exists($usuarios_file)) {
    die("El archivo de usuarios no existe.");
}

$usuarios = json_decode(file_get_contents($usuarios_file), true);
if (!is_array($usuarios)) $usuarios = [];

/* ================= OBTENER USUARIO ================= */

if (!isset($_GET['usuario'])) {
    die("Usuario no especificado.");
}

$usuario_id = $_GET['usuario'];

$usuario_encontrado = null;

foreach ($usuarios as $index => $usuario) {
    if ($usuario['usuario'] === $usuario_id) {
        $usuario_encontrado = [
            'index' => $index,
            'usuario' => $usuario
        ];
        break;
    }
}

if (!$usuario_encontrado) {
    die("Usuario no encontrado.");
}

/* ================= CONTROL DE PERMISOS ================= */

$usuario_objetivo = $usuario_encontrado['usuario'];

if ($_SESSION['tipo'] === 'Administrador') {

    $flota_usuario = $usuario_objetivo['flota'] ?? '';

    // No puede eliminar otra flota
    if ($flota_admin !== $flota_usuario) {
        die("No tienes permiso para eliminar este usuario.");
    }

    // No puede eliminar SuperAdministradores
    if ($usuario_objetivo['tipo'] === 'SuperAdministrador') {
        die("No tienes permiso para eliminar este usuario.");
    }

    // No puede eliminarse a sí mismo
    if ($usuario_objetivo['usuario'] === $_SESSION['usuario']) {
        die("No puedes eliminar tu propio usuario.");
    }
}

/* ================= CONFIRMACIÓN ================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['confirmar']) && $_POST['confirmar'] === 'sí') {

        unset($usuarios[$usuario_encontrado['index']]);
        $usuarios = array_values($usuarios);

        if (file_put_contents($usuarios_file, json_encode($usuarios, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
            header('Location: index.php?p=usuarios');
            exit();
        } else {
            $error = "No se pudo eliminar el usuario.";
        }

    } else {
        header('Location: index.php?p=usuarios');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Eliminar Usuario</title>

<link rel="icon" href="images/logo.webp">
<link rel="manifest" href="manifest.json">
<link rel="apple-touch-icon" sizes="180x180" href="images/logo.webp">
<link rel="stylesheet" href="style.css">
</head>
<body>

<h1>Eliminar Usuario</h1>

<?php if (isset($error)): ?>
<p style="color:red;"><strong><?= htmlspecialchars($error) ?></strong></p>
<?php endif; ?>

<p>
¿Estás seguro de que deseas eliminar al usuario 
<strong><?= htmlspecialchars($usuario_objetivo['usuario']) ?></strong>?
</p>

<form method="POST">
    <button type="submit" name="confirmar" value="sí">Sí, eliminar</button>
    <button type="submit" name="confirmar" value="no">Cancelar</button>
</form>

</body>
</html>
<?php break;

// ===================== eliminar_vehiculo =====================
case 'eliminar_vehiculo':
// Verificar si el usuario está logueado y es administrador
if (!isset($_SESSION['usuario']) || !in_array($_SESSION['tipo'], ['Administrador', 'SuperAdministrador'])) {
    header('Location: index.php');
    exit();
}

// Verificar que se reciba la matrícula
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("ID de vehículo no válido.");
}

$matricula = $_GET['id'];

// Cargar vehículos desde JSON
$vehiculos_file = 'vehiculos.json';
if (!file_exists($vehiculos_file)) {
    die("El archivo de vehículos no existe.");
}
$vehiculos = json_decode(file_get_contents($vehiculos_file), true);

// Buscar el vehículo
$vehiculo_encontrado = null;
foreach ($vehiculos as $index => $vehiculo) {
    if ($vehiculo['matricula'] === $matricula) {
        $vehiculo_encontrado = ['index' => $index, 'vehiculo' => $vehiculo];
        break;
    }
}

if (!$vehiculo_encontrado) {
    die("No se encontró el vehículo con matrícula: $matricula");
}

// Procesar confirmación de eliminación
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['confirmar']) && $_POST['confirmar'] === 'sí') {
        unset($vehiculos[$vehiculo_encontrado['index']]);
        $vehiculos = array_values($vehiculos); // Reindexar array

        if (file_put_contents($vehiculos_file, json_encode($vehiculos, JSON_PRETTY_PRINT))) {
            header('Location: index.php?p=vehiculos');
            exit();
        } else {
            $error = "No se pudo eliminar el vehículo. Verifique los permisos del archivo.";
        }
    } else {
        header('Location: index.php?p=vehiculos');
        exit();
    }
}

// Cargar versión y autor desde version.xk
$version_text = 'v.1.4';
$autor_text = 'B174M3 // XaeK';
if (file_exists('version.xk')) {
    $lines = file('version.xk', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $version_text = $lines[0] ?? $version_text;
    $autor_text = $lines[1] ?? $autor_text;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Eliminar Vehículo</title>

<link rel="icon" href="images/logo.webp">
<link rel="manifest" href="manifest.json">
<link rel="apple-touch-icon" sizes="180x180" href="images/logo.webp">
<link rel="stylesheet" href="style.css">

<style>
body { margin:15px; font-family:Arial,sans-serif; }

/* Botones */
button { padding:6px 12px; margin:2px; border-radius:4px; cursor:pointer; }

/* Mensajes de error */
.rojo_intenso { color:#cc0000; }

/* ===== MODO OSCURO AUTOMÁTICO ===== */
@media (prefers-color-scheme: dark) {
    body { background:#000; color:#ddd; }
    h1,h2,h3,h4,p,strong { color:#ddd; }
    input, select, button { background:#111; color:#fff; border:1px solid #555; }
    button:hover { background:#222; }
    .rojo_intenso { color:#f33; }
}
</style>
</head>
<body>

<br>

<h1>Eliminar Vehículo</h1>

<br>

<?php if (isset($error)): ?>
    <p class="rojo_intenso"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<p>¿Estás seguro de que deseas eliminar el vehículo <strong><?= htmlspecialchars($vehiculo_encontrado['vehiculo']['vehiculo']) ?></strong> con matrícula <strong><?= htmlspecialchars($vehiculo_encontrado['vehiculo']['matricula']) ?></strong>?</p>

<br>

<form method="POST">
    <button type="submit" name="confirmar" value="sí">Sí, eliminar</button>
    <button type="submit" name="confirmar" value="no">Cancelar</button>
</form>

<h4 class="small" style="margin-top:12px; text-align:left;"><?= htmlspecialchars($version_text) ?></h4>
<p class="small" style="text-align:left;"><?= htmlspecialchars($autor_text) ?></p>

</body>
</html>
<?php break;

// ===================== estaciones =====================
case 'estaciones':
// Administrador y SuperAdministrador
if (!isset($_SESSION['usuario']) || !in_array($_SESSION['tipo'], ['Administrador','SuperAdministrador'])) {
    header('Location: index.php?p=login');
    exit();
}

$is_admin = in_array($_SESSION['tipo'], ['Administrador','SuperAdministrador']);
$is_superadmin = $_SESSION['tipo'] === 'SuperAdministrador';
$usuario_flota = $_SESSION['flota'] ?? '';

// ===========================
// CARGAR USUARIOS Y FLOTA
// ===========================
$usuarios_file = 'usuarios.json';
$usuarios = [];
if (file_exists($usuarios_file)) {
    $usuarios = json_decode(file_get_contents($usuarios_file), true);
}

// Crear lista de flotas únicas
$flotas_disponibles = [];
foreach ($usuarios as $u) {
    if (!empty($u['flota'])) $flotas_disponibles[] = $u['flota'];
}
$flotas_disponibles = array_values(array_unique($flotas_disponibles));

// ===========================
// CARGAR ESTACIONES
// ===========================
$estaciones_file = 'estaciones.json';
if (!file_exists($estaciones_file)) {
    file_put_contents($estaciones_file, json_encode([
        ["nombre"=>"Tambre","flotas"=>["TRALUSA"]],
        ["nombre"=>"Sionlla","flotas"=>["TRALUSA"]],
        ["nombre"=>"Cacheiras","flotas"=>["TRALUSA"]]
    ], JSON_PRETTY_PRINT));
}

$estaciones = json_decode(file_get_contents($estaciones_file), true);
if (!is_array($estaciones)) $estaciones = [];

// ===========================
// AGREGAR NUEVA ESTACIÓN
// ===========================
if (isset($_POST['nueva_estacion']) && trim($_POST['nueva_estacion']) !== '') {
    $nueva = trim($_POST['nueva_estacion']);
    $asignadas = [];

    if ($is_superadmin) {
        $asignadas = $_POST['flotas'] ?? [];
        $asignadas = array_values(array_intersect($asignadas,$flotas_disponibles));
    } else {
        $asignadas = [$usuario_flota];
    }

    // Verificar si ya existe
    $existe = false;
    foreach ($estaciones as &$e) {
        if (strcasecmp($e['nombre'],$nueva)===0) {
            foreach ($asignadas as $f) {
                if (!in_array($f,$e['flotas'] ?? [])) $e['flotas'][]=$f;
            }
            $existe = true;
            break;
        }
    }
    if (!$existe) {
        $estaciones[] = ["nombre"=>$nueva,"flotas"=>$asignadas];
    }

    file_put_contents($estaciones_file,json_encode($estaciones,JSON_PRETTY_PRINT));
    $mensaje = "Estación '$nueva' agregada correctamente.";
}

// ===========================
// EDITAR ESTACIONES
// ===========================
if (isset($_POST['editar_estaciones']) && isset($_POST['estaciones']) && is_array($_POST['estaciones'])) {
    foreach ($_POST['estaciones'] as $i => $nombre) {
        $nombre = trim($nombre);
        if ($nombre === '') continue;

        $flotas_editar = [];
        if ($is_superadmin) {
            $flotas_editar = $_POST['flotas'][$i] ?? [];
            $flotas_editar = array_values(array_intersect($flotas_editar,$flotas_disponibles));
        } else {
            $flotas_editar = [$usuario_flota];
        }

        // Evitar duplicados en otras estaciones
        $duplicado = false;
        foreach ($estaciones as $j=>$e) {
            if ($j!=$i && strcasecmp($e['nombre'],$nombre)===0) {
                foreach ($flotas_editar as $f) {
                    if (!in_array($f,$e['flotas'] ?? [])) $e['flotas'][]=$f;
                }
                $duplicado = true;
                break;
            }
        }
        if (!$duplicado) {
            $estaciones[$i]['nombre'] = $nombre;
            $estaciones[$i]['flotas'] = $flotas_editar;
        }
    }
    file_put_contents($estaciones_file,json_encode($estaciones,JSON_PRETTY_PRINT));
    $mensaje = "Estaciones actualizadas correctamente.";
}

// ===========================
// "ELIMINAR"/DESASIGNAR ESTACIÓN
// ===========================
if (isset($_GET['eliminar'])) {
    $index = (int)$_GET['eliminar'];
    if (isset($estaciones[$index])) {
        if ($is_superadmin) {
            unset($estaciones[$index]); // SuperAdmin elimina totalmente
        } else {
            // Desasignar flota del administrador
            $estaciones[$index]['flotas'] = array_values(array_filter($estaciones[$index]['flotas'], function($f) use($usuario_flota){
                return strcasecmp($f,$usuario_flota)!==0;
            }));
        }
        $estaciones = array_values($estaciones); // Reindexar
        file_put_contents($estaciones_file,json_encode($estaciones,JSON_PRETTY_PRINT));
        $mensaje = "Estación actualizada correctamente.";
    } else {
        $error = "Estación no encontrada.";
    }
}

// ===========================
// FILTRAR ESTACIONES PARA ADMIN
// ===========================
if (!$is_superadmin && $usuario_flota!=='') {
    $estaciones = array_filter($estaciones,function($e) use($usuario_flota){
        return in_array($usuario_flota,$e['flotas'] ?? []);
    });
}

// ===========================
// VERSION
// ===========================
$version_file='version.xk';
$version_text=''; $autor_text='';
if(file_exists($version_file)){
    $lines = file($version_file,FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $version_text=$lines[0] ?? '';
    $autor_text=$lines[1] ?? '';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>ITVControl</title>
<link rel="icon" href="images/logo.webp">
<link rel="manifest" href="manifest.json">
<link rel="apple-touch-icon" sizes="180x180" href="images/logo.webp">
<link rel="stylesheet" href="style.css">

<style>
/* ===== BASE Y TEXTO ===== */
body {margin:15px;font-family:Arial,sans-serif;color:#000;background:#fff;}
h1,h2,h3,h4,h5,h6,p,strong,span,label {color:#000;}
input, select, textarea {padding:5px;margin:2px 0;border-radius:4px;background:#fff;color:#000;border:1px solid #ccc;}
input[type=submit]{cursor:pointer;}
.verde{color:#4CAF50;} .rojo_intenso{color:#f33;}
table{border-collapse:collapse;width:100%;}
th,td{border:1px solid #ccc;padding:8px;vertical-align:top;}
th{background:#eee;color:#000;}
.user-info{position:fixed;top:10px;right:15px;text-align:right;font-size:14px;background:rgba(255,255,255,0.6);padding:5px 10px;border-radius:8px;}
.user-info strong{display:block;}
.user-info small{color:#4a90e2;font-weight:bold;}
.menu a img{width:80px;vertical-align:middle;}

/* MODO OSCURO */
@media(prefers-color-scheme: dark){
    body{background:#000;color:#ddd;}
    h1,h2,h3,h4,h5,h6,p,strong,span,label{color:#ddd;}
    th{background:#1c75bc;color:#fff;}
    td{color:#ddd;border-color:#555;}
    input, select, textarea{background:#111;color:#fff;border:1px solid #555;}
    input[type=submit]{background:#222;color:#fff;border:1px solid #666;}
    .menu img:not([alt="Logo"]){filter: invert(1) hue-rotate(180deg);}
    h1 img{filter:none;}
    .user-info{background:rgba(0,0,0,0.5);}
    .user-info small{color:#3399ff;}
}
</style>
</head>
<body>

<div class="user-info">
<strong><?=htmlspecialchars($_SESSION['usuario'])?> | <?=htmlspecialchars($_SESSION['tipo'])?></strong>
<small><?= $is_superadmin ? "Todas las flotas" : ($usuario_flota ? strtoupper($usuario_flota) : "Sin flota asignada") ?></small>
<div id="fecha-hora"></div>
</div>

<h1><img src="images/logo.webp" width="30" style="vertical-align: middle;"> Gestionar Estaciones</h1>
  <hr style="border:1px solid #4a90e2; margin:10px 0 20px 0;">

    <div class="menu">
      <a title="index" href="index.php"><img src="images/index.webp" alt="index" width="80"></a>
      <a title="citas" href="index.php?p=citas"><img src="images/citas.webp" alt="citas" width="80"></a>
      <a title="vehiculos" href="index.php?p=vehiculos"><img src="images/vehiculos.webp" alt="vehiculos" width="80"></a>
     <?php if(in_array($_SESSION['tipo'], ['Administrador','SuperAdministrador'])): ?>
      <a title="estaciones" href="index.php?p=estaciones"><img src="images/estaciones.webp" alt="estaciones" width="80"></a>
      <a title="seguridad" href="index.php?p=ips_bloqueadas"><img src="images/secury.webp" alt="seguridad" width="80"></a>
      <a title="usuarios" href="index.php?p=usuarios"><img src="images/usuarios.webp" alt="usuarios" width="80"></a>
     <?php endif; ?>
      <a title="imprimir" href="index.php?p=imprimir"><img src="images/imprimir.webp" alt="imprimir" width="80"></a>
      <a title="logout" href="index.php?p=logout"><img src="images/logout.webp" alt="logout" width="80"></a>
    </div>
  
    <br><br><br>

<?php if(isset($mensaje)): ?><p class="verde"><?=htmlspecialchars($mensaje)?></p><?php endif; ?>
<?php if(isset($error)): ?><p class="rojo_intenso"><?=htmlspecialchars($error)?></p><?php endif; ?>

<h2>Agregar Nueva Estación</h2>
<form method="POST">
    <input type="text" name="nueva_estacion" placeholder="Nombre de la estación" required>
    <?php if($is_superadmin): ?>
        <br><label>Asignar a flotas:</label><br>
        <?php foreach($flotas_disponibles as $f): ?>
            <label style="margin-right:10px;">
                <input type="checkbox" name="flotas[]" value="<?=htmlspecialchars($f)?>">
                <?=htmlspecialchars($f)?>
            </label>
        <?php endforeach; ?>
    <?php endif; ?>
    <br><input type="submit" value="Agregar">
</form>
<br>

<h2>Editar Estaciones</h2>
<form method="POST">
<table>
<thead><tr><th>Nombre</th><th>Flotas</th><th>Acción</th></tr></thead>
<tbody>
<?php foreach($estaciones as $i=>$e): ?>
<tr>
    <td><input type="text" name="estaciones[<?=$i?>]" value="<?=htmlspecialchars($e['nombre'])?>" required></td>
    <td>
        <?php if($is_superadmin): ?>
            <?php foreach($flotas_disponibles as $f): ?>
                <label style="margin-right:10px;">
                    <input type="checkbox" name="flotas[<?=$i?>][]" value="<?=htmlspecialchars($f)?>"
                        <?=in_array($f,$e['flotas'] ?? [])?'checked':''?>>
                    <?=htmlspecialchars($f)?>
                </label>
            <?php endforeach; ?>
        <?php else: ?>
            <?=htmlspecialchars(implode(', ', $e['flotas'] ?? []))?>
        <?php endif; ?>
    </td>
    <td>
<button 
    style="padding:4px 8px; cursor:pointer; background:#cc0000 !important; color:#fff; border-radius:4px;"
    onclick="if(confirm('¿Seguro que quieres eliminar/desasignar esta estación?')){ 
        window.location.href='?eliminar=<?=$i?>'; 
    }">
    <?= $is_superadmin ? 'Eliminar' : 'Desasignar' ?>
</button>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<input type="submit" name="editar_estaciones" value="Guardar Cambios">
</form>

<h4 class="small" style="margin-top:12px;"><?=htmlspecialchars($version_text)?></h4>
<p class="small"><?=htmlspecialchars($autor_text)?></p>

<script>
function actualizarFechaHora(){
    const d=new Date();
    document.getElementById('fecha-hora').innerText=d.toLocaleDateString('es-ES')+' '+d.toLocaleTimeString('es-ES');
}
actualizarFechaHora();
setInterval(actualizarFechaHora,1000);
</script>
</body>
</html>
<?php break;

// ===================== imprimir =====================
case 'imprimir':
if (!isset($_SESSION['usuario'])) {
    header('Location: index.php'); exit();
}
$is_admin = isset($_SESSION['tipo']) && in_array($_SESSION['tipo'], ['Administrador','SuperAdministrador']);
$is_superadmin = $_SESSION['tipo'] === 'SuperAdministrador';

// =====================
// CARGAR VERSIÓN Y AUTOR
// =====================
$version_file='version.xk';
$version='v.1.0'; $autor='';
if(file_exists($version_file)){
    $lines=file($version_file, FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES);
    if(isset($lines[0])) $version=$lines[0];
    if(isset($lines[1])) $autor=$lines[1];
}
$flota_usuario = $_SESSION['flota'] ?? null;
$flota_texto = $is_superadmin ? "Todas las flotas" : ($flota_usuario ? strtoupper($flota_usuario) : "Sin flota asignada");
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>ITVControl</title>
<link rel="icon" href="images/logo.webp">
<link rel="manifest" href="manifest.json">
<link rel="apple-touch-icon" sizes="180x180" href="images/logo.webp">
<link rel="stylesheet" href="style.css">

<style>
body {margin:15px;font-family:Arial,sans-serif; color:#000;}

/* Cuadro de usuario igual que en index.php */
.user-info{
    position:fixed; top:10px; right:15px; text-align:right; font-size:14px;
    background:rgba(255,255,255,0.6); padding:5px 10px; border-radius:8px;
}
.user-info strong{display:block;}
.user-info small{color:#4a90e2;font-weight:bold;}

/* Título y línea azul */
h1 img{vertical-align:middle;}
hr.linea-azul{border:1px solid #4a90e2;margin:10px 0 20px 0;}

/* Botones */
button{padding:12px 20px;font-size:16px;cursor:pointer;border:none;border-radius:4px;}
button.azul{background:#3399ff;color:white;}
button.verde{background:#4CAF50;color:white;}
button.azul:hover{background:#1c75bc;}
button.verde:hover{background:#339933;}

/* Menú */
.menu img{vertical-align:middle;}

/* Modo oscuro */
@media (prefers-color-scheme: dark){
    body{background:#000;color:#ddd;}
    .user-info{background:rgba(0,0,0,0.5);}
    .user-info small{color:#3399ff;}
    h1,h2,h3,h4,p,strong{color:#ddd;}
    .menu img{filter: invert(1) hue-rotate(180deg);}
    h1 img{filter:none;}
}
</style>
</head>
<body>

<div class="user-info">
    <strong><?= htmlspecialchars($_SESSION['usuario']) ?> | <?= htmlspecialchars($_SESSION['tipo']) ?></strong>
    <small><?= htmlspecialchars($flota_texto) ?></small>
    <div id="fecha-hora"></div>
</div>

<h1><img src="images/logo.webp" width="30"> Impresora</h1>
<hr class="linea-azul">

    <div class="menu">
      <a title="index" href="index.php"><img src="images/index.webp" alt="index" width="80"></a>
      <a title="citas" href="index.php?p=citas"><img src="images/citas.webp" alt="citas" width="80"></a>
      <a title="vehiculos" href="index.php?p=vehiculos"><img src="images/vehiculos.webp" alt="vehiculos" width="80"></a>
     <?php if(in_array($_SESSION['tipo'], ['Administrador','SuperAdministrador'])): ?>
      <a title="estaciones" href="index.php?p=estaciones"><img src="images/estaciones.webp" alt="estaciones" width="80"></a>
      <a title="seguridad" href="index.php?p=ips_bloqueadas"><img src="images/secury.webp" alt="seguridad" width="80"></a>
      <a title="usuarios" href="index.php?p=usuarios"><img src="images/usuarios.webp" alt="usuarios" width="80"></a>
     <?php endif; ?>
      <a title="imprimir" href="index.php?p=imprimir"><img src="images/imprimir.webp" alt="imprimir" width="80"></a>
      <a title="logout" href="index.php?p=logout"><img src="images/logout.webp" alt="logout" width="80"></a>
    </div>
  
    <br><br><br>

<p><a href="index.php?p=imprimir_caducidades"><button class="azul">IMPRIMIR CADUCIDADES</button></a></p>
<p><a href="index.php?p=imprimir_citas"><button class="verde">IMPRIMIR CITAS</button></a></p>

<h4 class="small" style="text-align:left;"><?= htmlspecialchars($version) ?></h4>
<p class="small" style="text-align:left;"><?= htmlspecialchars($autor) ?></p>

<script>
function actualizarFechaHora(){
    const d=new Date();
    document.getElementById('fecha-hora').innerText =
        d.toLocaleDateString('es-ES')+' '+d.toLocaleTimeString('es-ES');
}
actualizarFechaHora();
setInterval(actualizarFechaHora,1000);
</script>

</body>
</html>
<?php break;

// ===================== imprimir_caducidades =====================
case 'imprimir_caducidades':
// Verificar login
if (!isset($_SESSION['usuario'])) {
    header('Location: index.php');
    exit();
}

// Roles
$is_admin = isset($_SESSION['tipo']) && in_array($_SESSION['tipo'], ['Administrador', 'SuperAdministrador']);
$is_superadmin = $_SESSION['tipo'] === 'SuperAdministrador';

// Flota
$flota_usuario = $_SESSION['flota'] ?? null;
$flota_texto = $is_superadmin ? "Todas las flotas" : ($flota_usuario ? strtoupper($flota_usuario) : "Sin flota asignada");

// =====================
// CARGA DE VEHÍCULOS
// =====================
$vehiculos_file = 'vehiculos.json';
$vehiculos = file_exists($vehiculos_file)
    ? json_decode(file_get_contents($vehiculos_file), true)
    : [];

// =====================
// FILTRAR POR FLOTA
// =====================
if (!$is_superadmin && $flota_usuario) {
    $vehiculos = array_filter($vehiculos, function($v) use ($flota_usuario) {
        return isset($v['flota']) && strtoupper(trim($v['flota'])) === strtoupper(trim($flota_usuario));
    });
}

// =====================
// SELECCIÓN DE MES / AÑO
// =====================
$mes_actual = date('m');
$anio_actual = date('Y');
$mes_seleccionado = $_GET['mes'] ?? $mes_actual;
$anio_seleccionado = $_GET['anio'] ?? $anio_actual;

// =====================
// MESES EN ESPAÑOL
// =====================
$meses_es = [
    1=>'Enero', 2=>'Febrero', 3=>'Marzo', 4=>'Abril', 5=>'Mayo', 6=>'Junio',
    7=>'Julio', 8=>'Agosto', 9=>'Septiembre', 10=>'Octubre', 11=>'Noviembre', 12=>'Diciembre'
];

// =====================
// FUNCIONES
// =====================
function formatear_fecha($fecha) {
    $f = DateTime::createFromFormat('Y-m-d', $fecha);
    return $f ? $f->format('d/m/Y') : $fecha;
}

// =====================
// FILTRAR VEHÍCULOS POR MES/AÑO
// =====================
$vehiculos_filtrados = array_filter($vehiculos, function($v) use ($mes_seleccionado, $anio_seleccionado) {
    $fecha = DateTime::createFromFormat('Y-m-d', $v['caducidad_itv']);
    return $fecha && $fecha->format('m') == str_pad($mes_seleccionado,2,'0',STR_PAD_LEFT)
                 && $fecha->format('Y') == $anio_seleccionado;
});

// =====================
// ORDENAR POR FECHA
// =====================
usort($vehiculos_filtrados, function($a, $b) {
    $fechaA = strtotime($a['caducidad_itv']);
    $fechaB = strtotime($b['caducidad_itv']);
    return $fechaA <=> $fechaB;
});

// =====================
// HORIZONTE SEGURO
// =====================
$frecuencia_meses = 24;
$tipos_detectados = [];

foreach ($vehiculos as $v) {
    $tipo = strtolower($v['tipo'] ?? '');

    if (str_contains($tipo, 'autobus') ||
        str_contains($tipo, 'microbus') ||
        str_contains($tipo, 'mercador') ||
        str_contains($tipo, 'tractora') ||
        str_contains($tipo, 'remolque')) {
        $frecuencia_meses = min($frecuencia_meses, 6);
        $tipos_detectados[] = 'Semestral';
    } elseif (str_contains($tipo, 'turismo') ||
              str_contains($tipo, 'taxi') ||
              str_contains($tipo, 'agricola') ||
              str_contains($tipo, 'obras')) {
        $frecuencia_meses = min($frecuencia_meses, 12);
        $tipos_detectados[] = 'Anual';
    } elseif (str_contains($tipo, 'moto') ||
              str_contains($tipo, 'quad') ||
              str_contains($tipo, 'ciclomotor')) {
        $frecuencia_meses = min($frecuencia_meses, 24);
        $tipos_detectados[] = 'Bienal';
    }
}

$fecha_limite_obj = (new DateTime())->modify("+$frecuencia_meses months");
$fecha_limite_obj->modify('-1 month');

$mes_maximo = (int)$fecha_limite_obj->format('m');
$anio_maximo = $fecha_limite_obj->format('Y');

$fecha_impresion = date('d/m/Y H:i');

// =====================
// INFO DE IMPRESIÓN Y VERSION
// =====================
$fecha_impresion = date('d/m/Y H:i');

$version_file = 'version.xk';
$version = 'v.1.0';
$autor = '';
if (file_exists($version_file)) {
    $lines = file($version_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (isset($lines[0])) $version = $lines[0];
    if (isset($lines[1])) $autor = $lines[1];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>ITVControl</title>
<link rel="icon" href="images/logo.webp">
<link rel="manifest" href="manifest.json">
<link rel="apple-touch-icon" sizes="180x180" href="images/logo.webp">
<link rel="stylesheet" href="style.css">

<style>
body{margin:15px;font-family:Arial,sans-serif;color:#000;}

/* Cuadro de usuario */
.user-info{
    position:fixed;top:10px;right:15px;text-align:right;font-size:14px;
    background:rgba(255,255,255,0.6);padding:5px 10px;border-radius:8px;
}
.user-info strong{display:block;}
.user-info small{color:#4a90e2;font-weight:bold;}

/* Título y línea azul */
h1 img{vertical-align:middle;}
hr.linea-azul{border:1px solid #4a90e2;margin:10px 0 20px 0;}

/* Tabla */
table{border-collapse:collapse;width:100%;margin-top:10px;}
th,td{border:1px solid #ccc;padding:2px 4px;font-size:15px;text-align:left;line-height:1.05;}
th{background:#eee;}

/* Modo oscuro */
@media (prefers-color-scheme: dark){
    body{background:#000;color:#ddd;}
    h1,h2,h3,h4,p,strong,td,th{color:#ddd;}
    th{background:#222;color:#fff;}
    .menu img{filter:invert(1) hue-rotate(180deg);}
    h1 img{filter:none;}
    .user-info{background:rgba(0,0,0,0.5);}
    .user-info small{color:#3399ff;}
}

/* Impresión */
@media print{
    @page{size:A4 portrait;margin:12mm;}
    body{font-size:15px;}
    .menu,form,button,.small,.no-imprimir,.user-info{display:none!important;}
    h1{margin:0 0 6px 0;font-size:16px;color:#000!important;}
    tbody tr td{font-weight:bold;}
    table,tr,td,th{page-break-inside:avoid!important;}
}
</style>
</head>
<body>

<div class="user-info">
    <strong><?=htmlspecialchars($_SESSION['usuario'])?> | <?=htmlspecialchars($_SESSION['tipo'])?></strong>
    <small><?=htmlspecialchars($flota_texto)?></small>
    <div id="fecha-hora"></div>
</div>

<h1><img src="images/logo.webp" width="30" style="vertical-align:middle;"> Caducidades ITV</h1>
<hr class="linea-azul">
 
    <div class="menu">
      <a title="index" href="index.php"><img src="images/index.webp" alt="index" width="80"></a>
      <a title="citas" href="index.php?p=citas"><img src="images/citas.webp" alt="citas" width="80"></a>
      <a title="vehiculos" href="index.php?p=vehiculos"><img src="images/vehiculos.webp" alt="vehiculos" width="80"></a>
     <?php if(in_array($_SESSION['tipo'], ['Administrador','SuperAdministrador'])): ?>
      <a title="estaciones" href="index.php?p=estaciones"><img src="images/estaciones.webp" alt="estaciones" width="80"></a>
      <a title="seguridad" href="index.php?p=ips_bloqueadas"><img src="images/secury.webp" alt="seguridad" width="80"></a>
      <a title="usuarios" href="index.php?p=usuarios"><img src="images/usuarios.webp" alt="usuarios" width="80"></a>
     <?php endif; ?>
      <a title="imprimir" href="index.php?p=imprimir"><img src="images/imprimir.webp" alt="imprimir" width="80"></a>
      <a title="logout" href="index.php?p=logout"><img src="images/logout.webp" alt="logout" width="80"></a>
    </div>
  
    <br><br><br>

<form method="GET" action="index.php?p=imprimir_caducidades" style="margin:15px 0;">
<input type="hidden" name="p" value="imprimir_caducidades">
    <label>Mes:
        <select name="mes">
            <?php for($m=1;$m<=12;$m++): ?>
                <option value="<?=$m?>" <?=$m==$mes_seleccionado?'selected':''?>><?=$meses_es[$m]?></option>
            <?php endfor; ?>
        </select>
    </label>
    <label>Año:
        <input type="number" name="anio" value="<?=$anio_seleccionado?>" min="2000" max="2100">
    </label>
    <button type="submit">Mostrar</button>
    <button type="button" onclick="window.print()">Imprimir</button>
</form>

<div class="print-header">
<?= $meses_es[(int)$mes_seleccionado] ?> <?= $anio_seleccionado ?><br>Impreso el <?= $fecha_impresion ?>
</div>

<?php if (!empty($tipos_detectados)): ?>
<div class="aviso-horizonte no-imprimir" style="color:orange;">
⚠️ Para que aparezcan todos los vehículos es seguro imprimir
<strong style="color:red;"><?= $meses_es[$mes_maximo] ?> <?= $anio_maximo ?></strong>.
</div>
<?php endif; ?>

<table>
<thead>
<tr>
    <th>Vehículo</th>
    <th>Matrícula</th>
    <th>Tipo</th>
    <th>Estado</th>
    <th>Caducidad ITV</th>
</tr>
</thead>
<tbody>
<?php if(empty($vehiculos_filtrados)): ?>
<tr><td colspan="5">No hay vehículos que caduquen en el mes seleccionado.</td></tr>
<?php else: ?>
<?php foreach($vehiculos_filtrados as $v): ?>
<tr>
    <td><?=htmlspecialchars($v['vehiculo'])?></td>
    <td><?=htmlspecialchars($v['matricula'])?></td>
    <td><?=htmlspecialchars($v['tipo'] ?? '')?></td>
    <td><?=htmlspecialchars($v['estado'])?></td>
    <td><?=formatear_fecha($v['caducidad_itv'])?></td>
</tr>
<?php endforeach; ?>
<?php endif; ?>
</tbody>
</table>

<h4 class="small no-imprimir" style="margin-top:12px;"><?=htmlspecialchars($version)?></h4>
<p class="small no-imprimir"><?=htmlspecialchars($autor)?></p>

<script>
function actualizarFechaHora(){
 const d=new Date();
 document.getElementById('fecha-hora').innerText=d.toLocaleDateString('es-ES')+' '+d.toLocaleTimeString('es-ES');
}
actualizarFechaHora();
setInterval(actualizarFechaHora,1000);
</script>

</body>
</html>
<?php break;

// ===================== imprimir_citas =====================
case 'imprimir_citas':
// Verificar login
if (!isset($_SESSION['usuario'])) {
    header('Location: index.php');
    exit();
}

$is_admin = isset($_SESSION['tipo']) && in_array($_SESSION['tipo'], ['Administrador', 'SuperAdministrador']);
$is_superadmin = isset($_SESSION['tipo']) && $_SESSION['tipo'] === 'SuperAdministrador';

// Flota igual que en index.php
$flota_usuario = $_SESSION['flota'] ?? null;
$flota_texto = $is_superadmin ? "Todas las flotas" : ($flota_usuario ? strtoupper($flota_usuario) : "Sin flota asignada");

// =====================
// CARGA DE VEHÍCULOS
// =====================
$vehiculos_file = 'vehiculos.json';
$vehiculos = file_exists($vehiculos_file)
    ? json_decode(file_get_contents($vehiculos_file), true)
    : [];

// =====================
// CARGA DE CITAS
// =====================
$citas_file = 'citas.json';
$citas = file_exists($citas_file)
    ? json_decode(file_get_contents($citas_file), true)
    : [];

// =====================
// FILTRAR CITAS POR FLOTA (NO LOS VEHÍCULOS)
// =====================
if (!$is_superadmin && $flota_usuario) {
    $citas = array_filter($citas, function($c) use ($flota_usuario) {
        return isset($c['flota']) && strtoupper(trim($c['flota'])) === strtoupper(trim($flota_usuario));
    });
}

// =====================
// MES / AÑO
// =====================
$mes_actual  = date('m');
$anio_actual = date('Y');
$mes  = $_GET['mes']  ?? $mes_actual;
$anio = $_GET['anio'] ?? $anio_actual;

// =====================
// MESES EN ESPAÑOL
// =====================
$meses_es = [
    1=>'Enero', 2=>'Febrero', 3=>'Marzo', 4=>'Abril', 5=>'Mayo', 6=>'Junio',
    7=>'Julio', 8=>'Agosto', 9=>'Septiembre', 10=>'Octubre', 11=>'Noviembre', 12=>'Diciembre'
];

// =====================
// FECHA LÍMITE (ahora - 1 hora)
// =====================
$ahora_menos_una_hora = new DateTime();
$ahora_menos_una_hora->modify('-1 hour');

// =====================
// FILTRAR CITAS
// =====================
$citas_filtradas = array_filter($citas, function ($cita) use ($mes, $anio, $ahora_menos_una_hora) {
    if (empty($cita['fecha_cita']) || empty($cita['hora_cita'])) return false;
    $fecha_hora_cita = DateTime::createFromFormat('Y-m-d H:i', $cita['fecha_cita'] . ' ' . $cita['hora_cita']);
    if (!$fecha_hora_cita) return false;
    if ($fecha_hora_cita < $ahora_menos_una_hora) return false;
    return $fecha_hora_cita->format('m') == $mes && $fecha_hora_cita->format('Y') == $anio;
});

// =====================
// ORDENAR CITAS
// =====================
usort($citas_filtradas, function ($a, $b) {
    $fa = strtotime($a['fecha_cita'] . ' ' . ($a['hora_cita'] ?? '00:00'));
    $fb = strtotime($b['fecha_cita'] . ' ' . ($b['hora_cita'] ?? '00:00'));
    return $fa <=> $fb;
});

// =====================
// FUNCIONES
// =====================
function formatear_fecha($fecha) {
    $f = DateTime::createFromFormat('Y-m-d', $fecha);
    return $f ? $f->format('d/m/Y') : $fecha;
}
function mostrar_vehiculo_completo($matricula, $vehiculos) {
    foreach ($vehiculos as $v) {
        if ($v['matricula'] === $matricula) return $v['vehiculo'] . ' - ' . $v['matricula'];
    }
    return '-';
}
function obtener_caducidad_itv($matricula, $vehiculos) {
    foreach ($vehiculos as $v) {
        if ($v['matricula'] === $matricula) return formatear_fecha($v['caducidad_itv']);
    }
    return '-';
}
function obtener_dia_semana_abrev($fecha) {
    $dias = [1=>'lu',2=>'ma',3=>'mi',4=>'ju',5=>'vi',6=>'sa',7=>'do'];
    $f = DateTime::createFromFormat('Y-m-d', $fecha);
    return $f ? $dias[(int)$f->format('N')] : '';
}

// =====================
// TEXTOS
// =====================
$meses_txt = [
    '01'=>'Enero','02'=>'Febrero','03'=>'Marzo','04'=>'Abril',
    '05'=>'Mayo','06'=>'Junio','07'=>'Julio','08'=>'Agosto',
    '09'=>'Septiembre','10'=>'Octubre','11'=>'Noviembre','12'=>'Diciembre'
];
$fecha_impresion = date('d/m/Y H:i');

// =====================
// VERSION
// =====================
$version_file = 'version.xk';
$version = 'v.1.0'; $autor = '';
if(file_exists($version_file)){
    $lines = file($version_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if(isset($lines[0])) $version = $lines[0];
    if(isset($lines[1])) $autor = $lines[1];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>ITVControl</title>
<link rel="icon" href="images/logo.webp">
<link rel="manifest" href="manifest.json">
<link rel="apple-touch-icon" sizes="180x180" href="images/logo.webp">
<link rel="stylesheet" href="style.css">

<style>
body{margin:15px;font-family:Arial,sans-serif;color:#000;}

/* Cuadro de usuario */
.user-info{
    position:fixed;top:10px;right:15px;text-align:right;font-size:14px;
    background:rgba(255,255,255,0.6);padding:5px 10px;border-radius:8px;
}
.user-info strong{display:block;}
.user-info small{color:#4a90e2;font-weight:bold;}

/* Título y línea azul */
h1 img{vertical-align:middle;}
hr.linea-azul{border:1px solid #4a90e2;margin:10px 0 20px 0;}

/* Tabla */
table{border-collapse:collapse;width:100%;}
th,td{border:1px solid #ccc;padding:2px 4px;font-size:15px;text-align:left;line-height:1.05;}
th{background:#eee;}
.fila-roja{border-left:5px solid #c00000;}
.fila-azul{border-left:5px solid #004aad;}
.fila-amarilla{border-left:5px solid #c9a600;}
.fila-naranja{border-left:5px solid #c97f00;}
.estado{font-weight:bold;text-transform:uppercase;font-size:10px;}
.matricula{font-size:9px;}
.dia-semana{font-weight:bold;}
.dia-vi,.dia-sa,.dia-do{color:#c00000!important;}

/* Modo oscuro */
@media (prefers-color-scheme: dark){
    body{background:#000;color:#ddd;}
    h1,h2,h3,h4,p,strong,td,th{color:#ddd;}
    th{background:#222;color:#fff;}
    .menu img{filter:invert(1) hue-rotate(180deg);}
    h1 img{filter:none;}
    .user-info{background:rgba(0,0,0,0.5);}
    .user-info small{color:#3399ff;}
    .dia-vi,.dia-sa,.dia-do{color:#ff4d4d!important;}
}

/* Impresión */
@media print{
    @page{size:A4 portrait;margin:12mm;}
    body{font-size:15px;}
    .menu,form,button,.small,.no-imprimir,.user-info{display:none!important;}
    h1{margin:0 0 6px 0;font-size:16px;}
    tbody tr td{font-weight:bold;}
    table,tr,td,th{page-break-inside:avoid!important;}
}
</style>
</head>
<body>

<div class="user-info">
    <strong><?=htmlspecialchars($_SESSION['usuario'])?> | <?=htmlspecialchars($_SESSION['tipo'])?></strong>
    <small><?=htmlspecialchars($flota_texto)?></small>
    <div id="fecha-hora"></div>
</div>

<h1><img src="images/logo.webp" width="30" style="vertical-align:middle;"> Citas ITV</h1>
<hr class="linea-azul">

    <div class="menu">
      <a title="index" href="index.php"><img src="images/index.webp" alt="index" width="80"></a>
      <a title="citas" href="index.php?p=citas"><img src="images/citas.webp" alt="citas" width="80"></a>
      <a title="vehiculos" href="index.php?p=vehiculos"><img src="images/vehiculos.webp" alt="vehiculos" width="80"></a>
     <?php if(in_array($_SESSION['tipo'], ['Administrador','SuperAdministrador'])): ?>
      <a title="estaciones" href="index.php?p=estaciones"><img src="images/estaciones.webp" alt="estaciones" width="80"></a>
      <a title="seguridad" href="index.php?p=ips_bloqueadas"><img src="images/secury.webp" alt="seguridad" width="80"></a>
      <a title="usuarios" href="index.php?p=usuarios"><img src="images/usuarios.webp" alt="usuarios" width="80"></a>
     <?php endif; ?>
      <a title="imprimir" href="index.php?p=imprimir"><img src="images/imprimir.webp" alt="imprimir" width="80"></a>
      <a title="logout" href="index.php?p=logout"><img src="images/logout.webp" alt="logout" width="80"></a>
    </div>
  
    <br><br><br>

<form method="GET" action="index.php?p=imprimir_citas" style="margin:15px 0;">
<input type="hidden" name="p" value="imprimir_citas">
    <select name="mes">
        <?php foreach($meses_txt as $num=>$nombre): ?>
        <option value="<?=$num?>" <?=$mes==$num?'selected':''?>><?=$nombre?></option>
        <?php endforeach; ?>
    </select>
    <input type="number" name="anio" value="<?=$anio?>" style="width:80px;">
    <button type="submit">Mostrar</button>
    <button type="button" onclick="window.print()">Imprimir</button>
</form>

<div class="print-header">
    <?= $meses_txt[$mes] ?> <?= $anio ?> — Impreso el <?= $fecha_impresion ?>
</div>

<table>
<thead>
<tr>
    <th>Día</th><th>Vehículo</th><th>Tipo</th><th>Fecha</th><th>Hora</th>
    <th>Estación</th><th>Caducidad</th><th>Estado</th>
</tr>
</thead>
<tbody>
<?php if(empty($citas_filtradas)): ?>
<tr><td colspan="8">No hay citas para este periodo</td></tr>
<?php else: ?>
<?php foreach($citas_filtradas as $cita):
$clase_fila='';$estado='NORMAL';
$tipo=strtolower($cita['tipo_cita']??'');$vehiculo=$cita['vehiculo']??'';
$fecha_cita=DateTime::createFromFormat('Y-m-d',$cita['fecha_cita']);
$caducidad=null;
foreach($vehiculos as $v){if($v['matricula']===$vehiculo){$caducidad=DateTime::createFromFormat('Y-m-d',$v['caducidad_itv']);break;}}
if($tipo==='primera'&&$fecha_cita&&$caducidad&&$fecha_cita>$caducidad){$clase_fila='fila-roja';$estado='PRIMERA INSPECCIÓN FUERA DE PLAZO';}
elseif($tipo==='segunda'&&empty($vehiculo)){$clase_fila='fila-naranja';$estado='SEGUNDA INSPECCIÓN SIN VEHÍCULO';}
elseif($tipo==='segunda'){$clase_fila='fila-azul';$estado='SEGUNDA INSPECCIÓN';}
elseif($tipo==='primera'&&empty($vehiculo)){$clase_fila='fila-amarilla';$estado='PRIMERA INSPECCIÓN SIN VEHÍCULO';}

$dia_abrev=obtener_dia_semana_abrev($cita['fecha_cita']);$dia_class='dia-'.$dia_abrev;
?>
<tr class="<?=$clase_fila?>">
<td class="dia-semana <?=$dia_class?>"><?=$dia_abrev?></td>
<td>
<?php
$vehiculo_completo=mostrar_vehiculo_completo($vehiculo,$vehiculos);
if($vehiculo_completo!=='-'){
$partes=explode(' - ',$vehiculo_completo,2);
echo htmlspecialchars($partes[0]);
if(isset($partes[1]))echo' - <span class="matricula">'.htmlspecialchars($partes[1]).'</span>';
}else echo'-';
?>
</td>
<td><?=htmlspecialchars($cita['tipo_cita']??'-')?></td>
<td><?=formatear_fecha($cita['fecha_cita'])?></td>
<td><?=htmlspecialchars($cita['hora_cita'])?></td>
<td><?=htmlspecialchars($cita['estacion_cita']??'-')?></td>
<td><?=obtener_caducidad_itv($vehiculo,$vehiculos)?></td>
<td class="estado"><?=$estado?></td>
</tr>
<?php endforeach;endif;?>
</tbody>
</table>
<br>
<div class="print-footer">
    <p><strong>Aviso importante:</strong><br>
    Le informamos que, en caso de retraso por parte del usuario, superados los <strong>15 minutos de margen</strong> sobre la hora concertada, esta será anulada a favor de otros usuarios del servicio. Por motivos organizativos, el servicio de inspección empezará en el intervalo de los quince minutos siguientes a la hora concertada.
    </p>
</div>

<h4 class="small no-imprimir" style="margin-top:12px;"><?=htmlspecialchars($version)?></h4>
<p class="small no-imprimir"><?=htmlspecialchars($autor)?></p>

<script>
function actualizarFechaHora(){
 const d=new Date();
 document.getElementById('fecha-hora').innerText=d.toLocaleDateString('es-ES')+' '+d.toLocaleTimeString('es-ES');
}
actualizarFechaHora();
setInterval(actualizarFechaHora,1000);
</script>
</body>
</html>
<?php break;

// ===================== index =====================
case 'index':
 



// Redirigir al login si no hay usuario logueado
if (!isset($_SESSION['usuario'])) {
    header('Location: index.php?p=login');
    exit();
}

$is_admin = isset($_SESSION['tipo']) && in_array($_SESSION['tipo'], ['Administrador', 'SuperAdministrador']);
$is_superadmin = isset($_SESSION['tipo']) && $_SESSION['tipo'] === 'SuperAdministrador';

// Obtener flota del usuario
$flota_usuario = $_SESSION['flota'] ?? null;
$flota_texto = $is_superadmin ? "Todas las flotas" : ($flota_usuario ? strtoupper($flota_usuario) : "Sin flota asignada");

// Cargar vehículos
$vehiculos_file = 'vehiculos.json';
if (!file_exists($vehiculos_file)) die("El archivo de vehículos no existe.");
$vehiculos = json_decode(file_get_contents($vehiculos_file), true);

// Cargar citas
$citas_file = 'citas.json';
if (!file_exists($citas_file)) die("El archivo de citas no existe.");
$citas = json_decode(file_get_contents($citas_file), true);

// =====================
// FUNCIONES
// =====================
function calcular_dias_restantes($caducidad_itv) {
    $fecha_actual = new DateTime('today');
    $fecha_caducidad = new DateTime($caducidad_itv);
    $fecha_caducidad->setTime(0, 0, 0);
    $intervalo = $fecha_actual->diff($fecha_caducidad);
    return (int)$intervalo->format('%r%a');
}

function obtener_citas_vehiculo($matricula_vehiculo, $citas) {
    $fecha_actual = new DateTime();
    $resultado = [];
    foreach ($citas as $cita) {
        if ($cita['vehiculo'] === $matricula_vehiculo) {
            $dt = DateTime::createFromFormat('Y-m-d H:i', $cita['fecha_cita'].' '.$cita['hora_cita']);
            if ($dt && $dt >= $fecha_actual) {
                $cita['timestamp'] = $dt->getTimestamp();
                $resultado[] = $cita;
            }
        }
    }
    usort($resultado, fn($a, $b) => $a['timestamp'] <=> $b['timestamp']);
    return $resultado;
}

function formatear_fecha($fecha) {
    $d = DateTime::createFromFormat('Y-m-d', $fecha);
    return $d ? $d->format('d/m/Y') : $fecha;
}

function obtener_color_y_texto($vehiculo) {
    $estado = $vehiculo['estado'];
    $dias = calcular_dias_restantes($vehiculo['caducidad_itv']);
    if ($estado === 'ITV RECHAZADA') return ['color'=>'rojo_intenso','texto_dias'=>'ITV RECHAZADA'];
    if ($dias < 0) return ['color'=>'rojo_intenso','texto_dias'=>'ITV CADUCADA'];
    if ($dias == 0) return ['color'=>'rojo_intenso','texto_dias'=>'CADUCA HOY'];
if ($dias == 1) return ['color'=>'rojo_intenso','texto_dias'=>'1 día'];
    if ($dias < 10) return ['color'=>'naranja_intenso','texto_dias'=>$dias.' días'];
    if ($dias <= 20) return ['color'=>'naranja_suave','texto_dias'=>$dias.' días'];
    if ($dias <= 35) return ['color'=>'azul','texto_dias'=>$dias.' días'];
    return ['color'=>'verde','texto_dias'=>$dias.' días'];
}

// =====================
// FILTRAR VEHÍCULOS POR FLOTA (si no es SuperAdministrador)
// =====================
if (!$is_superadmin && $flota_usuario) {
    $vehiculos = array_filter($vehiculos, function($v) use ($flota_usuario) {
        return isset($v['flota']) && strtoupper(trim($v['flota'])) === strtoupper(trim($flota_usuario));
    });
}

// =====================
// PRÓXIMA ITV GLOBAL
// =====================
$proxima_itv = null;
$ts_min = null;
$ahora = new DateTime();

foreach ($citas as $cita) {
    if ($cita['tipo_cita'] !== 'Primera') continue;
    if (!$is_superadmin && isset($cita['flota']) && strtoupper($cita['flota']) !== strtoupper($flota_usuario)) continue;

    $dt = DateTime::createFromFormat('Y-m-d H:i', $cita['fecha_cita'].' '.$cita['hora_cita']);
    if ($dt && $dt >= $ahora) {
        $ts = $dt->getTimestamp();
        if ($ts_min === null || $ts < $ts_min) {
            $ts_min = $ts;
            $proxima_itv = $cita;
        }
    }
}

// Filtrar y ordenar vehículos
$vehiculos_filtrados = array_filter($vehiculos, fn($v) => in_array($v['estado'], ['ACTIVO','ITV RECHAZADA']));
usort($vehiculos_filtrados, function($a, $b) {
    if ($a['estado'] === 'ITV RECHAZADA' && $b['estado'] !== 'ITV RECHAZADA') return -1;
    if ($a['estado'] !== 'ITV RECHAZADA' && $b['estado'] === 'ITV RECHAZADA') return 1;
    return calcular_dias_restantes($a['caducidad_itv']) <=> calcular_dias_restantes($b['caducidad_itv']);
});

// =====================
// VERSION LOCAL Y AUTOR
// =====================
$version = 'v.1.0';
$autor = 'Desconocido';
if (file_exists('version.xk')) {
    $lineas = file('version.xk', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (isset($lineas[0])) $version = trim($lineas[0]);
    if (isset($lineas[1])) $autor = trim($lineas[1]);
}

// =====================
// COMPROBAR VERSION REMOTA DESDE GITHUB (sin cache)
// =====================
$ultima_version_fallback = 'No se pudo comprobar actualización';
$ultima_version = null;
$github_url = 'https://raw.githubusercontent.com/X43K/ITVControl/main/version.xk';

if(function_exists('curl_version')){
    $ch = curl_init();
    curl_setopt($ch,CURLOPT_URL,$github_url.'?t='.time());
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
    curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,5);
    curl_setopt($ch,CURLOPT_TIMEOUT,5);
    curl_setopt($ch,CURLOPT_HTTPHEADER,["User-Agent: ITVControl","Cache-Control:no-cache","Pragma:no-cache"]);
    $res = curl_exec($ch);
    curl_close($ch);
    if($res && preg_match('/v\.\d+(\.\d+)*/i',$res,$m)) $ultima_version = trim($m[0]);
} else {
    $ctx = stream_context_create([
        'http'=>[
            'timeout'=>5,
            'header'=>"User-Agent: ITVControl\r\nCache-Control:no-cache\r\nPragma:no-cache\r\n"
        ]
    ]);
    $res = @file_get_contents($github_url.'?t='.time(), false, $ctx);
    if($res && preg_match('/v\.\d+(\.\d+)*/i',$res,$m)) $ultima_version = trim($m[0]);
}

// =====================
// EXTRAER NUMEROS DE VERSION
// =====================
$version_num = null;
$ultima_version_num = null;
if(preg_match('/\b(\d+(\.\d+)+)\b/',$version,$m)) $version_num = $m[1];
if($ultima_version && preg_match('/\b(\d+(\.\d+)+)\b/',$ultima_version,$mr)) $ultima_version_num = $mr[1];

// =====================
// DETERMINAR SI MOSTRAR AVISO
// =====================
$mostrar_aviso = false;
if($version_num && $ultima_version_num && version_compare($ultima_version_num,$version_num,'>')) $mostrar_aviso=true;
elseif(!$ultima_version_num) $mostrar_aviso=true;
?>
<?php if($mostrar_aviso): ?>
<div style="background:#ffcc00;border:2px solid #cc9900;padding:10px 15px;margin-bottom:5px;border-radius:8px;">
    <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
        <span style="font-weight:bold;font-size:25px;color:red;">¡Existe una actualización disponible!</span>
        <?php if($is_admin || $is_superadmin): ?>
            <form action="index.php?p=actualizar" method="post" style="margin:0;">
                <input type="hidden" name="version_actual" value="<?= htmlspecialchars($version_num ?? $version) ?>">
                <input type="hidden" name="version_nueva" value="<?= htmlspecialchars($ultima_version_num ?? $ultima_version_fallback) ?>">
                <button type="submit" style="background:#4a90e2;color:white;border:none;padding:8px 16px;border-radius:6px;font-weight:bold;cursor:pointer;font-size:25px;">
                    Actualizar ahora
                </button>
            </form>
        <?php endif; ?>
    </div>
    <div style="font-weight:bold;font-size:12px;color:black;margin-top:4px;">
        Versión actual: <?= htmlspecialchars($version_num ?? $version) ?><br>
        Versión disponible: <?= htmlspecialchars($ultima_version_num ?? $ultima_version ?? $ultima_version_fallback) ?>
    </div>
</div>
<?php endif; ?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>ITVControl</title>
<link rel="icon" href="images/logo.webp">
<link rel="manifest" href="manifest.json">
<link rel="apple-touch-icon" sizes="180x180" href="images/logo.webp">
<link rel="stylesheet" href="style.css">

<meta http-equiv="refresh" content="60">

<style>
/* ===== COLORES DE ESTADO ===== */
.negro{background:black;color:grey}
.rojo_intenso{background:#cc0000;color:white}
.naranja_intenso{background:#ff6600;color:white}
.naranja_suave{background:#ffae0d;color:white}
.azul{background:#3399ff;color:white}
.verde{background:#4CAF50;color:white}

/* ===== TABLAS ===== */
table{border-collapse:collapse;width:100%; table-layout:auto;}
th,td{border:1px solid #ccc;padding:8px;vertical-align:top}
th{background:#eee}

/* ===== ANCHOS DE COLUMNAS (tus originales) ===== */
th:nth-child(1), td:nth-child(1){white-space:normal;word-break:keep-all;width:1%;max-width:100%;}
th:nth-child(2), td:nth-child(2){white-space:nowrap;}
th:nth-child(3), td:nth-child(3){white-space:normal;word-break:keep-all;}
th:nth-child(4), td:nth-child(4){white-space:normal;word-break:keep-all;width:auto;min-width:80px;}
th:nth-child(5), td:nth-child(5){white-space:nowrap;}
th:nth-child(6), td:nth-child(6){white-space:normal;word-break:keep-all;width:auto;min-width:80px;}
th:nth-child(7), td:nth-child(7){white-space:nowrap;}
td.dias-numero{white-space:nowrap;}
ul{margin:0;padding-left:18px}

/* ===== INFO DE USUARIO ===== */
.user-info{
    position:fixed;
    top:10px;
    right:15px;
    text-align:right;
    font-size:14px;
    background:rgba(255,255,255,0.6);
    padding:5px 10px;
    border-radius:8px;
}
.user-info strong{display:block;}
.user-info small{color:#4a90e2;font-weight:bold;}

/* ===== MODO OSCURO ===== */
@media (prefers-color-scheme: dark){
    .user-info{background:rgba(0,0,0,0.5);}
    .user-info small{color:#3399ff;}
}

/* ===== PRÓXIMA ITV ===== */
.proxima-itv{
    position:fixed;
    top:100px;
    right:20px;
    width:260px;
    border:2px solid #000;
    background:#f8f8f8;
}
.proxima-itv .titulo{
    background:#4a90e2;
    color:#fff;
    text-align:center;
    font-weight:bold;
    font-size:18px;
    padding:8px;
    border-bottom:1px solid #000;
}
.proxima-itv .fila{display:flex;border-top:1px solid #000;}
.proxima-itv .label{width:40%;padding:6px;font-weight:bold;border-right:1px solid #000;}
.proxima-itv .valor{width:60%;padding:6px;}

/* ===== MODO OSCURO ===== */
@media (prefers-color-scheme: dark) {
    body{background:#000;color:#ddd;}
    h1,h2,h3,h4,p,strong{color:#ddd;}
    th{background:#1c75bc;color:#fff;}
    .proxima-itv{background:#111;border-color:#555;color:#ddd;}
    .proxima-itv .titulo{background:#1c75bc;}
    .menu img{filter: invert(1) hue-rotate(180deg);}
    h1 img{filter:none;}
    .user-info{background:rgba(0,0,0,0.5);}
    .user-info small{color:#3399ff;}
}

/* ===== BASE ===== */
body { margin:15px; font-family:Arial,sans-serif; }
</style>
</head>
<body>

<div class="user-info">
    <strong><?= htmlspecialchars($_SESSION['usuario']) ?> | <?= htmlspecialchars($_SESSION['tipo']) ?></strong>
    <small><?= htmlspecialchars($flota_texto) ?></small>
    <div id="fecha-hora"></div>
</div>

<h1><img src="images/logo.webp" width="30" style="vertical-align: middle;"> Página Principal - Gestión de ITV</h1>
<hr style="border:1px solid #4a90e2; margin:10px 0 20px 0;">

<div class="menu">
  <a title="index" href="index.php"><img src="images/index.webp" alt="index" width="80"></a>
  <a title="citas" href="index.php?p=citas"><img src="images/citas.webp" alt="citas" width="80"></a>
  <a title="vehiculos" href="index.php?p=vehiculos"><img src="images/vehiculos.webp" alt="vehiculos" width="80"></a>
 <?php if(in_array($_SESSION['tipo'], ['Administrador','SuperAdministrador'])): ?>
  <a title="estaciones" href="index.php?p=estaciones"><img src="images/estaciones.webp" alt="estaciones" width="80"></a>
  <a title="seguridad" href="index.php?p=ips_bloqueadas"><img src="images/secury.webp" alt="seguridad" width="80"></a>
  <a title="usuarios" href="index.php?p=usuarios"><img src="images/usuarios.webp" alt="usuarios" width="80"></a>
 <?php endif; ?>
  <a title="imprimir" href="index.php?p=imprimir"><img src="images/imprimir.webp" alt="imprimir" width="80"></a>
  <a title="logout" href="index.php?p=logout"><img src="images/logout.webp" alt="logout" width="80"></a>
</div>

<br><br><br>

<?php if($proxima_itv): ?>
<div class="proxima-itv">
    <div class="titulo">PRÓXIMA ITV</div>
    <div class="fila"><div class="label">FECHA</div><div class="valor"><?= formatear_fecha($proxima_itv['fecha_cita']) ?></div></div>
    <div class="fila"><div class="label">HORA</div><div class="valor"><?= $proxima_itv['hora_cita'] ?></div></div>
    <div class="fila"><div class="label">ESTACIÓN</div><div class="valor"><?= htmlspecialchars($proxima_itv['estacion_cita']) ?> <?= $proxima_itv['tipo_cita']==='Primera'?'1ª':'2ª' ?></div></div>
</div>
<?php endif; ?>

<h2>Vehículos</h2>
<table>
<thead>
<tr>
<th>Vehículo</th><th>Matrícula</th><th>Tipo</th>
<th>Estado</th><th>Caducidad ITV</th>
<th>Días</th><th>Cita Asignada</th>
</tr>
</thead>
<tbody>
<?php foreach($vehiculos_filtrados as $v):
$info = obtener_color_y_texto($v);
$citas_v = obtener_citas_vehiculo($v['matricula'],$citas);
?>
<tr class="<?= $info['color'] ?>">
<td><?= htmlspecialchars($v['vehiculo']) ?></td>
<td><?= htmlspecialchars($v['matricula']) ?></td>
<td><?= htmlspecialchars($v['tipo'] ?? '-') ?></td>
<?php
$dias_estado = calcular_dias_restantes($v['caducidad_itv']);
$estado_mostrar = ($dias_estado < 0 && $v['estado'] === 'ACTIVO') ? 'ITV CADUCADA' : $v['estado'];
?>
<td><?= htmlspecialchars($estado_mostrar) ?></td>
<td><?= formatear_fecha($v['caducidad_itv']) ?></td>
<td><?= htmlspecialchars($info['texto_dias']) ?></td>
<td>
<?php if($citas_v): ?><ul>
<?php foreach($citas_v as $c): 
    // Todas las citas de tipo "Primera" que coincidan con la fecha de la próxima cita
    $es_cita_proxima_dia = $proxima_itv &&
        $c['tipo_cita'] === 'Primera' &&
        $c['fecha_cita'] === $proxima_itv['fecha_cita'];
?>
<li style="<?= $es_cita_proxima_dia ? 'color:black; font-weight:bold; text-decoration:underline;' : '' ?>">
    <?= formatear_fecha($c['fecha_cita']) ?> <?= htmlspecialchars($c['hora_cita']) ?> – 
    <?= htmlspecialchars($c['estacion_cita']) ?> <?= $c['tipo_cita']==='Primera'?'1ª':'2ª' ?>
</li>
<?php endforeach; ?></ul>
<?php else: ?>Sin cita<?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<h4 class="small version-title" style="margin-top:12px; text-align:left;"><?= htmlspecialchars($version) ?></h4>
<p class="small version-author" style="text-align:left;"><?= htmlspecialchars($autor) ?></p>

<script>
function actualizarFechaHora(){
    const d=new Date();
    document.getElementById('fecha-hora').innerText =
        d.toLocaleDateString('es-ES')+' '+d.toLocaleTimeString('es-ES');
}
actualizarFechaHora();
setInterval(actualizarFechaHora,1000);
</script>
</body>
</html>
<?php break;

// ===================== ips_bloqueadas =====================
case 'ips_bloqueadas':
// Verificar permisos: solo Administrador o SuperAdministrador
if (!isset($_SESSION['usuario']) || !in_array($_SESSION['tipo'], ['Administrador', 'SuperAdministrador'])) {
    header('Location: index.php?p=login');
    exit();
}

$is_admin = in_array($_SESSION['tipo'], ['Administrador', 'SuperAdministrador']);
$is_superadmin = $_SESSION['tipo'] === 'SuperAdministrador';

// Obtener flota del usuario y texto a mostrar (igual que en index/citas)
$flota_usuario = $_SESSION['flota'] ?? null;
$flota_texto = $is_superadmin ? "Todas las flotas" : ($flota_usuario ? strtoupper($flota_usuario) : "Sin flota asignada");

$ips_file = __DIR__ . '/ips_permanentemente_bloqueadas.log';
$ips_registro = __DIR__ . '/ips_bloqueadas.log';

// Procesar desbloqueo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['desbloquear_ip'])) {
    $ip_a_desbloquear = $_POST['desbloquear_ip'];
    function eliminarIP($archivo, $ip) {
        if(!file_exists($archivo)) return;
        $lineas = file($archivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $lineas = array_filter($lineas, fn($linea) => strpos($linea, $ip) === false);
        file_put_contents($archivo, implode("\n", $lineas)."\n");
    }
    eliminarIP($ips_file, $ip_a_desbloquear);
    eliminarIP($ips_registro, $ip_a_desbloquear);
    header('Location: index.php?p=ips_bloqueadas');
    exit();
}

// Leer IPs bloqueadas permanentemente
$ips_permanentes = file_exists($ips_file) ? file($ips_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];

// Leer todas las líneas del registro de bloqueos
$log_lines = file_exists($ips_registro) ? file($ips_registro, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];

// Contar intentos por IP y registrar usuarios por IP
$intentos = [];
$usuarios_por_ip = [];
foreach($log_lines as $line){
    if(preg_match('/IP:\s*([\d\.]+)/', $line, $matches)){
        $ip_line = $matches[1];
        $intentos[$ip_line] = ($intentos[$ip_line] ?? 0) + 1;

        if(preg_match('/Usuario:\s*(.+?)\s*\|/', $line, $m)) {
            $usuario_linea = trim($m[1]);
            if($usuario_linea !== 'ANONIMO') $usuarios_por_ip[$ip_line][$usuario_linea] = true;
        }
    }
}

// Unir todas las IPs a mostrar (permanentes + las que aparecen en el registro)
$all_ips = array_unique(array_merge($ips_permanentes, array_keys($intentos)));

// Preparar alertas y formato de usuarios por IP
$alertas = [];
foreach($all_ips as $ip){
    $alertas[$ip] = true;
    $usuarios_por_ip[$ip] = isset($usuarios_por_ip[$ip]) ? implode('<br>', array_keys($usuarios_por_ip[$ip])) : '-';
}

// Función para obtener país y código de bandera
function obtenerPais($ip) {
    $geo = @json_decode(file_get_contents("http://ip-api.com/json/$ip"));
    if ($geo && $geo->status === "success") return [$geo->country, strtolower($geo->countryCode)];
    return ['Desconocido',''];
}

// =====================
// VERSION Y AUTOR
$version_file = 'version.xk';
$version = 'v.1.0'; $autor = '';
if(file_exists($version_file)){
    $lines = file($version_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if(isset($lines[0])) $version = $lines[0];
    if(isset($lines[1])) $autor = $lines[1];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>ITVControl</title>
<link rel="icon" href="images/logo.webp">
<link rel="manifest" href="manifest.json">
<link rel="apple-touch-icon" sizes="180x180" href="images/logo.webp">
<link rel="stylesheet" href="style.css">

<style>
body { margin:15px; font-family:Arial,sans-serif; }
.user-info{
    position:fixed; top:10px; right:15px; text-align:right; font-size:14px;
    background:rgba(255,255,255,0.6); padding:5px 10px; border-radius:8px;
}
.user-info strong{display:block;}
.user-info small{color:#4a90e2;font-weight:bold;}
h1 img{vertical-align:middle;}
table{width:100%; border-collapse:collapse; margin-top:10px;}
th, td{padding:8px; border:1px solid #ccc; text-align:center; vertical-align:middle;}
th{background:#004aad; color:#fff;}
tr:hover{background:#f1f1f1;}
.flag{width:24px;height:18px; vertical-align:middle; margin-left:5px;}
button{padding:5px 10px; cursor:pointer; border:none; background:#ff4c4c; color:#fff; border-radius:4px;}
button:hover{background:#ff0000;}
.alerta{color:red; font-weight:bold;}
@media (prefers-color-scheme: dark){
    body{background:#000; color:#ddd;}
    h1,label,p,th,td,strong{color:#ddd;}
    table{background:#111;}
    th{background:#222;color:#fff;}
    tr:hover{background:#222;}
    button{background:#cc3333;color:#fff;}
    .menu a img:not([alt="Logo"]){filter:invert(1) hue-rotate(180deg);}
    h1 img{filter:none;}
    .user-info{background:rgba(0,0,0,0.5);}
    .user-info small{color:#3399ff;}
}
</style>
</head>
<body>

<div class="user-info">
    <strong><?= htmlspecialchars($_SESSION['usuario']) ?> | <?= htmlspecialchars($_SESSION['tipo']) ?></strong>
    <small><?= htmlspecialchars($flota_texto) ?></small>
    <div id="fecha-hora"></div>
</div>

<h1><img src="images/logo.webp" width="30" style="vertical-align: middle;"> Gestionar IPs Bloqueadas</h1>
<hr style="border:1px solid #4a90e2; margin:10px 0 20px 0;">

    <div class="menu">
      <a title="index" href="index.php"><img src="images/index.webp" alt="index" width="80"></a>
      <a title="citas" href="index.php?p=citas"><img src="images/citas.webp" alt="citas" width="80"></a>
      <a title="vehiculos" href="index.php?p=vehiculos"><img src="images/vehiculos.webp" alt="vehiculos" width="80"></a>
     <?php if(in_array($_SESSION['tipo'], ['Administrador','SuperAdministrador'])): ?>
      <a title="estaciones" href="index.php?p=estaciones"><img src="images/estaciones.webp" alt="estaciones" width="80"></a>
      <a title="seguridad" href="index.php?p=ips_bloqueadas"><img src="images/secury.webp" alt="seguridad" width="80"></a>
      <a title="usuarios" href="index.php?p=usuarios"><img src="images/usuarios.webp" alt="usuarios" width="80"></a>
     <?php endif; ?>
      <a title="imprimir" href="index.php?p=imprimir"><img src="images/imprimir.webp" alt="imprimir" width="80"></a>
      <a title="logout" href="index.php?p=logout"><img src="images/logout.webp" alt="logout" width="80"></a>
    </div>
  
    <br><br><br>

<script>
function actualizarFechaHora(){
    const d=new Date();
    document.getElementById('fecha-hora').innerText = d.toLocaleDateString('es-ES')+' '+d.toLocaleTimeString('es-ES');
}
actualizarFechaHora();
setInterval(actualizarFechaHora,1000);
</script>

<?php if(empty($alertas)): ?>
<h2 style="color:green; text-align:center;">No hay IPs bloqueadas.</h2>
<?php else: ?>
<table>
    <tr>
    <th>⚠ Usuarios</th>
    <th>IP</th>
    <th>País</th>
    <th>Intentos</th>
    <?php if($is_superadmin): ?><th>Acción</th><?php endif; ?>
</tr>
<?php foreach($alertas as $ip=>$val):
    list($pais,$codigoPais)=obtenerPais($ip);
    $num_intentos = $intentos[$ip] ?? 0;
    $usuarios_html = $usuarios_por_ip[$ip] ?? '-';
    $mostrar_boton = in_array($ip,$ips_permanentes);
?>
<tr>
    <td class="alerta"><?= $usuarios_html ?></td>
    <td><?= htmlspecialchars($ip) ?></td>
    <td><?= htmlspecialchars($pais) ?> <?php if($codigoPais): ?><img src="https://flagcdn.com/24x18/<?= $codigoPais ?>.png" class="flag"><?php endif; ?></td>
    <td><?= $num_intentos ?></td>
    <?php if($is_superadmin): ?>
    <td>
        <?php if($mostrar_boton): ?>
        <form method="POST" style="margin:0;">
            <input type="hidden" name="desbloquear_ip" value="<?= htmlspecialchars($ip) ?>">
            <button type="submit">Desbloquear</button>
        </form>
        <?php endif; ?>
    </td>
    <?php endif; ?>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>

<h4 class="small" style="text-align:left; margin:4px 0;"><?= htmlspecialchars($version) ?></h4>
<p class="small" style="text-align:left; margin:0;"><?= htmlspecialchars($autor) ?></p>

</body>
</html>
<?php break;

// ===================== login =====================
case 'login':
// Detectar IP real
function getRealIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    } else {
        return $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
    }
}

$ip = getRealIP();
$bloqueadasFile = __DIR__ . "/ips_permanentemente_bloqueadas.log";

// Comprobar si la IP está bloqueada
$ipsBloqueadas = file_exists($bloqueadasFile) ? file($bloqueadasFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];

if (in_array($ip, $ipsBloqueadas)) {
    // Redirigir a la página de acceso bloqueado
    header("Location: index.php?p=acceso-bloqueado");
    exit;
}


/* ===== NUEVO: LOG DE LOGIN DETALLADO ===== */
$registro_login_file = __DIR__ . '/registro_login.log';

function log_login($usuario, $success, $ip, $userAgent, $intentos=0) {
    global $registro_login_file;

    $fecha = new DateTime('now', new DateTimeZone('Europe/Madrid'));
    $timestamp = $fecha->format('Y-m-d H:i:s');

    $resultado = $success ? "ok" : "error";

    // Geolocalización básica via ip-api.com
    $pais = 'UNKNOWN';
    $region = 'UNKNOWN';
    $ciudad = 'UNKNOWN';
    try {
        if (!in_array(substr($ip,0,3), ['127','10.','192','169'])) { // omitir IPs privadas
            $ip_enc = urlencode($ip);
            $geo = @file_get_contents("http://ip-api.com/json/$ip_enc?fields=status,countryCode,regionName,city");
            if($geo) {
                $geoData = json_decode($geo, true);
                if(isset($geoData['status']) && $geoData['status']=='success') {
                    $pais = $geoData['countryCode'] ?? 'UNKNOWN';
                    $region = $geoData['regionName'] ?? 'UNKNOWN';
                    $ciudad = $geoData['city'] ?? 'UNKNOWN';
                }
            }
        }
    } catch (Exception $e) {
        // ignoramos errores
    }

    // Parse simple de user agent
    $browser = 'UNKNOWN';
    $os = 'UNKNOWN';
    $device = 'UNKNOWN';

    // Browser
    if (preg_match('/MSIE ([0-9\.]+)/', $userAgent, $m)) $browser = 'IE '.$m[1];
    elseif (preg_match('/Firefox\/([0-9\.]+)/', $userAgent, $m)) $browser = 'Firefox '.$m[1];
    elseif (preg_match('/Chrome\/([0-9\.]+)/', $userAgent, $m)) $browser = 'Chrome '.$m[1];
    elseif (preg_match('/Safari\/([0-9\.]+)/', $userAgent, $m)) $browser = 'Safari '.$m[1];

    // OS
    if (preg_match('/Windows NT ([0-9\.]+)/', $userAgent, $m)) $os = 'Windows '.$m[1];
    elseif (preg_match('/Mac OS X ([0-9_]+)/', $userAgent, $m)) $os = 'macOS '.str_replace('_', '.', $m[1]);
    elseif (preg_match('/Linux/', $userAgent)) $os = 'Linux';

    // Device
    if (preg_match('/Mobile|Android|iPhone|iPad/', $userAgent)) $device = 'Mobile';
    elseif (preg_match('/Tablet/', $userAgent)) $device = 'Tablet';
    elseif (preg_match('/bot|crawl|spider|slurp/i', $userAgent)) $device = 'Bot';
    else $device = 'Desktop';

    $linea = sprintf("[%s] %s | Usuario: %s | IP: %s | Pais: %s | Region: %s | Ciudad: %s | UA: %s | Browser: %s | OS: %s | Device: %s | Intentos: %d\n",
        $timestamp,
        $resultado,
        $usuario,
        $ip,
        $pais,
        $region,
        $ciudad,
        $userAgent,
        $browser,
        $os,
        $device,
        $intentos
    );

    file_put_contents($registro_login_file, $linea, FILE_APPEND | LOCK_EX);
}
/* ===== FIN NUEVO LOG ===== */




if (isset($_SESSION['usuario'])) {
    header('Location: index.php');
    exit();
}

$usuarios_file = 'usuarios.json';
$usuarios_fail_log = __DIR__ . '/usuarios-fail.log';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!file_exists($usuarios_file)) die("El archivo de usuarios no existe.");
    $usuarios = json_decode(file_get_contents($usuarios_file), true);

    $usuario_input = trim($_POST['usuario'] ?? '');
    $contraseña_input = $_POST['contraseña'] ?? '';

    $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN';
    $fecha = date("Y-m-d H:i:s");

    $usuario_encontrado = false;
    $error = "Usuario o contraseña incorrectos.";

    foreach ($usuarios as &$usuario) {
        if (strcasecmp($usuario['usuario'], $usuario_input) === 0) {
            $usuario_encontrado = true;

            if (!isset($usuario['intentos'])) $usuario['intentos'] = 0;
            if (!isset($usuario['bloqueado'])) $usuario['bloqueado'] = false;

            if ($usuario['bloqueado']) {
                $error = "Su cuenta ha sido bloqueada.";
                break;
            }

            if (password_verify($contraseña_input, $usuario['contraseña'])) {
                $usuario['intentos'] = 0;
                file_put_contents($usuarios_file, json_encode($usuarios, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

                /* ===== SESIÓN ===== */
                $_SESSION['usuario'] = $usuario['usuario'];
                $_SESSION['tipo'] = $usuario['tipo'];

                // 💡 Nuevo: asignar flota (si existe)
                if (!empty($usuario['flota'])) {
                    $_SESSION['flota'] = strtoupper(trim($usuario['flota']));
                } else {
                    unset($_SESSION['flota']); // SuperAdmins no tienen flota
                }

                /* ===== NUEVO: LOG DE LOGIN EXITOSO ===== */
                log_login($usuario['usuario'], true, $ip, $userAgent, $usuario['intentos']);
                /* ===== FIN LOG ===== */

                header('Location: index.php');
                exit();
            } else {
                $usuario['intentos']++;
                if ($usuario['intentos'] >= 3) {
                    $usuario['bloqueado'] = true;
                    $error = "Su cuenta ha sido bloqueada tras 3 intentos fallidos.";
                }

                file_put_contents($usuarios_file, json_encode($usuarios, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

                $registro = "[$fecha] Usuario: $usuario_input | IP: $ip | UA: $userAgent | Intentos: {$usuario['intentos']}\n";
                file_put_contents($usuarios_fail_log, $registro, FILE_APPEND | LOCK_EX);

                /* ===== NUEVO: LOG DE LOGIN FALLIDO ===== */
                log_login($usuario_input, false, $ip, $userAgent, $usuario['intentos']);
                /* ===== FIN LOG ===== */

                break;
            }
        }
    }

    if (!$usuario_encontrado) {
        $registro = "[$fecha] Usuario: $usuario_input (NO EXISTE) | IP: $ip | UA: $userAgent\n";
        file_put_contents($usuarios_fail_log, $registro, FILE_APPEND | LOCK_EX);

        /* ===== NUEVO: LOG DE USUARIO NO EXISTENTE ===== */
        log_login($usuario_input, false, $ip, $userAgent, 0);
        /* ===== FIN LOG ===== */
    }
}

$version_file = 'version.xk';
$version = 'v.1.0';
$autor = '';
if (file_exists($version_file)) {
    $lines = file($version_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (isset($lines[0])) $version = $lines[0];
    if (isset($lines[1])) $autor = $lines[1];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ITVControl</title>
<link rel="icon" href="images/logo.webp">
<link rel="manifest" href="manifest.json">
<link rel="apple-touch-icon" sizes="180x180" href="images/logo.webp">
<link rel="stylesheet" href="style.css">
<style>
/* ===== LOGIN RESPONSIVE ===== */
* { box-sizing: border-box; }

body {
    font-family: Arial, sans-serif;
    margin: 0;
    padding: clamp(12px, 4vw, 40px);
    min-height: 100vh;
    min-height: 100dvh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8f9fa;
    color: #000;
}

.login-card {
    width: min(100%, 420px);
    padding: clamp(20px, 5vw, 36px);
    background: #fff;
    border: 1px solid #e3e3e3;
    border-radius: 14px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, .08);
}

h1 {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: clamp(20px, 5vw, 27px);
    margin: 0 0 18px 0;
}

h1 img {
    width: clamp(28px, 7vw, 36px);
    height: auto;
}

label {
    display: block;
    margin-top: 16px;
    font-weight: bold;
    font-size: clamp(14px, 3.4vw, 15px);
}

input[type=text],
input[type=password] {
    width: 100%;
    margin-top: 6px;
    padding: 12px 14px;
    font-size: 16px;
    border: 1px solid #ccc;
    border-radius: 8px;
    background: #fff;
    color: #000;
}

.password-wrap {
    position: relative;
    width: 100%;
}

.password-wrap input {
    padding-right: 46px;
}

/* Ícono del ojo */
.eye-icon {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    width: 24px;
    height: 24px;
    cursor: pointer;
    user-select: none;
    fill: #000;
}

input[type=submit] {
    width: 100%;
    min-height: 46px;
    margin-top: 22px;
    padding: 10px 12px;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
    background: #004aad;
    color: #fff;
    border: none;
    border-radius: 8px;
}

input[type=submit]:hover {
    background: #0066ff;
}

.login-error {
    margin: 14px 0 0;
    color: #cc0000;
    font-size: 14px;
}

/* ===== CUADRO DE COOKIES ===== */
.cookies-box {
    margin-top: 20px;
    padding: 12px;
    width: 100%;
    border: 1px solid #ccc;
    background: #f7f7f7;
    color: #333;
    font-size: clamp(11px, 2.8vw, 13px);
    line-height: 1.4;
    border-radius: 6px;
}

.login-meta {
    margin-top: 16px;
    text-align: center;
}

/* Móvil en horizontal: permitir scroll en vez de recortar */
@media (max-height: 560px) {
    body { align-items: flex-start; }
}

/* Móviles pequeños */
@media (max-width: 420px) {
    body { padding: 10px; }
    .login-card { padding: 18px 16px; border-radius: 12px; }
}

/* Pantallas grandes: un poco más de presencia */
@media (min-width: 1100px) {
    .login-card { width: 460px; }
}

/* ===== MODO OSCURO ===== */
@media (prefers-color-scheme: dark) {
    body { background: #000; color: #ddd; }
    .login-card {
        background: #1a1a1a;
        border-color: #333;
        box-shadow: 0 10px 30px rgba(0, 0, 0, .5);
    }
    h1, label, p { color: #ddd; }
    input[type=text], input[type=password] {
        background: #222;
        color: #ddd;
        border: 1px solid #555;
    }
    input[type=submit] {
        background: #0066ff;
        color: #fff;
    }
    input[type=submit]:hover {
        background: #3399ff;
    }
    .eye-icon { fill: #fff; }
    .cookies-box {
        border: 1px solid #555;
        background: #121212;
        color: #ccc;
    }
    .login-error { color: #ff6b6b; }
}
</style>
</head>
<body>

<main class="login-card">

<h1>
    <img src="images/logo.webp" alt="Logo">
    Iniciar Sesión
</h1>

<form method="POST" action="">
    <label for="usuario">Usuario:</label>
    <input type="text" id="usuario" name="usuario" autocomplete="username" autocapitalize="none" spellcheck="false" required autofocus>

    <label for="contraseña">Contraseña:</label>
    <div class="password-wrap">
        <input type="password" id="contraseña" name="contraseña" autocomplete="current-password" required>
        <svg id="togglePass" class="eye-icon" onclick="togglePassword('contraseña','togglePass')" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
            <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12zm11 4a4 4 0 1 0 0-8 4 4 0 0 0 0 8z"/>
            <circle cx="12" cy="12" r="2"/>
        </svg>
    </div>

    <input type="submit" value="Iniciar Sesión">
</form>

<?php if(isset($error)): ?>
<p class="login-error"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<div class="cookies-box">
    Esta web utiliza únicamente cookies técnicas necesarias para el inicio de sesión.
    No se emplean cookies de análisis, publicidad ni de terceros.
</div>

<div class="login-meta">
    <h4 class="small"><?= htmlspecialchars($version) ?></h4>
    <p class="small"><?= htmlspecialchars($autor) ?></p>
</div>

</main>

<script>
function togglePassword(inputId, iconId){
    const input = document.getElementById(inputId);
    const icon = document.getElementById(iconId);
    const isPassword = input.type === "password";
    input.type = isPassword ? "text" : "password";
    icon.innerHTML = isPassword
        ? '<path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12zm11 4a4 4 0 0 1-4-4h-2a6 6 0 0 0 12 0h-2a4 4 0 0 1-4 4z"/><circle cx="12" cy="12" r="2"/>'
        : '<path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12zm11 4a4 4 0 1 0 0-8 4 4 0 0 0 0 8z"/><circle cx="12" cy="12" r="2"/>';
}

const loginForm = document.querySelector('form');
loginForm.addEventListener('keydown', function(e){
    if(e.key === 'Enter'){
        e.preventDefault();
        loginForm.submit();
    }
});
</script>
</body>
</html>
<?php break;

// ===================== logout =====================
case 'logout':
session_unset();
session_destroy();

// Redirigir a login.php
header('Location: index.php?p=login');
exit();
break;

// ===================== usuarios =====================
case 'usuarios':
/* ================= SEGURIDAD ================= */
if (!isset($_SESSION['usuario']) || !in_array($_SESSION['tipo'], ['Administrador','SuperAdministrador'])) {
    header('Location: index.php');
    exit();
}

$is_superadmin = $_SESSION['tipo'] === 'SuperAdministrador';
$flota_usuario = strtoupper(trim($_SESSION['flota'] ?? ''));

/* ================= ARCHIVOS ================= */
$usuarios_file = 'usuarios.json';
$log_file = 'usuarios-fail.log';
$registro_historico_file = 'registro_login.log';

if (!file_exists($usuarios_file)) {
    file_put_contents($usuarios_file, json_encode([]));
}

$usuarios = json_decode(file_get_contents($usuarios_file), true);
if (!is_array($usuarios)) $usuarios = [];

/* ================= RECUPERAR FLOTA SI NO HAY EN LA SESIÓN ================= */
if ($_SESSION['tipo'] === 'Administrador') {
    if ($flota_usuario === '' || $flota_usuario === null) {
        foreach ($usuarios as $u) {
            if (isset($u['usuario']) && strcasecmp($u['usuario'], $_SESSION['usuario']) === 0) {
                if (!empty($u['flota'])) {
                    $flota_usuario = strtoupper(trim($u['flota']));
                    $_SESSION['flota'] = $u['flota'];
                }
                break;
            }
        }
    }

    if ($flota_usuario === '' || $flota_usuario === null) {
        $_SESSION['error_msg'] = 'No se encontró flota asignada para tu usuario. Por favor, inicia sesión de nuevo o contacta con el administrador.';
        header('Location: index.php');
        exit();
    }
}

/* ================= FILTRAR USUARIOS SEGÚN FLOTA ================= */
if ($_SESSION['tipo'] === 'Administrador') {
    $usuarios_filtrados = [];

    foreach ($usuarios as $u) {
        $usuario_flota = strtoupper(trim($u['flota'] ?? ''));

        // Administradores no ven SuperAdministradores
        if (($u['tipo'] ?? '') === 'SuperAdministrador') continue;

        // Solo incluir usuarios de la misma flota
        if ($usuario_flota === $flota_usuario) {
            $usuarios_filtrados[] = $u;
        }
    }

    $usuarios = $usuarios_filtrados;
}

/* ================= CLAVES OFFLINE ================= */
$claves_file = 'sync_offline_claves.json';

if (!file_exists($claves_file)) {
    file_put_contents($claves_file, json_encode(new stdClass(), JSON_PRETTY_PRINT));
}

$claves_offline = json_decode(file_get_contents($claves_file), true);
if (!is_array($claves_offline)) $claves_offline = [];
$flotas_disponibles = [];

foreach ($usuarios as $u) {
    if (!empty($u['flota'])) {
        $flotas_disponibles[] = strtoupper(trim($u['flota']));
    }
}

$flotas_disponibles = array_values(array_unique($flotas_disponibles));
sort($flotas_disponibles);

/* ================= ORDENAR ================= */
usort($usuarios, function($a, $b) {
    return strcasecmp($a['usuario'], $b['usuario']);
});

/* ================= VER LOG HISTORICO ================= */
$log_modal_historico = null;
$total_historico = 0;

if (isset($_GET['ver_historico'])) {
    $usuario_log = $_GET['ver_historico'];
    if (file_exists($registro_historico_file)) {
        $lineas = file($registro_historico_file, FILE_IGNORE_NEW_LINES);
        $filtrado = [];
        foreach ($lineas as $linea) {
            if (stripos($linea, "Usuario: $usuario_log") !== false) {
                $filtrado[] = $linea;
            }
        }
        $filtrado = array_reverse($filtrado);
        $total_historico = count($filtrado);
        $log_modal_historico = $filtrado;
    } else {
        $log_modal_historico = [];
    }
}
/* ================= USUARIOS DESCONOCIDOS ================= */

$usuarios_desconocidos = [];
$total_intentos_desconocidos = 0;

$usuarios_completos = json_decode(
    file_get_contents($usuarios_file),
    true
);

if (!is_array($usuarios_completos)) {
    $usuarios_completos = [];
}

$usuarios_registrados = [];

foreach ($usuarios_completos as $u) {
    if (isset($u['usuario'])) {
        $usuarios_registrados[strtolower(trim($u['usuario']))] = true;
    }
}

if (file_exists($log_file)) {
    $lineas_desconocidos = file(
        $log_file,
        FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES
    );

    foreach ($lineas_desconocidos as $linea) {
        $posicion = stripos($linea, 'Usuario:');

        if ($posicion === false) {
            continue;
        }

        $nombre = trim(substr($linea, $posicion + strlen('Usuario:')));

        // Si el registro contiene un separador, quedarse con el nombre.
        $separador = strcspn($nombre, "|;");
        $nombre = trim(substr($nombre, 0, $separador));

        if ($nombre === '') {
            continue;
        }

        $nombre_normalizado = strtolower($nombre);

        if (isset($usuarios_registrados[$nombre_normalizado])) {
            continue;
        }

        if (!isset($usuarios_desconocidos[$nombre_normalizado])) {
            $usuarios_desconocidos[$nombre_normalizado] = [
                'usuario' => $nombre,
                'intentos' => 0,
                'ultimo' => '',
                'registros' => []
            ];
        }

        $usuarios_desconocidos[$nombre_normalizado]['intentos']++;
        $usuarios_desconocidos[$nombre_normalizado]['ultimo'] = $linea;
        $usuarios_desconocidos[$nombre_normalizado]['registros'][] = $linea;

        $total_intentos_desconocidos++;
    }

    uasort($usuarios_desconocidos, function ($a, $b) {
        return $b['intentos'] - $a['intentos'];
    });
}
/* ================= BORRAR LOGS DE USUARIOS DESCONOCIDOS ================= */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['borrar_logs_desconocidos'])
    && $is_superadmin
) {
    if (file_exists($log_file)) {
        if (file_put_contents($log_file, '', LOCK_EX) === false) {
            $_SESSION['error_msg'] = 'No se pudieron borrar los registros de acceso.';
        } else {
            $_SESSION['mensaje_msg'] = 'Se han borrado todos los intentos registrados.';
        }
    }

    header('Location: index.php?p=usuarios');
    exit();
}
/* ================= VER LOG ================= */
$log_modal = null;
$total_intentos = 0;

if (isset($_GET['ver_log'])) {
    $usuario_log = $_GET['ver_log'];
    if (file_exists($log_file)) {
        $lineas = file($log_file, FILE_IGNORE_NEW_LINES);
        $filtrado = [];
        foreach ($lineas as $linea) {
            if (stripos($linea, "Usuario: $usuario_log") !== false) {
                $filtrado[] = $linea;
            }
        }
        $filtrado = array_reverse($filtrado);
        $total_intentos = count($filtrado);
        $log_modal = $filtrado;
    } else {
        $log_modal = [];
    }
}

/* ================= LIMPIAR LOG ================= */
if (isset($_GET['limpiar_log'])) {
    $usuario_limpiar = $_GET['limpiar_log'];
    if (file_exists($log_file)) {
        $lineas = file($log_file);
        $nuevo = [];
        foreach ($lineas as $linea) {
            if (stripos($linea, "Usuario: $usuario_limpiar") === false) {
                $nuevo[] = $linea;
            }
        }
        file_put_contents($log_file, implode('', $nuevo));
    }
    header('Location: index.php?p=usuarios');
    exit();
}

/* ================= AÑADIR USUARIO ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario_nuevo = trim($_POST['usuario'] ?? '');
    $pass = $_POST['contraseña'] ?? '';
    $pass2 = $_POST['confirmar_contraseña'] ?? '';
    $tipo = $_POST['tipo'] ?? '';
    $flota = strtoupper(trim($_POST['flota'] ?? ''));

    // Administrador solo puede asignar su propia flota
    if ($_SESSION['tipo'] === 'Administrador') {
        $flota = $flota_usuario;
    }

    // Validaciones
    if ($usuario_nuevo === '' || $pass === '' || $pass2 === '' || $tipo === '') {
        $error = "Todos los campos son obligatorios.";
    } elseif ($tipo !== 'SuperAdministrador' && $flota === '') {
        $error = "La flota es obligatoria para todos los usuarios excepto SuperAdministradores.";
    } elseif ($pass !== $pass2) {
        $error = "Las contraseñas no coinciden.";
    } else {
// Comprobar duplicados en TODOS los usuarios del sistema,
// independientemente de la flota o del rol del usuario.
$usuarios_completos = json_decode(
    file_get_contents($usuarios_file),
    true
);

if (!is_array($usuarios_completos)) {
    $usuarios_completos = [];
}

$existe = false;

foreach ($usuarios_completos as $u) {
    if (
        isset($u['usuario']) &&
        strcasecmp(trim($u['usuario']), $usuario_nuevo) === 0
    ) {
        $existe = true;
        break;
    }
}
        if ($existe) {
            $error = "El usuario ya existe en alguna de las flotas del sistema.";
        } else {
            $usuarios[] = [
                'usuario' => $usuario_nuevo,
                'contraseña' => password_hash($pass, PASSWORD_DEFAULT),
                'tipo' => $tipo,
                'flota' => $flota
            ];
            usort($usuarios, function($a, $b) { return strcasecmp($a['usuario'], $b['usuario']); });
            file_put_contents($usuarios_file, json_encode(array_values($usuarios), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            header('Location: index.php?p=usuarios');
            exit();
        }
    }
}

/* ================= MARCAR LOG HISTORICO ================= */
if (file_exists($registro_historico_file)) {
    $lineas_historico = file($registro_historico_file, FILE_IGNORE_NEW_LINES);
    foreach ($usuarios as &$usuario) {
        $usuario['tiene_historico'] = false;
        foreach ($lineas_historico as $linea) {
            if (stripos($linea, "Usuario: {$usuario['usuario']}") !== false) {
                $usuario['tiene_historico'] = true;
                break;
            }
        }
    }
}
unset($usuario);

/* ================= MARCAR LOGS ================= */
if (file_exists($log_file)) {
    $lineas_log = file($log_file, FILE_IGNORE_NEW_LINES);
    foreach ($usuarios as &$usuario) {
        $usuario['tiene_log'] = false;
        foreach ($lineas_log as $linea) {
            if (stripos($linea, "Usuario: {$usuario['usuario']}") !== false) {
                $usuario['tiene_log'] = true;
                break;
            }
        }
    }
}
unset($usuario);

/* ================= VERSION Y AUTOR ================= */
$version_text = 'v.1.4';
$autor_text = 'B174M3 // XaeK';
if (file_exists('version.xk')) {
    $lines = file('version.xk', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $version_text = $lines[0] ?? $version_text;
    $autor_text = $lines[1] ?? $autor_text;
}

/* ================= TAMAÑO LOGS ================= */
$registro_historico_file = 'registro_login.log';
$tamano_log_mb = 0;

if (file_exists($registro_historico_file)) {
    $tamano_log_bytes = filesize($registro_historico_file);
    $tamano_log_mb = round($tamano_log_bytes / 1024 / 1024, 2); // Convertir a MB
}

/* ================= FORMATO TAMAÑO LOGS ================= */
function formato_tamano($bytes) {
    $unidades = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($unidades) - 1) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, 2) . ' ' . $unidades[$i];
}

// Obtener tamaño del log
$registro_historico_file = 'registro_login.log';
$tamano_log_formateado = '0 B';

if (file_exists($registro_historico_file)) {
    $tamano_log_bytes = filesize($registro_historico_file);
    $tamano_log_formateado = formato_tamano($tamano_log_bytes);
}

/* ================= LIMPIAR LOGS ANTERIORES A X AÑOS ================= */
if ($is_superadmin && isset($_POST['borrar_logs_anteriores'])) {
    $anios = intval($_POST['conservar_anios']);
    if ($anios < 1) $anios = 1;

    $registro_historico_file = 'registro_login.log';

    if (file_exists($registro_historico_file)) {
        $lineas = file($registro_historico_file, FILE_IGNORE_NEW_LINES);
        $nuevo = [];

        // Fecha límite: conservar solo los logs desde esta fecha en adelante
        $fecha_limite = strtotime("-$anios years January 1");

        foreach ($lineas as $linea) {
            // Extraemos la fecha del log (primeros 19 caracteres: [YYYY-MM-DD HH:MM:SS])
            if (preg_match('/\[(\d{4}-\d{2}-\d{2}) \d{2}:\d{2}:\d{2}\]/', $linea, $matches)) {
                $fecha_linea = strtotime($matches[1]);
                if ($fecha_linea >= $fecha_limite) {
                    $nuevo[] = $linea;
                }
            }
        }

        file_put_contents($registro_historico_file, implode("\n", $nuevo) . "\n");
        $mensaje = "Logs antiguos borrados, conservando los últimos $anios años.";
    } else {
        $mensaje = "El archivo de logs no existe.";
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['gestionar_claves'])) {

    $accion = $_POST['accion'] ?? '';

    // ================= SUPERADMINISTRADOR =================
    if ($is_superadmin) {

        // CREAR / EDITAR
        if ($accion === 'crear') {
            $clave = strtoupper(trim($_POST['nueva_clave'] ?? ''));
            $flotas = $_POST['flotas'] ?? [];

            if ($clave !== '') {
                $claves_offline[$clave] = $flotas;
            }
        }

        if ($accion === 'editar') {
            $clave = $_POST['clave'] ?? '';
            $flotas = $_POST['flotas'] ?? [];

            if ($clave !== '') {
                $claves_offline[$clave] = $flotas;
            }
        }

        // ELIMINAR
        if ($accion === 'eliminar') {
            $clave = trim($_POST['clave'] ?? '');

            if ($clave !== '') {
                unset($claves_offline[$clave]);
            }
        }

    }

    // ================= ADMINISTRADOR =================
    elseif ($_SESSION['tipo'] === 'Administrador') {

        if ($accion === 'editar_propia') {

            $clave_anterior = trim($_POST['clave_anterior'] ?? '');
            $nueva_clave = strtoupper(trim($_POST['nueva_clave'] ?? ''));

            // Comprobar que la clave pertenece a su propia flota
            if (
                $clave_anterior !== '' &&
                isset($claves_offline[$clave_anterior]) &&
                in_array(
                    $flota_usuario,
                    array_map('strtoupper', $claves_offline[$clave_anterior])
                ) &&
                $nueva_clave !== ''
            ) {

                // Mantener las demás flotas que pudieran compartir la clave
                $flotas_clave = $claves_offline[$clave_anterior];

                unset($claves_offline[$clave_anterior]);

                $claves_offline[$nueva_clave] = $flotas_clave;

            }
        }
    }

    file_put_contents(
        $claves_file,
        json_encode($claves_offline, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        LOCK_EX
    );

    header("Location: index.php?p=usuarios");
    exit();
}


?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>ITVControl</title>
<link rel="icon" href="images/logo.webp">
<link rel="manifest" href="manifest.json">
<link rel="apple-touch-icon" sizes="180x180" href="images/logo.webp">
<link rel="stylesheet" href="style.css">

<style>
body { margin:15px; font-family:Arial,sans-serif; }

/* ===== CUADRO USUARIO ARRIBA ===== */
.user-info {
    position: fixed;
    top: 10px;
    right: 15px;
    text-align: right;
    font-size: 14px;
    background: rgba(255,255,255,0.6);
    padding: 5px 10px;
    border-radius: 8px;
}
.user-info strong { display: block; }
.user-info small { color:#4a90e2; font-weight:bold; }

/* MODO OSCURO */
@media (prefers-color-scheme: dark) {
    .user-info { background: rgba(0,0,0,0.5); }
    .user-info small { color:#3399ff; }
}
  
/* ===== MODO OSCURO ICONOS MENU ===== */
@media (prefers-color-scheme: dark) {
    .menu img {
        filter: invert(1) hue-rotate(180deg);
    }
    h1 img {
        filter: none;
    }
}
  
@media (prefers-color-scheme: dark) {
    body { background:#000; color:#ddd; }
    h1,h2,h3,h4,p,strong { color:#ddd; }
    
    /* Tablas */
    table {
        background: linear-gradient(135deg, #0b1726, #16233b);
        border-color:#333;
        box-shadow:2px 2px 12px rgba(255,255,255,0.05);
    }
    th { background:#1c75bc; color:#fff; }
    td { border-color:#333; color:#ddd; }
    tr:nth-child(even) td { background:#111827; }
    tr:hover td { background:#1e293b; }

    /* Cuadro info-usuarios */
    .info-usuarios {
        border:2px solid #3399ff;
        background: linear-gradient(135deg, #111827, #1e293b);
        box-shadow: 2px 2px 12px rgba(0,0,0,0.5);
        color:#ddd;
    }
    .info-usuarios::before { color:#3399ff; }

    /* Inputs y selects */
    input, select { background:#111; color:#fff; border:1px solid #555; }
    input[type="submit"], button {
        background: linear-gradient(135deg, #1c75bc, #0066cc);
        border:1px solid #005bb5;
        color:#fff;
    }
    input[type="submit"]:hover, button:hover {
        background: linear-gradient(135deg, #005bb5, #1c75bc);
    }

    /* Iconos del menú */
    .menu img:not([alt="Logo"]) { filter: invert(1) hue-rotate(180deg); }
    h1 img { filter:none; }

    /* User info */
    .user-info { background: rgba(0,0,0,0.5); color:#ddd; }
    .user-info small { color:#3399ff; }

    /* Eye icon en inputs de contraseña */
    .eye-icon { fill:#fff; }
}
  
/* ===== MENU ICONOS ===== */
.menu { margin-bottom:15px; }
.menu a { margin-right:0px; }
.menu img { width:80px; height:auto; vertical-align:middle; transition:filter 0.3s ease; }

/* ===== FORMULARIOS ===== */
input, select { padding:5px; margin:2px 0; border-radius:4px; border:1px solid #aaa; }
input[type="submit"], button {
    cursor:pointer;
    border-radius:6px;
    padding:6px 10px;
    background:linear-gradient(135deg, #4a90e2, #1c75bc);
    border:1px solid #1c75bc;
    color:#fff;
    transition:background 0.3s ease;
}
input[type="submit"]:hover, button:hover { background:linear-gradient(135deg, #1c75bc, #4a90e2); }
.eye-icon { position:absolute; right:5px; top:5px; cursor:pointer; user-select:none; width:22px; height:22px; fill:#000; }

/* ===== TABLA USUARIOS ===== */
table {
    border-collapse:collapse;
    width:100%;
    background:linear-gradient(135deg, #f8fbff, #e9f1ff);
    border-radius:12px;
    overflow:hidden;
    box-shadow:2px 2px 12px rgba(0,0,0,0.1);
}
th, td { border:1px solid #ccc; padding:8px; vertical-align:top; }
th { background:#4a90e2; color:#fff; text-transform:uppercase; letter-spacing:0.5px; }
tr:nth-child(even) td { background:#f6f9ff; }
tr:hover td { background:#dce8ff; }

/* ===== CUADRO INFO TIPOS USUARIO ===== */
.info-usuarios {
    flex:1;
    padding:20px;
    border:2px solid #4a90e2;
    border-radius:12px;
    background: linear-gradient(135deg, #f0f8ff, #dbe9ff);
    box-shadow: 2px 2px 12px rgba(0,0,0,0.15);
    font-size:14px;
    line-height:1.5;
    color: #000;
    transition: all 0.3s ease;
    position: relative;
}
.info-usuarios::before {
    content: "\2139";
    font-size: 22px;
    color: #4a90e2;
    position: absolute;
    top:10px;
    left:10px;
}
.info-usuarios:hover {
    transform: translateY(-2px);
    box-shadow: 4px 4px 14px rgba(0,0,0,0.25);
}

/* MODO OSCURO TABLA Y INFO USUARIO */
@media (prefers-color-scheme: dark) {
    table {
        background: linear-gradient(135deg, #0b1726, #16233b);
        box-shadow: 2px 2px 12px rgba(255,255,255,0.05);
    }
    tr:nth-child(even) td { background:#111827; }
    tr:hover td { background:#1e293b; }

    .info-usuarios {
        border:2px solid #3399ff;
        background: linear-gradient(135deg, #111827, #1e293b);
        box-shadow: 2px 2px 12px rgba(0,0,0,0.5);
        color:#ddd;
    }
    .info-usuarios::before { color:#3399ff; }

    .eye-icon { fill:#fff; }
}
</style>
</head>
<body>

<div class="user-info">
    <strong><?= htmlspecialchars($_SESSION['usuario']) ?> | <?= htmlspecialchars($_SESSION['tipo']) ?></strong>
    <div>
        <small><?= $is_superadmin ? "Todas las flotas" : ($flota_usuario ?: "Sin flota asignada") ?></small>
    </div>
    <div id="fecha-hora"></div>
</div>

<h1><img src="images/logo.webp" alt="Logo" width="30" style="vertical-align:middle;"> Gestionar Usuarios</h1>
<hr style="border:1px solid #4a90e2; margin:10px 0 20px 0;">

<div class="menu">
<a title="index" href="index.php"><img src="images/index.webp" alt="index"></a>
<a title="citas" href="index.php?p=citas"><img src="images/citas.webp" alt="citas"></a>
<a title="vehiculos" href="index.php?p=vehiculos"><img src="images/vehiculos.webp" alt="vehiculos"></a>
<?php if(in_array($_SESSION['tipo'], ['Administrador','SuperAdministrador'])): ?>
    <a title="estaciones" href="index.php?p=estaciones"><img src="images/estaciones.webp" alt="estaciones"></a>
    <a title="seguridad" href="index.php?p=ips_bloqueadas"><img src="images/secury.webp" alt="seguridad"></a>
    <a title="usuarios" href="index.php?p=usuarios"><img src="images/usuarios.webp" alt="usuarios"></a>
<?php endif; ?>
<a title="imprimir" href="index.php?p=imprimir"><img src="images/imprimir.webp" alt="imprimir"></a>
<a title="logout" href="index.php?p=logout"><img src="images/logout.webp" alt="logout"></a>
</div>

<br><br><br>

<?php if (isset($error)): ?>
    <p style="color:#ff0000; font-weight:bold;">
        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
    </p>
<?php endif; ?>

<h2>Añadir Usuario</h2>
<div style="display:flex; gap:20px; align-items:flex-start;">
<form method="POST" style="flex:1;">
<label>Usuario:</label><input type="text" name="usuario" required><br>

<label>Contraseña:</label>
<div style="position:relative; display:inline-block;">
<input type="password" id="contraseña" name="contraseña" required style="padding-right:35px;">
<svg id="togglePass1" class="eye-icon" onclick="togglePassword('contraseña','togglePass1')" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
<path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12zm11 4a4 4 0 1 0 0-8 4 4 0 0 0 0 8z"/>
<circle cx="12" cy="12" r="2"/>
</svg>
</div><br>

<label>Confirmar Contraseña:</label>
<div style="position:relative; display:inline-block;">
<input type="password" id="confirmar_contraseña" name="confirmar_contraseña" required style="padding-right:35px;">
<svg id="togglePass2" class="eye-icon" onclick="togglePassword('confirmar_contraseña','togglePass2')" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
<path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12zm11 4a4 4 0 1 0 0-8 4 4 0 0 0 0 8z"/>
<circle cx="12" cy="12" r="2"/>
</svg>
</div><br>

<label>Tipo:</label>
<select name="tipo">
<option value="Usuario">Usuario</option>
<option value="Colaborador">Colaborador</option>
<option value="Administrador">Administrador</option>
<?php if($is_superadmin): ?>
<option value="SuperAdministrador">SuperAdministrador</option>
<?php endif; ?>
</select><br>

<?php if($_SESSION['tipo']==='Administrador'): ?>
<input type="hidden" name="flota" value="<?= htmlspecialchars($flota_usuario) ?>">
<strong>Flota:</strong> <?= htmlspecialchars($flota_usuario) ?><br>
<?php else: ?>
Flota:<input type="text" name="flota" <?= $is_superadmin ? '' : 'required' ?> style="text-transform:uppercase;"><br>
<?php endif; ?>

<input type="submit" value="Añadir Usuario">
</form>

<div class="info-usuarios">
<strong>Tipos de usuario:</strong><br><br>
<strong>Usuario</strong> - Puede consultar e imprimir.<br>
<strong>Colaborador</strong> - Todo lo anterior + añadir citas, vehículos y modificar estados/caducidades.<br>
<strong>Administrador</strong> - Todo lo anterior + gestionar estaciones, ver IPs bloqueadas, añadir/modificar/desbloquear/eliminar usuarios.<br>
<strong>SuperAdministrador</strong> - Todo lo anterior + editar id, matricula y flota vehiculo + desbloquear IPs bloqueadas.<br>
  <br>El <strong>SuperAdministrador</strong> es el unico que puede interactuar con todas las flotas. los demas usuarios includos los <strong>Administrador</strong>es unicamente podran interactuar con su flota asignada no pudiento interferir en las demás.
</div>
</div>

<br><br>
<h2>Lista de Usuarios</h2>
<table>
<thead>
<tr>
<th>Usuario</th>
<th>Flota</th>
<th>Tipo</th>
<th>Estado</th>
<th>Acciones</th>
</tr>
</thead>
<tbody>
<?php foreach ($usuarios as $usuario): ?>
<tr>
<td><?= htmlspecialchars($usuario['usuario']) ?></td>
<td><?= htmlspecialchars($usuario['flota'] ?? '-') ?></td>
<td><?= htmlspecialchars($usuario['tipo']) ?></td>
<td>
  <?php if (!empty($usuario['bloqueado'])): ?>
    <span style="color:#cc0000;font-weight:bold;">🔴 Bloqueado</span>

    <?php if (in_array($_SESSION['tipo'], ['Administrador','SuperAdministrador'])): ?>
      <form action="index.php?p=desbloquear_usuario" method="post" style="display:inline; margin-left:5px;">
        <input type="hidden" name="usuario" value="<?= htmlspecialchars($usuario['usuario']) ?>">
        <button type="submit"
                style="padding:2px 6px; cursor:pointer; background-color:#3399ff; color:#fff; font-size:12px; border:none; border-radius:4px;"
                onclick="return confirm('¿Estás seguro de que quieres desbloquear a este usuario?');">
          Desbloquear
        </button>
      </form>
    <?php endif; ?>

  <?php else: ?>
    <span style="color:green;">🟢 Activo</span>
  <?php endif; ?>
</td>
<td style="display:flex; gap:5px; flex-wrap:wrap;">
<form action="index.php?p=editar_usuario" method="get" style="margin:0;">
<input type="hidden" name="p" value="editar_usuario">
<input type="hidden" name="usuario" value="<?= htmlspecialchars($usuario['usuario']) ?>">
<button type="submit" style="padding:4px 8px; background:#4CAF50 !important; cursor:pointer;">Editar</button>
</form>

<form action="index.php?p=eliminar_usuario" method="get" style="margin:0;" onsubmit="return confirm('¿Estás seguro de eliminar este usuario?');">
<input type="hidden" name="p" value="eliminar_usuario">
<input type="hidden" name="usuario" value="<?= htmlspecialchars($usuario['usuario']) ?>">
<button type="submit" style="padding:4px 8px; cursor:pointer; background:#cc0000 !important; color:#fff;">Eliminar</button>
</form>

<?php if (!empty($usuario['tiene_historico'])): ?>
<form action="index.php?p=usuarios" method="get" style="margin:0;">
<input type="hidden" name="p" value="usuarios">
<input type="hidden" name="ver_historico" value="<?= htmlspecialchars($usuario['usuario']) ?>">
<button type="submit" style="padding:4px 8px; cursor:pointer; background:#b35f00 !important; background-image:none !important; color:#fff !important; font-weight:bold; border:none; border-radius:4px;">Ver Log HISTORICOS</button>
</form>
<?php endif; ?>
  
<?php if (!empty($usuario['tiene_log'])): ?>
<form action="index.php?p=usuarios" method="get" style="margin:0;">
<input type="hidden" name="p" value="usuarios">
<input type="hidden" name="ver_log" value="<?= htmlspecialchars($usuario['usuario']) ?>">
<button type="submit" style="padding:4px 8px; cursor:pointer; background:#cc3333 !important; background-image:none !important;color:#fff !important; font-weight:bold; border:none; border-radius:4px;">Ver Log FAILS</button>
</form>
<?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<br><br>

<!-- ===== INTENTOS DE ACCESO CON USUARIOS DESCONOCIDOS ===== -->

<h2>Intentos de acceso con usuarios desconocidos</h2>

<div style="padding:15px; border:2px solid #4a90e2; border-radius:10px; margin-bottom:20px;">

    <p>
<p>
    <strong>Total de intentos:</strong>
    <?= (int)$total_intentos_desconocidos ?>
    &nbsp; | &nbsp;
    <strong>Nombres diferentes:</strong>
    <?= count($usuarios_desconocidos) ?>
</p>

<?php if ($is_superadmin): ?>
<form method="POST"
      style="margin:10px 0 15px 0;"
      onsubmit="return confirm('¿Quieres borrar todos los registros de intentos de usuarios inexistentes?');">

    <button type="submit"
            name="borrar_logs_desconocidos"
            value="1"
            style="background:#cc0000 !important; color:#fff !important; border:1px solid #990000; padding:8px 14px; border-radius:6px; cursor:pointer; font-weight:bold;">
        🗑️ Borrar todos los intentos de usuarios desconocidos
    </button>

</form>
<?php endif; ?>

    <?php if (empty($usuarios_desconocidos)): ?>

        <p style="color:green; font-weight:bold;">
            No hay intentos registrados con usuarios desconocidos.
        </p>

    <?php else: ?>

        <table>
            <thead>
                <tr>
                    <th>Usuario introducido</th>
                    <th>Número de intentos</th>
                    <th>Último intento</th>
                    <th>Detalles</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($usuarios_desconocidos as $desconocido): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars(
                                $desconocido['usuario'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?></strong>
                        </td>

                        <td>
                            <?= (int)$desconocido['intentos'] ?>
                        </td>

                        <td>
                            <pre style="white-space:pre-wrap; word-break:break-word; margin:0; font-size:12px;"><?= htmlspecialchars(
                                $desconocido['ultimo'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?></pre>
                        </td>

                        <td>
                            <details>
                                <summary style="cursor:pointer; color:#1c75bc;">
                                    Ver todos los intentos
                                </summary>

                                <pre style="white-space:pre-wrap; word-break:break-word; font-size:12px; max-height:250px; overflow:auto;"><?php
                                    foreach ($desconocido['registros'] as $registro) {
                                        echo htmlspecialchars(
                                            $registro,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) . "\n";
                                    }
                                ?></pre>
                            </details>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

    <?php endif; ?>

</div>

<br><br>


<?php if ($is_superadmin || $_SESSION['tipo'] === 'Administrador'): ?>

<br><br>
<h2>Gestión de Claves Offline</h2>

<?php if ($is_superadmin): ?>

<!-- CREAR CLAVE -->
<form method="POST">
    <input type="hidden" name="gestionar_claves" value="1">
    <input type="hidden" name="accion" value="crear">

    <label>Nueva clave:</label>
    <input type="text" name="nueva_clave" required style="text-transform:uppercase;">

    <label>Flotas:</label>
    <div style="border:1px solid #ccc; padding:10px; border-radius:6px; max-height:150px; overflow:auto;">
        <?php foreach ($flotas_disponibles as $flota): ?>
            <label style="display:block; cursor:pointer;">
                <input type="checkbox" name="flotas[]" value="<?= htmlspecialchars($flota) ?>">
                <?= htmlspecialchars($flota) ?>
            </label>
        <?php endforeach; ?>
    </div>

    <button type="submit">Crear clave</button>
</form>

<br>

<?php endif; ?>

<!-- TABLA -->
<table>
    <thead>
        <tr>
            <th>Clave</th>
            <?php if ($is_superadmin): ?>
                <th>Flotas asignadas</th>
                <th>Acciones</th>
            <?php else: ?>
                <th>Acción</th>
            <?php endif; ?>
        </tr>
    </thead>

    <tbody>

    <?php foreach ($claves_offline as $clave => $flotas): ?>

        <?php
        // Los administradores únicamente ven claves de su propia flota
        if (
            !$is_superadmin &&
            !in_array(
                $flota_usuario,
                array_map('strtoupper', $flotas ?? [])
            )
        ) {
            continue;
        }
        ?>

        <tr>

            <td>
                <strong><?= htmlspecialchars($clave) ?></strong>
            </td>

            <?php if ($is_superadmin): ?>

            <td>
                <form method="POST" style="display:flex; gap:10px; align-items:center;">
                    <input type="hidden" name="gestionar_claves" value="1">
                    <input type="hidden" name="accion" value="editar">
                    <input type="hidden" name="clave" value="<?= htmlspecialchars($clave) ?>">

                    <div style="border:1px solid #ccc; padding:10px; border-radius:6px; max-height:150px; overflow:auto;">
                        <?php foreach ($flotas_disponibles as $flota): ?>
                            <label style="display:block; cursor:pointer;">
                                <input type="checkbox"
                                       name="flotas[]"
                                       value="<?= htmlspecialchars($flota) ?>"
                                       <?= in_array($flota, $flotas ?? []) ? 'checked' : '' ?>>
                                <?= htmlspecialchars($flota) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <button type="submit">Guardar</button>
                </form>
            </td>

            <td>
                <form method="POST" onsubmit="return confirm('¿Eliminar clave?');">
                    <input type="hidden" name="gestionar_claves" value="1">
                    <input type="hidden" name="accion" value="eliminar">
                    <input type="hidden" name="clave" value="<?= htmlspecialchars($clave) ?>">

                    <button type="submit" style="background:red;color:white;">
                        Eliminar
                    </button>
                </form>
            </td>

            <?php else: ?>

            <td>
                <form method="POST" style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">

                    <input type="hidden" name="gestionar_claves" value="1">
                    <input type="hidden" name="accion" value="editar_propia">
                    <input type="hidden" name="clave_anterior" value="<?= htmlspecialchars($clave) ?>">

                    <input type="text"
                           name="nueva_clave"
                           value="<?= htmlspecialchars($clave) ?>"
                           required
                           style="text-transform:uppercase;">

                    <button type="submit"
                            onclick="return confirm('¿Guardar la nueva clave de tu flota?');">
                        Cambiar clave
                    </button>

                </form>
            </td>

            <?php endif; ?>

        </tr>

    <?php endforeach; ?>

    </tbody>
</table>

<?php endif; ?>

  
<br><br><br>
  
<?php if ($is_superadmin): ?>
<p><strong>Tamaño log:</strong> <?= $tamano_log_formateado ?></p>
<h3>Borrar logs HISTORICOS antiguos</h3>
<form method="post" style="margin-bottom:20px;">
    <label>Conservar últimos 
        <input type="number" name="conservar_anios" value="4" min="1" style="width:60px;"> años
    </label>
    <button type="submit" name="borrar_logs_anteriores" 
            style="padding:4px 8px; margin-left:10px; background:#cc0000; color:#fff; border:none; border-radius:4px; cursor:pointer;">
        Aceptar
    </button>
</form>
<?php endif; ?>

<br><br><br>
<br><br><br>


<h4 class="small version-title" style="margin-top:12px; text-align:left;"><?= htmlspecialchars($version_text) ?></h4>
<p class="small version-author" style="text-align:left;"><?= htmlspecialchars($autor_text) ?></p>

  <?php if ($log_modal_historico !== null): ?>
<div id="logModalHistorico" style="
position:fixed;
top:50%;
left:50%;
transform:translate(-50%,-50%);
width:70%;
max-height:70%;
overflow:auto;
background:inherit;
color:inherit;
border:2px solid currentColor;
padding:20px;
z-index:9999;
box-shadow:0 0 20px rgba(0,0,0,0.5);
">
<h3>Log histórico (<?= $total_historico ?>)</h3>

<pre style="font-size:12px; white-space:pre-wrap; word-wrap:break-word;">
<?php
if (empty($log_modal_historico)) {
    echo "No hay registros históricos para este usuario.";
} else {
    foreach ($log_modal_historico as $linea) {
        $linea_html = htmlspecialchars($linea);
        if (stripos($linea, 'ok') !== false) {
            echo "<span style='color:green;'>$linea_html</span>\n";
        } elseif (stripos($linea, 'error') !== false) {
            echo "<span style='color:red;'>$linea_html</span>\n";
        } else {
            echo $linea_html . "\n";
        }
    }
}
?>
</pre>

<div style="margin-top:10px; display:flex; gap:10px; justify-content:flex-end;">
    <button onclick="document.getElementById('logModalHistorico').style.display='none'"
            style="padding:4px 8px; border:1px solid #777; border-radius:4px; cursor:pointer;">
        Cerrar
    </button>
</div>
</div>
<?php endif; ?>
  
<?php if ($log_modal !== null): ?>
<div id="logModal" style="
position:fixed;
top:50%;
left:50%;
transform:translate(-50%,-50%);
width:70%;
max-height:70%;
overflow:auto;
background:inherit;
color:inherit;
border:2px solid currentColor;
padding:20px;
z-index:9999;
box-shadow:0 0 20px rgba(0,0,0,0.5);
">
<h3>Intentos fallidos (<?= $total_intentos ?>)</h3>

<pre style="font-size:12px; white-space:pre-wrap; word-wrap:break-word;">
<?php
if (empty($log_modal)) {
    echo "No hay intentos registrados.";
} else {
    foreach ($log_modal as $linea) {
        echo htmlspecialchars($linea) . "\n";
    }
}
?>
</pre>

<div style="margin-top:10px; display:flex; gap:10px; justify-content:flex-end;">
    <a href="index.php?p=usuarios&amp;limpiar_log=<?= urlencode($_GET['ver_log']) ?>"
       onclick="return confirm('¿Eliminar todos los registros de este usuario?')"
       style="color:#cc0000;font-weight:bold; padding:4px 8px; border:1px solid #cc0000; border-radius:4px;">
       Limpiar registros
    </a>

    <button onclick="document.getElementById('logModal').style.display='none'"
            style="padding:4px 8px; border:1px solid #777; border-radius:4px; cursor:pointer;">
        Cerrar
    </button>
</div>
</div>
<?php endif; ?>

<script>
function togglePassword(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById(iconId);
    if(input.type==='password'){ input.type='text'; } else { input.type='password'; }
}

function actualizarFechaHora(){
    const d = new Date();
    document.getElementById('fecha-hora').innerText = d.toLocaleDateString('es-ES')+' '+d.toLocaleTimeString('es-ES');
}
actualizarFechaHora();
setInterval(actualizarFechaHora,1000);
</script>
</body>
</html>
<?php break;

// ===================== vehiculos =====================
case 'vehiculos':
if (!isset($_SESSION['usuario'])) {
    header('Location: index.php?p=login');
    exit();
}

$is_colab = isset($_SESSION['tipo']) && in_array($_SESSION['tipo'], ['Colaborador', 'Administrador', 'SuperAdministrador']);
$is_admin = isset($_SESSION['tipo']) && in_array($_SESSION['tipo'], ['Administrador', 'SuperAdministrador']);
$is_superadmin = $_SESSION['tipo'] === 'SuperAdministrador';

$flota_usuario = $_SESSION['flota'] ?? null;

// =====================
// CARGAR VEHÍCULOS
// =====================
$vehiculos_file = 'vehiculos.json';
if (!file_exists($vehiculos_file)) file_put_contents($vehiculos_file, json_encode([]));
$vehiculos = json_decode(file_get_contents($vehiculos_file), true);

// =====================
// CARGAR USUARIOS (para flotas) - Solo superadmin
// =====================
$usuarios_file = 'usuarios.json';
$flotas_existentes = [];
if($is_superadmin && file_exists($usuarios_file)) {
    $usuarios_data = json_decode(file_get_contents($usuarios_file), true);
    foreach($usuarios_data as $u) {
        if(isset($u['flota']) && !in_array($u['flota'], $flotas_existentes)) {
            $flotas_existentes[] = $u['flota'];
        }
    }
}

// =====================
// PROCESAR FORMULARIO NUEVO VEHÍCULO
// =====================
if($is_colab && $_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['vehiculo'])) {
    $nuevo_vehiculo = [
        'vehiculo' => $_POST['vehiculo'],
        'matricula' => $_POST['matricula'],
        'tipo' => $_POST['tipo'],
        'estado' => $_POST['estado'],
        'caducidad_itv' => $_POST['caducidad_itv'],
        'flota' => $is_superadmin ? $_POST['flota'] : $flota_usuario
    ];

    $vehiculos[] = $nuevo_vehiculo;
    file_put_contents($vehiculos_file, json_encode($vehiculos, JSON_PRETTY_PRINT));
    header('Location: index.php?p=vehiculos');
    exit();
}

// =====================
// FUNCIONES
// =====================
function calcular_dias_restantes($caducidad_itv) {
    $fecha_actual = new DateTime('today');
    $fecha_caducidad = new DateTime($caducidad_itv);
    $fecha_caducidad->setTime(0,0,0);
    $intervalo = $fecha_actual->diff($fecha_caducidad);
    return (int)$intervalo->format('%r%a');
}

function obtener_color_y_texto($vehiculo) {
    $estado = $vehiculo['estado'];
    $dias = calcular_dias_restantes($vehiculo['caducidad_itv']);
    if ($estado === 'ITV RECHAZADA') return ['color'=>'rojo_intenso','texto_dias'=>'ITV RECHAZADA'];
    if ($estado === 'BAJA') return ['color'=>'negro','texto_dias'=>'-'];
    if ($dias < 0) return ['color'=>'rojo_intenso','texto_dias'=>'Caducada hace '.abs($dias).' día'.(abs($dias)==1?'':'s')];
    if ($dias <= 1) return ['color'=>'rojo_intenso','texto_dias'=>$dias.' día'.($dias==1?'':'s')];
    if ($dias < 10) return ['color'=>'naranja_intenso','texto_dias'=>$dias.' días'];
    if ($dias <= 20) return ['color'=>'naranja_suave','texto_dias'=>$dias.' días'];
    if ($dias <= 35) return ['color'=>'azul','texto_dias'=>$dias.' días'];
    return ['color'=>'verde','texto_dias'=>$dias.' días'];
}

function formatear_fecha($fecha) {
    $f = DateTime::createFromFormat('Y-m-d', $fecha);
    return $f ? $f->format('d/m/Y') : $fecha;
}

// =====================
// FILTRAR VEHÍCULOS SEGÚN FLOTA
// =====================
if (!$is_superadmin && $flota_usuario) {
    $vehiculos = array_filter($vehiculos, fn($v) => strtoupper($v['flota'] ?? '') === strtoupper($flota_usuario));
}

// =====================
// ORDENAR VEHÍCULOS

$orden = $_GET['orden'] ?? 'vehiculo';

usort($vehiculos, function($a, $b) use ($orden){

    switch($orden){

        case 'matricula':
            return strcasecmp($a['matricula'], $b['matricula']);

        case 'tipo':
            return strcasecmp($a['tipo'], $b['tipo']);

        case 'estado':
            return strcasecmp($a['estado'], $b['estado']);

        case 'caducidad':
            return strtotime($a['caducidad_itv']) - strtotime($b['caducidad_itv']);

        case 'dias':
            return calcular_dias_restantes($a['caducidad_itv']) - calcular_dias_restantes($b['caducidad_itv']);

        case 'vehiculo':
        default:
            return strcasecmp($a['vehiculo'], $b['vehiculo']);
    }
});

// =====================
// VERSION Y AUTOR
$version = 'v.1.0'; $autor = 'Desconocido';
if(file_exists('version.xk')){
    $lineas = file('version.xk', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if(isset($lineas[0])) $version=$lineas[0];
    if(isset($lineas[1])) $autor=$lineas[1];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>ITVControl</title>
<link rel="icon" href="images/logo.webp">
<link rel="manifest" href="manifest.json">
<link rel="apple-touch-icon" sizes="180x180" href="images/logo.webp">
<link rel="stylesheet" href="style.css">

<style>
body { margin:15px; font-family:Arial,sans-serif; }
table{border-collapse:collapse;width:100%;}
th,td{border:1px solid #ccc;padding:8px;vertical-align:top;}
td.dias-numero{white-space:nowrap;}
ul{margin:0;padding-left:18px;}
.user-info{
    position:fixed; top:10px; right:15px; text-align:right; font-size:14px;
    background:rgba(255,255,255,0.6); padding:5px 10px; border-radius:8px;
}
.user-info strong{display:block;}
.user-info small{color:#4a90e2;font-weight:bold;}
.boton-editar{background:#4CAF50;color:white;padding:3px 7px;border-radius:4px;text-decoration:none;}
.boton-eliminar{background:#cc0000;color:white;padding:3px 7px;border-radius:4px;text-decoration:none;}
input, select {padding:5px; margin:2px 0; border-radius:4px;}
input[type="submit"] {cursor:pointer; background:#4CAF50;color:white;border:none;padding:5px 8px;}
@media (prefers-color-scheme: dark){
    body{background:#000;color:#ddd;}
    h1,h2,h3,h4,p,strong{color:#ddd;}
    th{background:#1c75bc;color:#fff;}
    .menu img{filter: invert(1) hue-rotate(180deg);}
    .user-info{background:rgba(0,0,0,0.5);}
    .user-info small{color:#3399ff;}
}
.negro{background:black;color:grey;}
.rojo_intenso{background:#cc0000;color:white;}
.naranja_intenso{background:#ff6600;color:white;}
.naranja_suave{background:#ffae0d;color:white;}
.azul{background:#3399ff;color:white;}
.verde{background:#4CAF50;color:white;}

/* FORMULARIO estilo citas.php */
input, select { 
    padding:5px; 
    margin:2px 0; 
    border-radius:4px; 
    border:1px solid #ccc; 
    background:#fff; 
    color:#000; 
}
input[type="submit"] { 
    cursor:pointer; 
    background:#4a90e2; 
    color:#fff; 
    border:none; 
    padding:6px 12px; 
    border-radius:4px; 
}
input[type="submit"]:hover { 
    background:#1c75bc; 
}

/* MODO OSCURO */
@media (prefers-color-scheme: dark){
    input, select { 
        background:#111; 
        color:#fff; 
        border:1px solid #555; 
    }
    input[type="submit"] { 
        background: linear-gradient(135deg,#1c75bc,#0066cc); 
        border:1px solid #005bb5; 
        color:#fff; 
    }
    input[type="submit"]:hover { 
        background: linear-gradient(135deg,#005bb5,#1c75bc); 
    }
}

</style>
</head>
<body>

<div class="user-info">
    <strong><?= htmlspecialchars($_SESSION['usuario']) ?> | <?= htmlspecialchars($_SESSION['tipo']) ?></strong>
    <small><?= $is_superadmin ? "Todas las flotas" : ($flota_usuario ? strtoupper($flota_usuario) : "Sin flota asignada") ?></small>
    <div id="fecha-hora"></div>
</div>

<h1><img src="images/logo.webp" width="30" style="vertical-align: middle;"> Gestionar Vehículos</h1>
<hr style="border:1px solid #4a90e2; margin:10px 0 20px 0;">

<div class="menu">
  <a title="index" href="index.php"><img src="images/index.webp" alt="index" width="80"></a>
  <a title="citas" href="index.php?p=citas"><img src="images/citas.webp" alt="citas" width="80"></a>
  <a title="vehiculos" href="index.php?p=vehiculos"><img src="images/vehiculos.webp" alt="vehiculos" width="80"></a>
  <?php if(in_array($_SESSION['tipo'], ['Administrador','SuperAdministrador'])): ?>
    <a title="estaciones" href="index.php?p=estaciones"><img src="images/estaciones.webp" alt="estaciones" width="80"></a>
    <a title="seguridad" href="index.php?p=ips_bloqueadas"><img src="images/secury.webp" alt="seguridad" width="80"></a>
    <a title="usuarios" href="index.php?p=usuarios"><img src="images/usuarios.webp" alt="usuarios" width="80"></a>
  <?php endif; ?>
  <a title="imprimir" href="index.php?p=imprimir"><img src="images/imprimir.webp" alt="imprimir" width="80"></a>
  <a title="logout" href="index.php?p=logout"><img src="images/logout.webp" alt="logout" width="80"></a>
</div>

<br><br><br>

<script>
function actualizarFechaHora(){
    const d=new Date();
    document.getElementById('fecha-hora').innerText =
        d.toLocaleDateString('es-ES')+' '+d.toLocaleTimeString('es-ES');
}
actualizarFechaHora();
setInterval(actualizarFechaHora,1000);
</script>

<?php if($is_colab): ?>
<h2>Añadir Nuevo Vehículo</h2>
<form method="POST">
    <label>Vehículo:</label><input type="text" name="vehiculo" required><br>
    <label>Matrícula:</label><input type="text" name="matricula" required><br>
    <label>Tipo:</label>
    <select name="tipo" required>
        <option value="Turismo, Transporte mercancías hasta 3500 kg y cuadriciclos">Turismo, Transporte mercancías hasta 3500 kg y cuadriciclos</option>
        <option value="Transporte mercancías más de 3500 kg">Transporte mercancías más de 3500 kg</option>
        <option value="Cabeza tractora + Remolque">Cabeza tractora + Remolque</option>
        <option value="Autobuses y microbuses">Autobuses y microbuses</option>
        <option value="Verificación taxímetro">Verificación taxímetro</option>
        <option value="Periódica taxi con verificación taxímetro">Periódica taxi con verificación taxímetro</option>
        <option value="Periódica taxi sin verificación taxímetro">Periódica taxi sin verificación taxímetro</option>
        <option value="Ciclomotores de 2 y 3 ruedas, motocicletas">Ciclomotores de 2 y 3 ruedas, motocicletas</option>
        <option value="Quadsvehículos similares y ATVs">Quadsvehículos similares y ATVs</option>
        <option value="Obras y Servicios (excepto quadsvehículos similares y ATVs)">Obras y Servicios (excepto quadsvehículos similares y ATVs)</option>
        <option value="Tractor Sin Remolque (Agrícolas y de Obras y Servicios)">Tractor Sin Remolque (Agrícolas y de Obras y Servicios)</option>
        <option value="Tractor Con Remolque (Agrícolas y de Obras y Servicios)">Tractor Con Remolque (Agrícolas y de Obras y Servicios)</option>
    </select><br>
    <label>Estado:</label>
    <select name="estado">
        <option value="ACTIVO">ACTIVO</option>
        <option value="ITV RECHAZADA">ITV RECHAZADA</option>
        <option value="BAJA">BAJA</option>
    </select><br>
    <label>Caducidad ITV:</label><input type="date" name="caducidad_itv" required><br>

    <?php if($is_superadmin): ?>
        <label>Flota:</label>
        <select name="flota" required>
            <?php foreach($flotas_existentes as $f): ?>
                <option value="<?= htmlspecialchars($f) ?>"><?= htmlspecialchars($f) ?></option>
            <?php endforeach; ?>
        </select><br>
    <?php endif; ?>
    <br>
    <input type="submit" value="Añadir Vehículo">
</form>
<hr>
<?php endif; ?>

<h2>Lista de Vehículos</h2>
<form method="GET" action="index.php?p=vehiculos">
    <input type="hidden" name="p" value="vehiculos">
    <label>Ordenar por:</label>
    <select name="orden" onchange="this.form.submit()">
        <option value="vehiculo" <?= ($orden ?? '')=='vehiculo'?'selected':'' ?>>Vehículo</option>
        <option value="matricula" <?= ($orden ?? '')=='matricula'?'selected':'' ?>>Matrícula</option>
        <option value="tipo" <?= ($orden ?? '')=='tipo'?'selected':'' ?>>Tipo</option>
        <option value="estado" <?= ($orden ?? '')=='estado'?'selected':'' ?>>Estado</option>
        <option value="caducidad" <?= ($orden ?? '')=='caducidad'?'selected':'' ?>>Caducidad ITV</option>
        <option value="dias" <?= ($orden ?? '')=='dias'?'selected':'' ?>>Días</option>
    </select>
</form>
<br>
<table>
<thead>
<tr>
<th>Vehículo</th><th>Matrícula</th><th>Tipo</th><th>Estado</th><th>Caducidad ITV</th><th>Días</th>
<?php if($is_colab): ?><th>Acciones</th><?php endif; ?>
</tr>
</thead>
<tbody>
<?php foreach($vehiculos as $v):
$info = obtener_color_y_texto($v);
$puede_editar = $is_superadmin || ($is_colab && strtoupper($v['flota']??'')===strtoupper($flota_usuario));
$puede_eliminar = $is_superadmin || ($is_admin && strtoupper($v['flota']??'')===strtoupper($flota_usuario));
?>
<tr class="<?= $info['color'] ?>">
<td><?= htmlspecialchars($v['vehiculo']) ?></td>
<td><?= htmlspecialchars($v['matricula']) ?></td>
<td><?= htmlspecialchars($v['tipo']) ?></td>
<td><?= htmlspecialchars($v['estado']) ?></td>
<td><?= formatear_fecha($v['caducidad_itv']) ?></td>
<td><?= htmlspecialchars($info['texto_dias']) ?></td>
<?php if($is_colab): ?>
<td>
<?php if($puede_editar): ?>
<a href="index.php?p=editar_vehiculo&amp;id=<?= urlencode($v['matricula']) ?>" class="boton-editar">Editar</a>
<?php endif; ?>
<?php if($puede_eliminar): ?>
<a href="index.php?p=eliminar_vehiculo&amp;id=<?= urlencode($v['matricula']) ?>" class="boton-eliminar">Eliminar</a>
<?php endif; ?>
</td>
<?php endif; ?>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<h4 class="small" style="text-align:left; margin:4px 0;"><?= htmlspecialchars($version) ?></h4>
<p class="small" style="text-align:left; margin:0;"><?= htmlspecialchars($autor) ?></p>

</body>
</html>
<?php break;

// ===================== check_bloqueo (compat) =====================
case 'check_bloqueo':
    header('Location: index.php?p=login');
    exit;
    break;

default:
    break;
}
