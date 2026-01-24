<?php
session_start();

// Verificar que sea administrador
if (!isset($_SESSION['usuario']) || $_SESSION['tipo'] != 'Administrador') {
    header('Location: index.php');
    exit();
}

// Verificar que se reciban fecha y hora de la cita
if (!isset($_GET['fecha']) || !isset($_GET['hora'])) {
    die("ID de cita no válido.");
}

$fecha = $_GET['fecha'];
$hora = $_GET['hora'];

// Cargar citas desde JSON
$citas_file = 'citas.json';
if (!file_exists($citas_file)) {
    die("El archivo de citas no existe.");
}
$citas = json_decode(file_get_contents($citas_file), true);

// Buscar la cita
$cita_encontrada = null;
foreach ($citas as $index => $cita) {
    if ($cita['fecha_cita'] === $fecha && $cita['hora_cita'] === $hora) {
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
        // Eliminar cita
        unset($citas[$cita_encontrada['index']]);
        $citas = array_values($citas); // Reindexar

        if (file_put_contents($citas_file, json_encode($citas, JSON_PRETTY_PRINT))) {
            header('Location: citas.php');
            exit();
        } else {
            $error = "No se pudo eliminar la cita. Verifique los permisos del archivo.";
        }
    } else {
        // Cancelar eliminación
        header('Location: citas.php');
        exit();
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
    <title>Eliminar Cita</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>Eliminar Cita</h1>

    <?php if (isset($error)): ?>
        <p style="color: red;"><?= $error ?></p>
    <?php endif; ?>

    <p>¿Estás seguro de que deseas eliminar la cita del <strong><?= formatear_fecha($cita_encontrada['cita']['fecha_cita']) ?></strong> a las <strong><?= htmlspecialchars($cita_encontrada['cita']['hora_cita']) ?></strong> en la estación <strong><?= htmlspecialchars($cita_encontrada['cita']['estacion_cita']) ?></strong>?</p>

    <form method="POST">
        <button type="submit" name="confirmar" value="sí">Sí, eliminar</button>
        <button type="submit" name="confirmar" value="no">Cancelar</button>
    </form>

</body>
</html>
