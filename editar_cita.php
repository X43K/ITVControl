<?php
session_start();

// Verificar si el usuario está logueado y es administrador o superadministrador
if (
    !isset($_SESSION['usuario']) ||
    !in_array($_SESSION['tipo'], ['Administrador', 'SuperAdministrador'])
) {
    header('Location: index.php');
    exit();
}

// Variables de control para el menú
$is_admin = in_array($_SESSION['tipo'], ['Administrador', 'SuperAdministrador']);
$is_superadmin = $_SESSION['tipo'] === 'SuperAdministrador';

// Obtener ID de la cita a editar
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: citas.php');
    exit();
}

$id_cita = $_GET['id'];

// Cargar citas desde JSON
$citas_file = 'citas.json';
if (!file_exists($citas_file)) die("El archivo de citas no existe.");
$citas = json_decode(file_get_contents($citas_file), true);

// Buscar la cita a editar
$cita_editar = null;
foreach ($citas as &$cita) {
    if (isset($cita['id_cita']) && $cita['id_cita'] === $id_cita) {
        $cita_editar = &$cita;
        break;
    }
}
if ($cita_editar === null) die("No se encontró la cita con el ID proporcionado.");

// Cargar vehículos desde JSON
$vehiculos_file = 'vehiculos.json';
$vehiculos = json_decode(file_get_contents($vehiculos_file), true);

// Ordenar vehículos alfabéticamente (orden natural)
usort($vehiculos, function ($a, $b) {
    return strnatcasecmp($a['vehiculo'], $b['vehiculo']);
});

// Cargar estaciones desde JSON
$estaciones_file = 'estaciones.json';
if (!file_exists($estaciones_file)) {
    file_put_contents($estaciones_file, json_encode(['Tambre','Sionlla','Cacheiras'], JSON_PRETTY_PRINT));
}
$estaciones = json_decode(file_get_contents($estaciones_file), true);

