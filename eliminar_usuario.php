<?php
session_start();

/* ================= SEGURIDAD BASE ================= */

if (
    !isset($_SESSION['usuario']) ||
    !in_array($_SESSION['tipo'], ['Administrador', 'SuperAdministrador'])
) {
    header('Location: index.php');
    exit();
}

$is_superadmin = $_SESSION['tipo'] === 'SuperAdministrador';
$flota_admin = $_SESSION['flota'] ?? '';

$usuarios_file = 'usuarios.json';
if (!file_exists($usuarios_file)) {
    die("El archivo de usuarios no existe.");
}

$usuarios = json_decode(file_get_contents($usuarios_file), true);
if (!is_array($usuarios)) $usuarios = [];

/* ================= OBTENER USUARIO ================= */

if (!isset($_GET['usuario'])) {
    die("Usuario no especificado.");
}

$usuario_id = $_GET['usuario'];

$usuario_encontrado = null;

foreach ($usuarios as $index => $usuario) {
    if ($usuario['usuario'] === $usuario_id) {
        $usuario_encontrado = [
            'index' => $index,
            'usuario' => $usuario
        ];
        break;
    }
}

if (!$usuario_encontrado) {
    die("Usuario no encontrado.");
}

/* ================= CONTROL DE PERMISOS ================= */

$usuario_objetivo = $usuario_encontrado['usuario'];

if ($_SESSION['tipo'] === 'Administrador') {

    $flota_usuario = $usuario_objetivo['flota'] ?? '';

    // No puede eliminar otra flota
    if ($flota_admin !== $flota_usuario) {
        die("No tienes permiso para eliminar este usuario.");
    }

    // No puede eliminar SuperAdministradores
    if ($usuario_objetivo['tipo'] === 'SuperAdministrador') {
        die("No tienes permiso para eliminar este usuario.");
    }

    // No puede eliminarse a sí mismo
    if ($usuario_objetivo['usuario'] === $_SESSION['usuario']) {
        die("No puedes eliminar tu propio usuario.");
    }
}

/* ================= CONFIRMACIÓN ================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['confirmar']) && $_POST['confirmar'] === 'sí') {

        unset($usuarios[$usuario_encontrado['index']]);
        $usuarios = array_values($usuarios);

        if (file_put_contents($usuarios_file, json_encode($usuarios, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
            header('Location: usuarios.php');
            exit();
        } else {
            $error = "No se pudo eliminar el usuario.";
        }

    } else {
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

<link rel="icon" href="images/logo.webp">
<link rel="manifest" href="manifest.json">
<link rel="apple-touch-icon" sizes="180x180" href="images/logo.webp">
<link rel="stylesheet" href="style.css">
</head>
<body>

<h1>Eliminar Usuario</h1>

<?php if (isset($error)): ?>
<p style="color:red;"><strong><?= htmlspecialchars($error) ?></strong></p>
<?php endif; ?>

<p>
¿Estás seguro de que deseas eliminar al usuario 
<strong><?= htmlspecialchars($usuario_objetivo['usuario']) ?></strong>?
</p>

<form method="POST">
    <button type="submit" name="confirmar" value="sí">Sí, eliminar</button>
    <button type="submit" name="confirmar" value="no">Cancelar</button>
</form>

</body>
</html>
