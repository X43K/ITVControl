<?php
session_start();

// Verificar permisos: solo Administrador o SuperAdministrador
if (!isset($_SESSION['usuario']) || !in_array($_SESSION['tipo'], ['Administrador', 'SuperAdministrador'])) {
    header('Location: login.php');
    exit();
}

$is_admin = in_array($_SESSION['tipo'], ['Administrador', 'SuperAdministrador']);
$is_superadmin = $_SESSION['tipo'] === 'SuperAdministrador';

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
    header('Location: '.$_SERVER['PHP_SELF']);
    exit();
}

// Leer IPs bloqueadas permanentemente
$ips_permanentes = file_exists($ips_file) ? file($ips_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];

// Leer todas las líneas del registro de bloqueos
$log_lines = file_exists($ips_registro) ? file($ips_registro, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];

// Contar intentos por IP
$intentos = [];
foreach($log_lines as $line){
    if(preg_match('/IP:\s*([\d\.]+)/', $line, $matches)){
        $ip_line = $matches[1];
        if(!isset($intentos[$ip_line])) $intentos[$ip_line] = 0;
        $intentos[$ip_line]++;
    }
}

// Obtener todas las IPs únicas del registro
$ips_bloqueadas = array_keys($intentos);

// Función para obtener país y código de bandera
function obtenerPais($ip) {
    $geo = @json_decode(file_get_contents("http://ip-api.com/json/$ip"));
    if ($geo && $geo->status === "success") {
        return [$geo->country, strtolower($geo->countryCode)];
    }
    return ['Desconocido', ''];
}

// =====================
// CARGAR VERSIÓN Y AUTOR
// =====================
$version_file = 'version.xk';
$version = 'v.1.0'; 
$autor = '';
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
<title>IPs Bloqueadas</title>
<link rel="icon" href="images/logo.webp">
<link rel="stylesheet" href="style.css">

<style>
body { 
    margin: 15px; 
    font-family: Arial, sans-serif; 
}

table { width:100%; border-collapse: collapse; background:#fff; box-shadow:0 0 10px rgba(0,0,0,0.1); }
th, td { padding:12px; border-bottom:1px solid #ddd; text-align:center; }
th { background:#004aad; color:#fff; }
tr:hover { background:#f1f1f1; }

.flag { width:24px; height:18px; vertical-align:middle; margin-left:5px; }

button { padding:5px 10px; cursor:pointer; border:none; background:#ff4c4c; color:#fff; border-radius:4px; }
button:hover { background:#ff0000; }

@media (prefers-color-scheme: dark) {
    body { background:#000; color:#ddd; }
    h1,label,p,th,td,strong { color:#ddd; }
    table { background:#111; }
    th { background:#222; color:#fff; }
    tr:hover { background:#222; }
    button { background:#cc3333; color:#fff; }
    .menu a img:not([alt="Logo"]) { filter: invert(1) hue-rotate(180deg); }
    h1 img { filter:none; }
}
</style>
</head>
<body>

<div class="user-info" style="position:fixed;top:10px;right:15px;text-align:right;font-size:14px;">
    <strong><?= htmlspecialchars($_SESSION['usuario']) ?> | <?= htmlspecialchars($_SESSION['tipo']) ?></strong>
    <div id="fecha-hora"></div>
</div>

<br>

<h1><img src="images/logo.webp" width="30" style="vertical-align: middle;"> IPs Bloqueadas</h1>

<br>

<div class="menu">
    <a title="index" href="index.php"><img src="images/index.webp" alt="index" width="80"></a>
    <a title="citas" href="citas.php"><img src="images/citas.webp" alt="citas" width="80"></a>
    <a title="vehiculos" href="vehiculos.php"><img src="images/vehiculos.webp" alt="vehiculos" width="80"></a>
    <?php if ($is_admin): ?>
        <a title="estaciones" href="estaciones.php"><img src="images/estaciones.webp" alt="estaciones" width="80"></a>
        <a title="seguridad" href="ips_bloqueadas.php"><img src="images/secury.webp" alt="seguridad" width="80"></a>
    <?php endif; ?>
    <?php if ($is_superadmin): ?>
        <a title="usuarios" href="usuarios.php"><img src="images/usuarios.webp" alt="usuarios" width="80"></a>
    <?php endif; ?>
    <a title="imprimir" href="imprimir.php"><img src="images/imprimir.webp" alt="imprimir" width="80"></a>
    <a title="logout" href="logout.php"><img src="images/logout.webp" alt="logout" width="80"></a>
</div>

<br>

<?php if(empty($ips_bloqueadas)): ?>
<h1 style="color:green; text-align:center;">No hay IPs bloqueadas.</h1>
<?php else: ?>
<table>
    <tr>
        <th>IP</th>
        <th>País</th>
        <th>Intentos</th>
        <?php if($is_admin): ?><th>Acción</th><?php endif; ?>
    </tr>
    <?php foreach($ips_bloqueadas as $ip):
        list($pais, $codigoPais) = obtenerPais($ip);
        $num_intentos = $intentos[$ip] ?? 0;
        $mostrar_boton = in_array($ip, $ips_permanentes);
    ?>
    <tr>
        <td><?= htmlspecialchars($ip) ?></td>
        <td>
            <?= htmlspecialchars($pais) ?>
            <?php if($codigoPais): ?>
            <img src="https://flagcdn.com/24x18/<?= $codigoPais ?>.png" alt="<?= $pais ?>" class="flag">
            <?php endif; ?>
        </td>
        <td><?= $num_intentos ?></td>
        <?php if($is_admin): ?>
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
