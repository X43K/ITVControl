<?php
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
        // Eliminar el vehículo
        unset($vehiculos[$vehiculo_encontrado['index']]);
        $vehiculos = array_values($vehiculos); // Reindexar array

        if (file_put_contents($vehiculos_file, json_encode($vehiculos, JSON_PRETTY_PRINT))) {
            header('Location: vehiculos.php');
            exit();
        } else {
            $error = "No se pudo eliminar el vehículo. Verifique los permisos del archivo.";
        }
    } else {
        // Si el usuario cancela
        header('Location: vehiculos.php');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Eliminar Vehículo</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>Eliminar Vehículo</h1>

    <?php if (isset($error)): ?>
        <p style="color: red;"><?= $error ?></p>
    <?php endif; ?>

    <p>¿Estás seguro de que deseas eliminar el vehículo <strong><?= htmlspecialchars($vehiculo_encontrado['vehiculo']['vehiculo']) ?></strong> con matrícula <strong><?= htmlspecialchars($vehiculo_encontrado['vehiculo']['matricula']) ?></strong>?</p>

    <form method="POST">
        <button type="submit" name="confirmar" value="sí">Sí, eliminar</button>
        <button type="submit" name="confirmar" value="no">Cancelar</button>
    </form>

</body>
</html>
