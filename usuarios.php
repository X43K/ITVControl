<?php
session_start();

// Verificar si el usuario está logueado y es administrador
if (!isset($_SESSION['usuario']) || $_SESSION['tipo'] != 'Administrador') {
    header('Location: index.php');
    exit();
}

$is_admin = ($_SESSION['tipo'] == 'Administrador');

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
    <link rel="shortcut icon" href="images/logo.webp">
    <link rel="icon" sizes="64x64" href="images/logo.webp">
    <link rel="apple-touch-icon" sices="180x180" href="images/logo.webp">
    <meta charset="UTF-8">
    <title>Gestionar Usuarios</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1><img src="images/logo.webp" alt="Logo" width="30" style="vertical-align: middle;">Gestionar Usuarios</h1>

<div class="menu">
    <a title="index" href="index.php"><img src="images/index.webp" alt="index" width="80" style="vertical-align: middle;"></a>
    <a title="citas" href="citas.php"><img src="images/citas.webp" alt="citas" width="80" style="vertical-align: middle;"></a>
    <a title="vehiculos" href="vehiculos.php"><img src="images/vehiculos.webp" alt="vehiculos" width="80" style="vertical-align: middle;"></a>
        <?php if ($is_admin): ?>
    <a title="estaciones" href="estaciones.php"><img src="images/estaciones.webp" alt="estaciones" width="80" style="vertical-align: middle;"></a>
    <a title="usuarios" href="usuarios.php"><img src="images/usuarios.webp" alt="usuarios" width="80" style="vertical-align: middle;"></a>
        <?php endif; ?>
    <a title="imprimir" href="imprimir.php"><img src="images/imprimir.webp" alt="imprimir" width="80" style="vertical-align: middle;"></a>
    <a title="logout" href="logout.php"><img src="images/logout.webp" alt="logout" width="80" style="vertical-align: middle;"></a>
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

        <h4 class="small" style="margin-top:12px;">ITVControl v.1.2</h4>
        <p class="small">B174M3 // XaeK</p>
</body>
</html>
