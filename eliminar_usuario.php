<?php
session_start();

// Verificar si el usuario es administrador
if (!isset($_SESSION['usuario']) || $_SESSION['tipo'] != 'Administrador') {
    header('Location: index.php');
    exit();
}

// Verificar que el archivo usuarios.json exista
$usuarios_file = 'usuarios.json';
if (!file_exists($usuarios_file)) {
    die("El archivo de usuarios no existe.");
}

// Cargar usuarios desde JSON
$usuarios = json_decode(file_get_contents($usuarios_file), true);

// Verificar que se reciba el usuario por GET
if (!isset($_GET['usuario'])) {
    die("Usuario no especificado.");
}

$usuario_id = $_GET['usuario']; // Nombre de usuario como identificador

// Buscar el usuario en el array
$usuario_encontrado = null;
foreach ($usuarios as $index => $usuario) {
    if ($usuario['usuario'] == $usuario_id) {
        $usuario_encontrado = ['index' => $index, 'usuario' => $usuario];
        break;
    }
}

if (!$usuario_encontrado) {
    die("Usuario no encontrado.");
}

// Procesar confirmación
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['confirmar']) && $_POST['confirmar'] === 'sí') {
        // Eliminar usuario
        unset($usuarios[$usuario_encontrado['index']]);
        $usuarios = array_values($usuarios); // Reindexar

        if (file_put_contents($usuarios_file, json_encode($usuarios, JSON_PRETTY_PRINT))) {
            header('Location: usuarios.php');
            exit();
        } else {
            $error = "No se pudo eliminar el usuario. Verifique los permisos del archivo.";
        }
    } else {
        // Cancelar eliminación
        header('Location: usuarios.php');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Eliminar Usuario</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>Eliminar Usuario</h1>

    <?php if (isset($error)): ?>
        <p style="color: red;"><?= $error ?></p>
    <?php endif; ?>

    <p>¿Estás seguro de que deseas eliminar al usuario <strong><?= htmlspecialchars($usuario_encontrado['usuario']['usuario']) ?></strong>?</p>

    <form method="POST">
        <button type="submit" name="confirmar" value="sí">Sí, eliminar</button>
        <button type="submit" name="confirmar" value="no">Cancelar</button>
    </form>

</body>
</html>
