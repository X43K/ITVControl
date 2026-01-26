<?php
session_start();

// Verificar si el usuario está logueado y es administrador
if (!isset($_SESSION['usuario']) || $_SESSION['tipo'] != 'Administrador') {
    header('Location: index.php');
    exit();
}

// Obtener fecha y hora de la cita a editar
if (!isset($_GET['fecha']) || !isset($_GET['hora']) || empty($_GET['fecha']) || empty($_GET['hora'])) {
    header('Location: citas.php');
    exit();
}

$fecha_cita = $_GET['fecha'];
$hora_cita = $_GET['hora'];

// Cargar citas desde JSON
$citas_file = 'citas.json';
if (!file_exists($citas_file)) die("El archivo de citas no existe.");
$citas = json_decode(file_get_contents($citas_file), true);

// Buscar la cita a editar
$cita_editar = null;
foreach ($citas as &$cita) {
    if ($cita['fecha_cita'] === $fecha_cita && $cita['hora_cita'] === $hora_cita) {
        $cita_editar = &$cita;
        break;
    }
}
if ($cita_editar === null) die("No se encontró la cita para la fecha y hora proporcionadas.");

// Cargar vehículos desde JSON
$vehiculos_file = 'vehiculos.json';
$vehiculos = json_decode(file_get_contents($vehiculos_file), true);

// Cargar estaciones desde JSON
$estaciones_file = 'estaciones.json';
if (!file_exists($estaciones_file)) file_put_contents($estaciones_file, json_encode(['Tambre','Sionlla','Cacheiras'], JSON_PRETTY_PRINT));
$estaciones = json_decode(file_get_contents($estaciones_file), true);

// Procesar formulario de edición
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['fecha_cita']) && !empty($_POST['hora_cita']) && !empty($_POST['estacion_cita']) && !empty($_POST['tipo_cita'])) {
        $cita_editar['fecha_cita'] = $_POST['fecha_cita'];
        $cita_editar['hora_cita'] = $_POST['hora_cita'];
        $cita_editar['estacion_cita'] = $_POST['estacion_cita'];
        $cita_editar['tipo_cita'] = $_POST['tipo_cita'];
        $cita_editar['vehiculo'] = $_POST['vehiculo'] ?? '';

        // Guardar cambios sin bloquear por alertas, estas se manejan en JS
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
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Editar Cita</title>
<link rel="stylesheet" href="style.css">

<script>
// Función para alertas al editar cita
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

    // Más de 29 días antes (Primera ITV)
    if (tipoCita === 'Primera' && diffDias > 29) {
        if (!confirm("Atención: La cita de Primera ITV está programada " + diffDias + 
            " días antes de la caducidad de la ITV.\n¿Desea continuar?")) {
            return false;
        }
    }

    // Cita después de la caducidad
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
<h1>Editar Cita</h1>

<form method="POST" onsubmit="return validarCita(this);">
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
                <?= htmlspecialchars($vehiculo['matricula']) ?> - <?= htmlspecialchars($vehiculo['vehiculo']) ?>
            </option>
        <?php endforeach; ?>
    </select><br><br>

    <input type="submit" value="Guardar Cambios">
</form>

<?php if (isset($error)): ?>
    <p style="color:red;"><?= $error ?></p>
<?php endif; ?>

        <h4 class="small" style="margin-top:12px;">ITVControl v.1.2</h4>
        <p class="small">B174M3 // XaeK</p>
</body>
</html>