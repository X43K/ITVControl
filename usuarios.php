<?php
session_start();

// Verificar si el usuario está logueado y es administrador
if (!isset($_SESSION['usuario']) || $_SESSION['tipo'] != 'Administrador') {
    header('Location: index.php');
    exit();
}

// Cargar usuarios desde el archivo JSON
$usuarios_file = 'usuarios.json';
if (!file_exists($usuarios_file)) {
    file_put_contents($usuarios_file, json_encode([])); // Crear archivo vacío si no existe
}

$usuarios = json_decode(file_get_contents($usuarios_file), true);

// Procesar formulario de añadir usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['usuario']) && !empty($_POST['contraseña']) && !empty($_POST['tipo'])) {
        $nuevo_usuario = [
            'usuario' => $_POST['usuario'],
            'contraseña' => password_hash($_POST['contraseña'], PASSWORD_DEFAULT),
            'tipo' => $_POST['tipo']
        ];

        // Añadir el nuevo usuario al array de usuarios
        $usuarios[] = $nuevo_usuario;

        // Guardar el array de usuarios actualizado en el archivo JSON
        file_put_contents($usuarios_file, json_encode($usuarios, JSON_PRETTY_PRINT));

        // Redirigir a la misma página para evitar que se resuba el formulario
        header('Location: usuarios.php');
        exit();
    } else {
        $error = "Todos los campos son obligatorios.";
    }
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestionar Usuarios</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1><img src="logo.webp" alt="Logo" width="25" style="vertical-align: middle;">Gestionar Usuarios</h1>

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
        <p style="color: red;"><?= $error ?></p>
    <?php endif; ?>

    <h2>Añadir Usuario</h2>
    <form method="POST">
        <label>Usuario:</label><input type="text" name="usuario" required><br><br>
        <label>Contraseña:</label><input type="password" name="contraseña" required><br><br>
        <label>Tipo:</label>
        <select name="tipo">
            <option value="Usuario">Usuario</option>
            <option value="Administrador">Administrador</option>
        </select><br><br>
        <input type="submit" value="Añadir Usuario">
    </form>

    <h2>Lista de Usuarios</h2>
    <table>
        <thead>
            <tr>
                <th>Usuario</th>
                <th>Tipo</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($usuarios as $usuario): ?>
                <tr>
                    <td><?= htmlspecialchars($usuario['usuario']) ?></td>
                    <td><?= htmlspecialchars($usuario['tipo']) ?></td>
                    <td>
                        <a href="editar_usuario.php?usuario=<?= urlencode($usuario['usuario']) ?>">Editar</a> |
                        <a href="eliminar_usuario.php?usuario=<?= urlencode($usuario['usuario']) ?>">Eliminar</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
