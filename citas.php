<?php
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php'); // redirige a login si no está logueado
    exit();
}

// Comprobar si es administrador
$is_admin = ($_SESSION['tipo'] == 'Administrador');

// Cargar citas desde JSON
$citas_file = 'citas.json';
if (!file_exists($citas_file)) file_put_contents($citas_file, json_encode([]));
$citas = json_decode(file_get_contents($citas_file), true);

// Cargar vehículos desde JSON
$vehiculos_file = 'vehiculos.json';
if (!file_exists($vehiculos_file)) die("El archivo de vehículos no existe.");
$vehiculos = json_decode(file_get_contents($vehiculos_file), true);

// Cargar estaciones desde JSON
$estaciones_file = 'estaciones.json';
if (!file_exists($estaciones_file)) file_put_contents($estaciones_file, json_encode(['Tambre','Sionlla','Cacheiras'], JSON_PRETTY_PRINT));
$estaciones = json_decode(file_get_contents($estaciones_file), true);

// Procesar formulario de añadir cita solo si es administrador
if ($is_admin && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fecha_cita'])) {
    if (!empty($_POST['fecha_cita']) && !empty($_POST['hora_cita']) && !empty($_POST['estacion_cita'])) {
        $nueva_cita = [
            'fecha_cita' => $_POST['fecha_cita'],
            'hora_cita' => $_POST['hora_cita'],
            'estacion_cita' => $_POST['estacion_cita'],
            'tipo_cita' => $_POST['tipo_cita'],
            'vehiculo' => $_POST['vehiculo'] ?? ''
        ];

        // Validar vehículo
        if (!empty($nueva_cita['vehiculo'])) {
            $vehiculo_encontrado = false;
            foreach ($vehiculos as $vehiculo) {
                if ($vehiculo['matricula'] === $nueva_cita['vehiculo']) {
                    $vehiculo_encontrado = true;
                    $caducidad_itv = strtotime($vehiculo['caducidad_itv']);
                    $fecha_cita = strtotime($nueva_cita['fecha_cita']);
                    break;
                }
            }
            if (!$vehiculo_encontrado) $error = "Vehículo no encontrado.";
        }

        if (empty($error)) {
            $citas[] = $nueva_cita;
            if (file_put_contents($citas_file, json_encode($citas, JSON_PRETTY_PRINT))) {
                header('Location: citas.php');
                exit();
            } else {
                $error = "No se pudo guardar la cita. Verifique los permisos del archivo.";
            }
        }
    } else {
        $error = "Todos los campos son obligatorios.";
    }
}

