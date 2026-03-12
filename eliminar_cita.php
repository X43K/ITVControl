<?php
session_start();

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
            header('Location: citas.php');
            exit();
        } else {
            $error = "No se pudo eliminar la cita. Verifique los permisos del archivo.";
        }
    } else {
        header('Location: citas.php');
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
