<?php
session_start();

// Verificar si el usuario es administrador
if (!isset($_SESSION['usuario']) || $_SESSION['tipo'] != 'Administrador') {
    header('Location: index.php');
    exit();
}

// Obtener matrícula del vehículo a editar
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: vehiculos.php');
    exit();
}

$id_vehiculo = $_GET['id'];

// Cargar vehículos desde el archivo JSON
$vehiculos_file = 'vehiculos.json';
if (!file_exists($vehiculos_file)) {
    die("El archivo de vehículos no existe.");
}

$vehiculos = json_decode(file_get_contents($vehiculos_file), true);

// Buscar el vehículo a editar por su matrícula
$vehiculo_editar = null;
foreach ($vehiculos as &$vehiculo) {
    if ($vehiculo['matricula'] === $id_vehiculo) {
        $vehiculo_editar = &$vehiculo;
        break;
    }
}

if ($vehiculo_editar === null) {
    die("No se encontró el vehículo con matrícula: " . $id_vehiculo);
}

// Procesar formulario de edición
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar que los campos no estén vacíos
    if (!empty($_POST['vehiculo']) && !empty($_POST['matricula']) && !empty($_POST['estado']) && !empty($_POST['caducidad_itv']) && !empty($_POST['tipo'])) {
        $vehiculo_editar['vehiculo'] = $_POST['vehiculo'];
        $vehiculo_editar['matricula'] = $_POST['matricula'];
        $vehiculo_editar['tipo'] = $_POST['tipo']; // Guardar tipo
        $vehiculo_editar['estado'] = $_POST['estado'];
        $vehiculo_editar['caducidad_itv'] = $_POST['caducidad_itv'];

        // Guardar el array de vehículos actualizado en el archivo JSON
        if (file_put_contents($vehiculos_file, json_encode($vehiculos, JSON_PRETTY_PRINT))) {
            // Redirigir a la página de vehículos después de editar
            header('Location: vehiculos.php');
            exit();
        } else {
            $error = "No se pudo guardar los cambios. Verifique los permisos del archivo.";
        }
    } else {
        $error = "Todos los campos son obligatorios.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Vehículo</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>Editar Vehículo</h1>

    <form method="POST">
        <label>Vehículo:</label>
        <input type="text" name="vehiculo" 
        value="<?= htmlspecialchars($vehiculo_editar['vehiculo']) ?>" 
        readonly required><br><br>


        <label>Matrícula:</label>
        <input type="text" name="matricula" 
        value="<?= htmlspecialchars($vehiculo_editar['matricula']) ?>" 
        readonly required><br><br>


        <label>Estado:</label>
        <select name="estado">
            <option value="ACTIVO" <?= $vehiculo_editar['estado'] === 'ACTIVO' ? 'selected' : '' ?>>ACTIVO</option>
            <option value="ITV RECHAZADA" <?= $vehiculo_editar['estado'] === 'ITV RECHAZADA' ? 'selected' : '' ?>>ITV RECHAZADA</option>
            <option value="BAJA" <?= $vehiculo_editar['estado'] === 'BAJA' ? 'selected' : '' ?>>BAJA</option>
        </select><br><br>

        <label>Caducidad ITV:</label>
        <input type="date" name="caducidad_itv" value="<?= htmlspecialchars($vehiculo_editar['caducidad_itv']) ?>" required><br><br>

        <input type="submit" value="Guardar Cambios">
    </form>

    <?php if (isset($error)): ?>
        <p style="color: red;"><?= $error ?></p>
    <?php endif; ?>

        <h4 class="small" style="margin-top:12px;">ITVControl v.1.2</h4>
        <p class="small">B174M3 // XaeK</p>
</body>
</html>