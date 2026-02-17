<?php
session_start();

// Administrador y SuperAdministrador
if (
    !isset($_SESSION['usuario']) ||
    !in_array($_SESSION['tipo'], ['Administrador', 'SuperAdministrador'])
) {
    header('Location: login.php');
    exit();
}

$is_admin = isset($_SESSION['tipo']) && in_array($_SESSION['tipo'], ['Administrador', 'SuperAdministrador']);
$is_superadmin = isset($_SESSION['tipo']) && $_SESSION['tipo'] === 'SuperAdministrador';

// Mostrar errores para depuración (temporal)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Archivo de estaciones
$estaciones_file = 'estaciones.json';

// Crear archivo con estaciones por defecto si no existe o está corrupto
if (!file_exists($estaciones_file)) {
    file_put_contents($estaciones_file, json_encode(['Tambre', 'Sionlla', 'Cacheiras'], JSON_PRETTY_PRINT));
}

// Leer estaciones
$estaciones = json_decode(file_get_contents($estaciones_file), true);
if (!is_array($estaciones)) {
    $estaciones = ['Tambre', 'Sionlla', 'Cacheiras'];
}

// Agregar nueva estación
if (isset($_POST['nueva_estacion']) && trim($_POST['nueva_estacion']) !== '') {
    $nueva = trim($_POST['nueva_estacion']);
    if (!in_array($nueva, $estaciones)) {
        $estaciones[] = $nueva;
        file_put_contents($estaciones_file, json_encode($estaciones, JSON_PRETTY_PRINT));
        $mensaje = "Estación '$nueva' agregada correctamente.";
    } else {
        $error = "La estación '$nueva' ya existe.";
    }
}

// Editar estaciones existentes
if (isset($_POST['editar_estaciones']) && isset($_POST['estaciones']) && is_array($_POST['estaciones'])) {
    foreach ($_POST['estaciones'] as $i => $nombre) {
        $estaciones[$i] = trim($nombre);
    }
    file_put_contents($estaciones_file, json_encode($estaciones, JSON_PRETTY_PRINT));
    $mensaje = "Estaciones actualizadas correctamente.";
}

// Eliminar estación
if (isset($_GET['eliminar'])) {
    $index = (int)$_GET['eliminar'];
    if (isset($estaciones[$index])) {
        $eliminada = $estaciones[$index];
        unset($estaciones[$index]);
        $estaciones = array_values($estaciones); // Reindexar
        file_put_contents($estaciones_file, json_encode($estaciones, JSON_PRETTY_PRINT));
        $mensaje = "Estación '$eliminada' eliminada correctamente.";
    } else {
        $error = "Estación no encontrada.";
    }
}

// Cargar versión y autor desde version.xk
$version_file = 'version.xk';
$version_text = '';
$autor_text = '';
if (file_exists($version_file)) {
    $lines = file($version_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $version_text = $lines[0] ?? '';
    $autor_text = $lines[1] ?? '';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Gestionar Estaciones</title>
<link rel="shortcut icon" href="images/logo.webp">
<link rel="icon" sizes="64x64" href="images/logo.webp">
<link rel="apple-touch-icon" sizes="180x180" href="images/logo.webp">
<link rel="stylesheet" href="style.css">
<style>
body { margin:15px; font-family:Arial,sans-serif; }

/* Menú */
.menu { margin-bottom:15px; }
.menu a { margin-right:0px; }
.menu img { width:80px; height:auto; vertical-align:middle; transition:filter 0.3s ease; }
h1 img { vertical-align:middle; }

/* Formulario */
input, select, textarea { padding:5px; margin:2px 0; border-radius:4px; }
input[type="submit"] { cursor:pointer; }

/* Mensajes */
.verde { color:#4CAF50; }
.rojo_intenso { color:#cc0000; }

/* Tablas */
table { border-collapse:collapse; width:100%; }
th, td { border:1px solid #ccc; padding:8px; vertical-align:top; }
th { background:#eee; }
ul { margin:0; padding-left:18px; }

/* ===== BLOQUE USUARIO (igual que página principal) ===== */
.user-info {
    position: fixed;
    top: 10px;
    right: 15px;
    text-align: right;
    font-size: 14px;
    color: inherit;
}

/* PRÓXIMA ITV placeholder para consistencia */
.proxima-itv { display:none; }

/* ===== MODO OSCURO AUTOMÁTICO ===== */
@media (prefers-color-scheme: dark) {
    body { background:#000; color:#ddd; }
    h1,h2,h3,h4,p,strong { color:#ddd; }
    th { background:#222; color:#fff; }
    input, select, textarea { background:#111; color:#fff; border:1px solid #555; }
    input[type="submit"] { background:#222; color:#fff; border:1px solid #666; }
    .menu img:not([alt="Logo"]) { filter: invert(1) hue-rotate(180deg); }
    h1 img { filter:none; }
    .verde { color:#0f0; }
    .rojo_intenso { color:#f33; }
}
</style>
</head>
<body>

<!-- BLOQUE USUARIO -->
<div class="user-info">
    <strong><?= $_SESSION['usuario'] ?> | <?= $_SESSION['tipo'] ?></strong>
    <div id="fecha-hora"></div>
</div>
  
<br>

<h1>
<img src="images/logo.webp" width="30" style="vertical-align: middle;"> Gestionar Estaciones
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

<?php if (isset($mensaje)): ?>
    <p class="verde"><?= htmlspecialchars($mensaje) ?></p>
<?php endif; ?>
<?php if (isset($error)): ?>
    <p class="rojo_intenso"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<h2>Agregar Nueva Estación</h2>
<br>

<form method="POST">
    <input type="text" name="nueva_estacion" placeholder="Nombre de la estación" required>
    <input type="submit" value="Agregar">
</form>
<br><br>

<h2>Editar Estaciones Existentes</h2>
<br>

<form method="POST">
    <?php foreach ($estaciones as $i => $estacion): ?>
        <input type="text" name="estaciones[<?= $i ?>]" value="<?= htmlspecialchars($estacion) ?>" required>
        <a href="?eliminar=<?= $i ?>" onclick="return confirm('¿Seguro que quieres eliminar esta estación?');" class="rojo_intenso">Eliminar</a>
        <br><br>
    <?php endforeach; ?>
    <input type="submit" name="editar_estaciones" value="Guardar Cambios">
</form>

<h4 class="small" style="margin-top:12px; text-align:left;"><?= htmlspecialchars($version_text) ?></h4>
<p class="small" style="text-align:left;"><?= htmlspecialchars($autor_text) ?></p>

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