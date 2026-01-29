<?php
session_start();

// Verificar si el usuario está logueado y tiene permisos
if (!isset($_SESSION['usuario']) || !in_array($_SESSION['tipo'], ['SuperAdministrador'])) {
    header('Location: index.php');
    exit();
}

// Variables para menú
$is_admin = isset($_SESSION['tipo']) && in_array($_SESSION['tipo'], ['Administrador', 'SuperAdministrador']);
$is_superadmin = isset($_SESSION['tipo']) && $_SESSION['tipo'] === 'SuperAdministrador';

// Cargar usuarios
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

        $usuarios[] = $nuevo_usuario;
        file_put_contents($usuarios_file, json_encode($usuarios, JSON_PRETTY_PRINT));

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
    <meta charset="UTF-8">
    <title>Gestionar Usuarios</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<h1><img src="images/logo.webp" width="30" style="vertical-align: middle;">Gestionar Usuarios</h1>

<div class="menu">
    <a href="index.php"><img src="images/index.webp" width="80"></a>
    <a href="citas.php"><img src="images/citas.webp" width="80"></a>
    <a href="vehiculos.php"><img src="images/vehiculos.webp" width="80"></a>

    <?php if ($is_admin): ?>
        <a href="estaciones.php"><img src="images/estaciones.webp" width="80"></a>
    <?php endif; ?>

    <?php if ($is_superadmin): ?>
        <a href="usuarios.php"><img src="images/usuarios.webp" width="80"></a>
    <?php endif; ?>

    <a href="imprimir.php"><img src="images/imprimir.webp" width="80"></a>
    <a href="logout.php"><img src="images/logout.webp" width="80"></a>
</div>

<?php if (isset($error)): ?>
    <p style="color:red;"><?= $error ?></p>
<?php endif; ?>

<h2>Añadir Usuario</h2>
<form method="POST">
    <label>Usuario:</label><input type="text" name="usuario" required><br><br>
    <label>Contraseña:</label><input type="password" name="contraseña" required><br><br>
    <label>Tipo:</label>
    <select name="tipo">
        <option value="Usuario">Usuario</option>
        <option value="Colaborador">Colaborador</option>
        <option value="Administrador">Administrador</option>
        <option value="SuperAdministrador">SuperAdministrador</option>
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

<h4 class="small" style="margin-top:12px;">ITVControl v.1.3</h4>
<p class="small">B174M3 // XaeK</p>
</body>
</html>

