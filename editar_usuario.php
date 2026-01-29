<?php
session_start();

// Verificar si el usuario es Administrador o SuperAdministrador
if (
    !isset($_SESSION['usuario']) ||
    !in_array($_SESSION['tipo'], ['Administrador', 'SuperAdministrador'])
) {
    header('Location: index.php');
    exit();
}

// Variables de control para el menú
$is_admin = in_array($_SESSION['tipo'], ['Administrador', 'SuperAdministrador']);
$is_superadmin = $_SESSION['tipo'] === 'SuperAdministrador';

// Verificar si el archivo usuarios.json existe
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

$usuario_id = $_GET['usuario'];

// Buscar el usuario que se va a editar
$usuario_encontrado = false;
foreach ($usuarios as &$usuario) {
    if ($usuario['usuario'] === $usuario_id) {
        $usuario_encontrado = true;
        break;
    }
}

if (!$usuario_encontrado) {
    die("Usuario no encontrado.");
}

// Procesar formulario de edición
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['usuario']) && !empty($_POST['contraseña']) && !empty($_POST['tipo'])) {
        $nuevo_usuario = $_POST['usuario'];
        $nueva_contraseña = $_POST['contraseña'];
        $nuevo_tipo = $_POST['tipo'];

        $usuario['usuario'] = $nuevo_usuario;
        $usuario['contraseña'] = password_hash($nueva_contraseña, PASSWORD_DEFAULT);
        $usuario['tipo'] = $nuevo_tipo;

        if (file_put_contents($usuarios_file, json_encode($usuarios, JSON_PRETTY_PRINT))) {
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
    <a title="index" href="index.php"><img src="images/index.webp" alt="index" width="80" style="vertical-align: middle;"></a>
    <a title="citas" href="citas.php"><img src="images/citas.webp" alt="citas" width="80" style="vertical-align: middle;"></a>
    <a title="vehiculos" href="vehiculos.php"><img src="images/vehiculos.webp" alt="vehiculos" width="80" style="vertical-align: middle;"></a>

    <?php if ($is_admin): ?>
        <a title="estaciones" href="estaciones.php"><img src="images/estaciones.webp" alt="estaciones" width="80" style="vertical-align: middle;"></a>
    <?php endif; ?>

    <?php if ($is_superadmin): ?>
        <a title="usuarios" href="usuarios.php"><img src="images/usuarios.webp" alt="usuarios" width="80" style="vertical-align: middle;"></a>
    <?php endif; ?>

    <a title="imprimir" href="imprimir.php"><img src="images/imprimir.webp" alt="imprimir" width="80" style="vertical-align: middle;"></a>
    <a title="logout" href="logout.php"><img src="images/logout.webp" alt="logout" width="80" style="vertical-align: middle;"></a>
</div>

<p></br></p>

<?php if (isset($error)): ?>
    <p style="color: red;"><?= $error ?></p>
<?php endif; ?>

<h2>Editar Usuario</h2>

<form method="POST">
    <label>Usuario:</label>
    <input type="text" name="usuario" value="<?= htmlspecialchars($usuario['usuario']) ?>" required><br><br>

    <label>Contraseña:</label>
    <input type="password" name="contraseña" required><br><br>

    <label>Tipo:</label>
    <select name="tipo">
        <option value="Usuario" <?= $usuario['tipo'] === 'Usuario' ? 'selected' : '' ?>>Usuario</option>
        <option value="Colaborador" <?= $usuario['tipo'] === 'Colaborador' ? 'selected' : '' ?>>Colaborador</option>
        <option value="Administrador" <?= $usuario['tipo'] === 'Administrador' ? 'selected' : '' ?>>Administrador</option>
        <option value="SuperAdministrador" <?= $usuario['tipo'] === 'SuperAdministrador' ? 'selected' : '' ?>>SuperAdministrador</option>
    </select><br><br>

    <input type="submit" value="Actualizar Usuario">
</form>

<h4 class="small" style="margin-top:12px;">ITVControl v.1.3</h4>
<p class="small">B174M3 // XaeK</p>

</body>
</html>

