<?php
session_start();

// Verificar si el usuario ya está logueado
if (isset($_SESSION['usuario'])) {
    header('Location: index.php');
    exit();
}

$usuarios_file = 'usuarios.json';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar si el archivo de usuarios existe
    if (!file_exists($usuarios_file)) {
        die("El archivo de usuarios no existe.");
    }

    // Cargar los usuarios desde el archivo JSON
    $usuarios = json_decode(file_get_contents($usuarios_file), true);

    // Obtener el usuario y la contraseña del formulario
    $usuario_input = $_POST['usuario'];
    $contraseña_input = $_POST['contraseña'];

    // Buscar el usuario en el array de usuarios
    $usuario_encontrado = false;
    foreach ($usuarios as $usuario) {
        if ($usuario['usuario'] === $usuario_input) {
            $usuario_encontrado = true;
            // Verificar la contraseña
            if (password_verify($contraseña_input, $usuario['contraseña'])) {
                // Almacenar información de sesión
                $_SESSION['usuario'] = $usuario['usuario'];
                $_SESSION['tipo'] = $usuario['tipo'];
                // Redirigir al index o a la página principal
                header('Location: index.php');
                exit();
            } else {
                $error = "Contraseña incorrecta.";
                break;
            }
        }
    }

    // Si el usuario no fue encontrado
    if (!$usuario_encontrado) {
        $error = "Usuario no encontrado.";
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
    <title>Iniciar Sesión</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1><img src="images/logo.webp" alt="Logo" width="30" style="vertical-align: middle;">Iniciar Sesión</h1>

    <form method="POST">
        <label>Usuario:</label><input type="text" name="usuario" required><br><br>
        <label>Contraseña:</label><input type="password" name="contraseña" required><br><br>
        <input type="submit" value="Iniciar Sesión">
    </form>

    <?php if (isset($error)): ?>
        <p style="color: red;"><?= $error ?></p>
    <?php endif; ?>

        <h4 class="small" style="margin-top:12px;">ITVControl v.1.3</h4>
        <p class="small">B174M3 // XaeK</p>
</body>
</html>
