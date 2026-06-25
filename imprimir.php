<?php
session_start();
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
      <a title="citas" href="citas.php"><img src="images/citas.webp" alt="citas" width="80"></a>
      <a title="vehiculos" href="vehiculos.php"><img src="images/vehiculos.webp" alt="vehiculos" width="80"></a>
     <?php if(in_array($_SESSION['tipo'], ['Administrador','SuperAdministrador'])): ?>
      <a title="estaciones" href="estaciones.php"><img src="images/estaciones.webp" alt="estaciones" width="80"></a>
      <a title="seguridad" href="ips_bloqueadas.php"><img src="images/secury.webp" alt="seguridad" width="80"></a>
      <a title="usuarios" href="usuarios.php"><img src="images/usuarios.webp" alt="usuarios" width="80"></a>
     <?php endif; ?>
      <a title="imprimir" href="imprimir.php"><img src="images/imprimir.webp" alt="imprimir" width="80"></a>
      <a title="logout" href="logout.php"><img src="images/logout.webp" alt="logout" width="80"></a>
    </div>
  
    <br><br><br>

<p><a href="imprimir_caducidades.php"><button class="azul">IMPRIMIR CADUCIDADES</button></a></p>
<p><a href="imprimir_citas.php"><button class="verde">IMPRIMIR CITAS</button></a></p>

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
