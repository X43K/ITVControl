<?php
session_start();

// Verificar si el usuario es administrador
if (!isset($_SESSION['usuario']) || $_SESSION['tipo'] != 'Administrador') {
    header('Location: index.php');
    exit();
}

// Verificar si el archivo usuarios.json existe y es accesible
$usuarios_file = 'usuarios.json';
if (!file_exists($usuarios_file)) {
    die("El archivo de usuarios no existe.");
}

// Cargar usuarios desde el archivo JSON
$usuarios = json_decode(file_get_contents($usuarios_file), true);

// Verificar si el nombre de usuario está presente en la URL
if (!isset($_GET['usuario'])) {
    die("Usuario no especificado.");
}

$usuario_id = $_GET['usuario']; // Usamos el nombre de usuario como identificador

// Buscar el usuario que se va a editar
$usuario_encontrado = false;
foreach ($usuarios as &$usuario) {
    if ($usuario['usuario'] == $usuario_id) {
        $usuario_encontrado = true;
        break;
    }
}

if (!$usuario_encontrado) {
    die("Usuario no encontrado.");
}

// Procesar formulario de edición de usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar que se hayan enviado todos los campos
    if (!empty($_POST['usuario']) && !empty($_POST['contraseña']) && !empty($_POST['tipo'])) {
        $nuevo_usuario = $_POST['usuario'];
        $nueva_contraseña = $_POST['contraseña'];
        $nuevo_tipo = $_POST['tipo'];

        // Actualizar los datos del usuario
        $usuario['usuario'] = $nuevo_usuario;
        $usuario['contraseña'] = password_hash($nueva_contraseña, PASSWORD_DEFAULT); // Encriptar la contraseña
        $usuario['tipo'] = $nuevo_tipo;

        // Guardar el archivo de usuarios actualizado
        if (file_put_contents($usuarios_file, json_encode($usuarios, JSON_PRETTY_PRINT))) {
            // Redirigir para evitar el reenvío del formulario al actualizar la página
            header('Location: usuarios.php');
            exit();
        } else {
            $error = "No se pudo actualizar el usuario. Verifique los permisos del archivo.";
        }
    } else {
        $error = "Todos los campos son obligatorios.";
    }
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <link rel="shortcut icon" href="images/logo.webp">
    <link rel="icon" sizes="64x64" href="images/logo.webp">
    <link rel="apple-touch-icon" sices="180x180" href="images/logo.webp">
    <meta charset="UTF-8">
    <title>Editar Usuario</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>Editar Usuario: <?= htmlspecialchars($usuario['usuario']) ?></h1>

    <div class="menu">
        <a href="index.php">Página Principal</a>
        <a href="vehiculos.php">Gestionar Vehículos</a>
        <a href="citas.php">Gestionar Citas</a>
        <a href="usuarios.php">Gestionar Usuarios</a>
        <a href="logout.php">Cerrar Sesión</a>
    </div>

    <?php if (isset($error)): ?>
        <p style="color: red;"><?= $error ?></p>
    <?php endif; ?>

    <h2>Editar Usuario</h2>
    <form method="POST">
        <label>Usuario:</label><input type="text" name="usuario" value="<?= htmlspecialchars($usuario['usuario']) ?>" required><br><br>
        <label>Contraseña:</label><input type="password" name="contraseña" required><br><br>
        <label>Tipo:</label>
        <select name="tipo">
            <option value="Administrador" <?= $usuario['tipo'] == 'Administrador' ? 'selected' : '' ?>>Administrador</option>
            <option value="Usuario" <?= $usuario['tipo'] == 'Usuario' ? 'selected' : '' ?>>Usuario</option>
        </select><br><br>
        <input type="submit" value="Actualizar Usuario">
    </form>

        <h4 class="small" style="margin-top:12px;">ITVControl v.1.2</h4>
        <p class="small">B174M3 // XaeK</p>
</body>
</html>