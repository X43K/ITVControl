<?php
session_start();

// Verificar si el usuario está logueado y tiene permisos (Administrador, SuperAdministrador o Colaborador)
if (!isset($_SESSION['usuario']) || !in_array($_SESSION['tipo'], ['Administrador', 'SuperAdministrador', 'Colaborador'])) {
    header('Location: index.php');
    exit();
}

// Variables para menú
$is_admin = isset($_SESSION['tipo']) && in_array($_SESSION['tipo'], ['Administrador', 'SuperAdministrador']);
$is_superadmin = isset($_SESSION['tipo']) && $_SESSION['tipo'] === 'SuperAdministrador';

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
    die("No se encontró el vehículo con matrícula: " . htmlspecialchars($id_vehiculo));
}

// Procesar formulario de edición
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['vehiculo']) && !empty($_POST['matricula']) && !empty($_POST['estado']) && !empty($_POST['caducidad_itv'])) {
        $vehiculo_editar['vehiculo'] = $_POST['vehiculo'];
        $vehiculo_editar['matricula'] = $_POST['matricula'];
        $vehiculo_editar['estado'] = $_POST['estado'];
        $vehiculo_editar['caducidad_itv'] = $_POST['caducidad_itv'];

        if (file_put_contents($vehiculos_file, json_encode($vehiculos, JSON_PRETTY_PRINT))) {
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
    <link rel="shortcut icon" href="images/logo.webp">
    <link rel="icon" sizes="64x64" href="images/logo.webp">
    <link rel="apple-touch-icon" sizes="180x180" href="images/logo.webp">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<h1>Editar Vehículo</h1>

<div class="menu">
    <a href="index.php"><img src="images/index.webp" width="80" alt="index"></a>
    <a href="citas.php"><img src="images/citas.webp" width="80" alt="citas"></a>
    <a href="vehiculos.php"><img src="images/vehiculos.webp" width="80" alt="vehiculos"></a>

    <?php if ($is_admin): ?>
        <a href="estaciones.php"><img src="images/estaciones.webp" width="80" alt="estaciones"></a>
    <?php endif; ?>

    <?php if ($is_superadmin): ?>
        <a href="usuarios.php"><img src="images/usuarios.webp" width="80" alt="usuarios"></a>
    <?php endif; ?>

    <a href="imprimir.php"><img src="images/imprimir.webp" width="80" alt="imprimir"></a>
    <a href="logout.php"><img src="images/logout.webp" width="80" alt="logout"></a>
</div>
<p></p>

<form method="POST">
    <label>Vehículo:</label>
    <input type="text" name="vehiculo" value="<?= htmlspecialchars($vehiculo_editar['vehiculo']) ?>" readonly required><br><br>

    <label>Matrícula:</label>
    <input type="text" name="matricula" value="<?= htmlspecialchars($vehiculo_editar['matricula']) ?>" readonly required><br><br>

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
    <p style="color:red;"><?= $error ?></p>
<?php endif; ?>

<h4 class="small" style="margin-top:12px;">ITVControl v.1.3</h4>
<p class="small">B174M3 // XaeK</p>
</body>
</html>
