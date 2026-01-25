<?php
session_start();

// Solo administradores
if (!isset($_SESSION['usuario']) || $_SESSION['tipo'] != 'Administrador') {
    header('Location: login.php');
    exit();
}

$is_admin = ($_SESSION['tipo'] == 'Administrador');

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
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestionar Estaciones</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1><img src="images/logo.webp" alt="Logo" width="30" style="vertical-align: middle;">Gestionar Estaciones</h1>

<div class="menu">
    <a title="index" href="index.php"><img src="images/index.webp" alt="index" width="40" style="vertical-align: middle;"></a>
    <a title="citas" href="citas.php"><img src="images/citas.webp" alt="citas" width="40" style="vertical-align: middle;"></a>
    <a title="vehiculos" href="vehiculos.php"><img src="images/vehiculos.webp" alt="vehiculos" width="40" style="vertical-align: middle;"></a>
        <?php if ($is_admin): ?>
    <a title="estaciones" href="estaciones.php"><img src="images/estaciones.webp" alt="estaciones" width="40" style="vertical-align: middle;"></a>
    <a title="usuarios" href="usuarios.php"><img src="images/usuarios.webp" alt="usuarios" width="40" style="vertical-align: middle;"></a>
        <?php endif; ?>
    <a title="imprimir" href="imprimir.php"><img src="images/imprimir.webp" alt="imprimir" width="40" style="vertical-align: middle;"></a>
    <a title="logout" href="logout.php"><img src="images/logout.webp" alt="logout" width="40" style="vertical-align: middle;"></a>
</div>

    <?php if (isset($mensaje)): ?>
        <p style="color: green;"><?= htmlspecialchars($mensaje) ?></p>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <p style="color: red;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <h2>Agregar Nueva Estación</h2>
    <form method="POST">
        <input type="text" name="nueva_estacion" placeholder="Nombre de la estación" required>
        <input type="submit" value="Agregar">
    </form>

    <h2>Editar Estaciones Existentes</h2>
    <form method="POST">
        <?php foreach ($estaciones as $i => $estacion): ?>
            <input type="text" name="estaciones[<?= $i ?>]" value="<?= htmlspecialchars($estacion) ?>" required>
            <a href="?eliminar=<?= $i ?>" onclick="return confirm('¿Seguro que quieres eliminar esta estación?');">Eliminar</a>
            <br><br>
        <?php endforeach; ?>
        <input type="submit" name="editar_estaciones" value="Guardar Cambios">
    </form>

        <h4 class="small" style="margin-top:12px;">ITVControl v.1.1</h4>
        <p class="small">B174M3 // XaeK</p>
</body>
</html>