// Procesar formulario de edición
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['fecha_cita']) && !empty($_POST['hora_cita']) && !empty($_POST['estacion_cita']) && !empty($_POST['tipo_cita'])) {
        $cita_editar['fecha_cita'] = $_POST['fecha_cita'];
        $cita_editar['hora_cita'] = $_POST['hora_cita'];
        $cita_editar['estacion_cita'] = $_POST['estacion_cita'];
        $cita_editar['tipo_cita'] = $_POST['tipo_cita'];
        $cita_editar['vehiculo'] = $_POST['vehiculo'] ?? '';

        if (file_put_contents($citas_file, json_encode($citas, JSON_PRETTY_PRINT))) {
            header('Location: citas.php');
            exit();
        } else {
            $error = "No se pudo guardar la cita. Verifique los permisos del archivo.";
        }
    } else {
        $error = "Todos los campos son obligatorios.";
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
<title>Editar Cita</title>
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
input, select { padding:5px; margin:2px 0; border-radius:4px; }
input[type="submit"] { cursor:pointer; }

/* Mensajes de error */
.rojo_intenso { color:#cc0000; }

/* ===== MODO OSCURO AUTOMÁTICO ===== */
@media (prefers-color-scheme: dark) {
    body { background:#000; color:#ddd; }
    h1,h2,h3,h4,p,strong { color:#ddd; }
    input, select { background:#111; color:#fff; border:1px solid #555; }
    input[type="submit"] { background:#222; color:#fff; border:1px solid #666; }
    .menu img:not([alt="Logo"]) { filter: invert(1) hue-rotate(180deg); }
    h1 img { filter:none; }
    .rojo_intenso { color:#f33; }
}
</style>

<script>
function validarCita(form) {
    var fechaCita = new Date(form.fecha_cita.value);
    var tipoCita = form.tipo_cita.value;
    var vehiculo = form.vehiculo.value;

    if (vehiculo === '') return true;

    var caducidades = {
        <?php foreach ($vehiculos as $v) {
            echo "'" . $v['matricula'] . "':'" . $v['caducidad_itv'] . "',";
        } ?>
    };

    if (!caducidades[vehiculo]) return true;

    var caducidadItv = new Date(caducidades[vehiculo]);
    var diffTime = caducidadItv - fechaCita;
    var diffDias = Math.floor(diffTime / (1000*60*60*24));

    if (tipoCita === 'Primera' && diffDias > 29) {
        if (!confirm("Atención: La cita de Primera ITV está programada " + diffDias +
            " días antes de la caducidad de la ITV.\n¿Desea continuar?")) {
            return false;
        }
    }

    if (diffDias < 0) {
        if (!confirm("Atención: La cita se asigna después de la caducidad de la ITV (" +
            caducidadItv.toLocaleDateString() + ").\n¿Desea continuar igualmente?")) {
            return false;
        }
    }

    return true;
}
</script>
</head>

<body>
<div class="user-info" style="position:fixed;top:10px;right:15px;text-align:right;font-size:14px;">
    <strong><?= htmlspecialchars($_SESSION['usuario']) ?> | <?= htmlspecialchars($_SESSION['tipo']) ?></strong>
    <div id="fecha-hora"></div>
</div>
<br>

<h1><img src="images/logo.webp" alt="Logo" width="30" style="vertical-align: middle;"> Editar Cita</h1>
<br>

<div class="menu">
    <a title="index" href="index.php"><img src="images/index.webp" alt="index" width="80"></a>
    <a title="citas" href="citas.php"><img src="images/citas.webp" alt="citas" width="80"></a>
    <a title="vehiculos" href="vehiculos.php"><img src="images/vehiculos.webp" alt="vehiculos" width="80"></a>
    <?php if ($is_admin): ?>
        <a title="estaciones" href="estaciones.php"><img src="images/estaciones.webp" alt="estaciones" width="80"></a>
    <?php endif; ?>
    <?php if ($is_superadmin): ?>
        <a title="usuarios" href="usuarios.php"><img src="images/usuarios.webp" alt="usuarios" width="80"></a>
    <?php endif; ?>
    <a title="imprimir" href="imprimir.php"><img src="images/imprimir.webp" alt="imprimir" width="80"></a>
    <a title="logout" href="logout.php"><img src="images/logout.webp" alt="logout" width="80"></a>
</div>

<br>

<form method="POST" onsubmit="return validarCita(this);">
    <label><strong>ID de Cita:</strong> <?= htmlspecialchars($cita_editar['id_cita']) ?></label><br><br>

    <label>Fecha de Cita:</label>
    <input type="date" name="fecha_cita" value="<?= htmlspecialchars($cita_editar['fecha_cita']) ?>" required><br><br>

    <label>Hora de Cita:</label>
    <input type="time" name="hora_cita" value="<?= htmlspecialchars($cita_editar['hora_cita']) ?>" required><br><br>

    <label>Estación:</label>
    <select name="estacion_cita" required>
        <?php foreach ($estaciones as $estacion): ?>
            <option value="<?= htmlspecialchars($estacion) ?>" <?= $cita_editar['estacion_cita'] === $estacion ? 'selected' : '' ?>>
                <?= htmlspecialchars($estacion) ?>
            </option>
        <?php endforeach; ?>
    </select><br><br>

    <label>Tipo de Cita:</label>
    <select name="tipo_cita">
        <option value="Primera" <?= $cita_editar['tipo_cita']==='Primera'?'selected':'' ?>>Primera</option>
        <option value="Segunda" <?= $cita_editar['tipo_cita']==='Segunda'?'selected':'' ?>>Segunda</option>
    </select><br><br>

    <label>Vehículo (Opcional):</label>
    <select name="vehiculo">
        <option value="">Sin asignar</option>
        <?php foreach ($vehiculos as $vehiculo): ?>
            <option value="<?= htmlspecialchars($vehiculo['matricula']) ?>" <?= $cita_editar['vehiculo']===$vehiculo['matricula']?'selected':'' ?>>
                <?= htmlspecialchars($vehiculo['vehiculo']) ?> - <?= htmlspecialchars($vehiculo['matricula']) ?>
            </option>
        <?php endforeach; ?>
    </select><br><br>

    <input type="submit" value="Guardar Cambios">
</form>

<?php if (isset($error)): ?>
    <p class="rojo_intenso"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<h4 class="small" style="margin-top:12px; text-align:left;"><?= htmlspecialchars($version_text) ?></h4>
<p class="small" style="text-align:left;"><?= htmlspecialchars($autor_text) ?></p>

</body>
</html>