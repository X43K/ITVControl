<?php
session_name('ITVCONTROL_SESSID');
session_start();

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
            header('Location: vehiculos.php');
            exit();
        } else {
            $error = "No se pudo eliminar el vehículo. Verifique los permisos del archivo.";
        }
    } else {
        header('Location: vehiculos.php');
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