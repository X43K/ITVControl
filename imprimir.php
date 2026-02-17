<?php
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario'])) {
    header('Location: index.php');
    exit();
}

// Verificar si el usuario es administrador
$is_admin = isset($_SESSION['tipo']) && in_array($_SESSION['tipo'], ['Administrador', 'SuperAdministrador']);
$is_superadmin = isset($_SESSION['tipo']) && $_SESSION['tipo'] === 'SuperAdministrador';

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
<title>Impresora</title>
<link rel="icon" href="images/logo.webp">
<link rel="stylesheet" href="style.css">

<style>
/* Colores consistentes */
.negro { background-color: black; color: white; }
.rojo_intenso { background-color: #cc0000; color: white; }
.naranja_intenso { background-color: #ff6600; color: white; }
.naranja_suave { background-color: #ffcc66; color: black; }
.azul { background-color: #3399ff; color: white; }
.verde { background-color: #4CAF50; color: white; }

table { border-collapse: collapse; width: 100%; }
th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
th { background-color: #eee; }

/* ===== MODO OSCURO AUTOMÁTICO ===== */
@media (prefers-color-scheme: dark) {
    body { background:#000; color:#ddd; }
    h1,h2,h3,h4,p,strong { color:#ddd; }
    th { background:#222; color:#fff; }
    .menu img { filter: invert(1) hue-rotate(180deg); }
    h1 img { filter:none; } /* logo.webp NO se invierte */
}
  body {
    margin: 15px;       /* margen superior, inferior, izquierdo y derecho */
    font-family: Arial, sans-serif; /* fuente consistente */
}

</style>
</head>
<body>

<div class="user-info" style="position:fixed;top:10px;right:15px;text-align:right;font-size:14px;">
    <strong><?= $_SESSION['usuario'] ?> | <?= $_SESSION['tipo'] ?></strong>
        <div id="fecha-hora"></div>
</div>

<br>

<h1>
<img src="images/logo.webp" width="30" style="vertical-align: middle;"> Impresora
</h1>

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

<p>
    <a href="imprimir_caducidades.php">
        <button class="azul" style="padding:12px 20px;font-size:16px;cursor:pointer;">
            IMPRIMIR CADUCIDADES
        </button>
    </a>
</p>

<p>
    <a href="imprimir_citas.php">
        <button class="verde" style="padding:12px 20px;font-size:16px;cursor:pointer;">
            IMPRIMIR CITAS
        </button>
    </a>
</p>

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