// Función para formatear fecha en DD/MM/YYYY
function formatear_fecha($fecha) {
    $fecha_obj = DateTime::createFromFormat('Y-m-d', $fecha);
    return $fecha_obj ? $fecha_obj->format('d/m/Y') : $fecha;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Gestionar Citas</title>
<link rel="stylesheet" href="style.css">

<script>
// Función para mostrar alertas al añadir cita
function validarCita(form) {
    var fechaCita = new Date(form.fecha_cita.value);
    var tipoCita = form.tipo_cita.value;
    var vehiculo = form.vehiculo.value;

    if (vehiculo === '') return true; // Si no hay vehículo asignado, no hay alerta

    var caducidades = {
        <?php foreach ($vehiculos as $v) {
            echo "'" . $v['matricula'] . "':'" . $v['caducidad_itv'] . "',";
        } ?>
    };

    if (!caducidades[vehiculo]) return true;

    var caducidadItv = new Date(caducidades[vehiculo]);
    var diffTime = caducidadItv - fechaCita;
    var diffDias = Math.floor(diffTime / (1000*60*60*24));

    // Alerta si cita es más de 29 días antes de caducidad
    if (tipoCita === 'Primera' && diffDias > 29) {
        if (!confirm("Atención: La cita de Primera ITV está programada " + diffDias + 
            " días antes de la caducidad de la ITV.\n¿Desea continuar?")) {
            return false;
        }
    }

    // Alerta si cita es después de caducidad
    if (diffDias < 0) {
        if (!confirm("Atención: La cita se asigna después de la caducidad de la ITV (" + 
            caducidadItv.toLocaleDateString() + ").\n¿Desea continuar igualmente?")) {
            return false;
        }
    }

    return true; // Todo ok, enviar formulario
}
</script>
</head>
<body>
<h1><img src="logo.webp" alt="Logo" width="25" style="vertical-align: middle;">Gestionar Citas de ITV</h1>

    <div class="menu">
        <a href="index.php">Página Principal</a>
        <a href="citas.php">Gestionar Citas</a>
        <a href="vehiculos.php">Gestionar Vehículos</a>
        <?php if (isset($_SESSION['tipo']) && $_SESSION['tipo'] === 'Administrador'): ?>
            <a href="estaciones.php">Gestionar Estaciones</a>
            <a href="usuarios.php">Gestionar Usuarios</a>
        <?php endif; ?>
        <a href="logout.php">Cerrar Sesión</a>
    </div>

<?php if (isset($error)): ?>
    <p style="color:red;"><?= $error ?></p>
<?php endif; ?>

<?php if ($is_admin): ?>
<h2>Añadir Cita</h2>
<form method="POST" onsubmit="return validarCita(this);">
    <label>Fecha de Cita:</label>
    <input type="date" name="fecha_cita" required><br><br>
    <label>Hora de Cita:</label>
    <input type="time" name="hora_cita" required><br><br>
    <label>Estación:</label>
    <select name="estacion_cita" required>
        <?php foreach ($estaciones as $estacion): ?>
            <option value="<?= htmlspecialchars($estacion) ?>"><?= htmlspecialchars($estacion) ?></option>
        <?php endforeach; ?>
    </select><br><br>
    <label>Tipo de Cita:</label>
    <select name="tipo_cita">
        <option value="Primera">Primera</option>
        <option value="Segunda">Segunda</option>
    </select><br><br>
    <label>Vehículo (Opcional):</label>
    <select name="vehiculo">
        <option value="">Sin asignar</option>
        <?php foreach ($vehiculos as $vehiculo): ?>
            <option value="<?= htmlspecialchars($vehiculo['matricula']) ?>">
                <?= htmlspecialchars($vehiculo['matricula']) ?> - <?= htmlspecialchars($vehiculo['vehiculo']) ?>
            </option>
        <?php endforeach; ?>
    </select><br><br>
    <input type="submit" value="Añadir Cita">
</form>
<?php endif; ?>

<h2>Lista de Citas</h2>
<table>
    <thead>
        <tr>
            <th>Fecha</th>
            <th>Hora</th>
            <th>Estación</th>
            <th>Tipo</th>
            <th>Vehículo</th>
            <?php if ($is_admin): ?><th>Acciones</th><?php endif; ?>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($citas as $cita): ?>
        <tr>
            <td><?= formatear_fecha($cita['fecha_cita']) ?></td>
            <td><?= htmlspecialchars($cita['hora_cita']) ?></td>
            <td><?= htmlspecialchars($cita['estacion_cita']) ?></td>
            <td><?= htmlspecialchars($cita['tipo_cita']) ?></td>
            <td><?= htmlspecialchars($cita['vehiculo']) ?></td>
            <?php if ($is_admin): ?>
            <td>
                <a href="editar_cita.php?fecha=<?= urlencode($cita['fecha_cita']) ?>&hora=<?= urlencode($cita['hora_cita']) ?>">Editar</a> |
                <a href="eliminar_cita.php?fecha=<?= urlencode($cita['fecha_cita']) ?>&hora=<?= urlencode($cita['hora_cita']) ?>">Eliminar</a>
            </td>
            <?php endif; ?>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

</body>
</html>
