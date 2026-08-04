<?php
include __DIR__ . '/check_bloqueo.php';

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

session_name('ITVCONTROL_SESSID');
session_start();

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
<title>ITVControl</title>
<link rel="icon" href="images/logo.webp">
<link rel="manifest" href="manifest.json">
<link rel="apple-touch-icon" sizes="180x180" href="images/logo.webp">
<link rel="stylesheet" href="style.css">
<style>
/* ===== TU CSS EXISTENTE ===== */
body {
    font-family: Arial, sans-serif;
    background:#fff;
    color:#000;
    padding:20px;
}
h1 {
    font-size:26px;
    margin-bottom:20px;
}
h1 img {
    vertical-align: middle;
    width:30px;
}
label {
    display:block;
    margin-top:10px;
    font-weight:bold;
}
input[type=text],
input[type=password] {
    width:250px;
    padding:5px;
    margin-top:2px;
    border:1px solid #ccc;
    background:#fff;
    color:#000;
}
input[type=submit] {
    margin-top:12px;
    padding:6px 12px;
    cursor:pointer;
    background:#004aad;
    color:#fff;
    border:none;
    border-radius:4px;
}
input[type=submit]:hover {
    background:#0066ff;
}

/* Ícono del ojo */
.eye-icon {
    position:absolute;
    right:5px;
    top:6px;
    cursor:pointer;
    user-select:none;
    width:22px;
    height:22px;
    fill:#000;
}
@media (prefers-color-scheme: dark) {
    .eye-icon { fill:#fff; }
}

/* ===== MODO OSCURO ===== */
@media (prefers-color-scheme: dark) {
    body { background:#000; color:#ddd; }
    h1,label,p { color:#ddd; }
    input[type=text], input[type=password] {
        background:#222;
        color:#ddd;
        border:1px solid #555;
    }
    input[type=submit] {
        background:#0066ff;
        color:#fff;
    }
    input[type=submit]:hover {
        background:#3399ff;
    }
}

/* ===== CUADRO DE COOKIES ===== */
.cookies-box {
    margin-top: 25px;
    padding: 12px;
    border: 1px solid #ccc;
    background: #f7f7f7;
    color: #333;
    width: 330px;
    font-size: 13px;
    border-radius: 6px;
}
@media (prefers-color-scheme: dark) {
    .cookies-box {
        border: 1px solid #555;
        background: #1a1a1a;
        color: #ccc;
    }
}
</style>
</head>
<body>

<h1>
    <img src="images/logo.webp" alt="Logo">
    Iniciar Sesión
</h1>

<form method="POST" action="">
    <label for="usuario">Usuario:</label>
    <input type="text" id="usuario" name="usuario" autocomplete="username" autocapitalize="none" spellcheck="false" required>

    <label for="contraseña">Contraseña:</label>
    <div style="position:relative; display:inline-block;">
        <input type="password" id="contraseña" name="contraseña" autocomplete="current-password" required style="padding-right:35px;">
        <svg id="togglePass" class="eye-icon" onclick="togglePassword('contraseña','togglePass')" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
            <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12zm11 4a4 4 0 1 0 0-8 4 4 0 0 0 0 8z"/>
            <circle cx="12" cy="12" r="2"/>
        </svg>
    </div>

    <br><br>
    <input type="submit" value="Iniciar Sesión">
</form>

<?php if(isset($error)): ?>
<p style="color:red;"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<div class="cookies-box">
    Esta web utiliza únicamente cookies técnicas necesarias para el inicio de sesión.
    No se emplean cookies de análisis, publicidad ni de terceros.
</div>

<h4 class="small" style="margin-top:12px;"><?= htmlspecialchars($version) ?></h4>
<p class="small"><?= htmlspecialchars($autor) ?></p>

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