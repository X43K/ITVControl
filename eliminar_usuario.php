<?php
session_start();

// Verificar si el usuario es administrador
if (!isset($_SESSION['usuario']) || $_SESSION['tipo'] != 'SuperAdministrador') {
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

$usuario_id = $_GET['usuario'];

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
        unset($usuarios[$usuario_encontrado['index']]);
        $usuarios = array_values($usuarios);

        if (file_put_contents($usuarios_file, json_encode($usuarios, JSON_PRETTY_PRINT))) {
            header('Location: usuarios.php');
            exit();
        } else {
            $error = "No se pudo eliminar el usuario. Verifique los permisos del archivo.";
        }
    } else {
        header('Location: usuarios.php');
        exit();
    }
}

// Cargar versión y autor desde version.xk
$version_text = 'v.1.4';
$autor_text = 'B174M3 // XaeK';
if (file_exists('version.xk')) {
    $lines = file('version.xk', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $version_text = $lines[0] ?? $version_text;
    $autor_text = $lines[1] ?? $autor_text;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Eliminar Usuario</title>
<link rel="shortcut icon" href="images/logo.webp">
<link rel="icon" sizes="64x64" href="images/logo.webp">
<link rel="apple-touch-icon" sizes="180x180" href="images/logo.webp">
<link rel="stylesheet" href="style.css">
<style>
body { margin:15px; font-family:Arial,sans-serif; }

/* Botones */
button { padding:6px 12px; margin:2px; border-radius:4px; cursor:pointer; }

/* Mensajes de error */
.rojo_intenso { color:#cc0000; }

/* ===== MODO OSCURO AUTOMÁTICO ===== */
@media (prefers-color-scheme: dark) {
    body { background:#000; color:#ddd; }
    h1,h2,h3,h4,p,strong { color:#ddd; }
    input, select, button { background:#111; color:#fff; border:1px solid #555; }
    button:hover { background:#222; }
    .rojo_intenso { color:#f33; }
}
</style>
</head>
<body>

<br>

<h1>Eliminar Usuario</h1>

<br>

<?php if (isset($error)): ?>
    <p class="rojo_intenso"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<p>¿Estás seguro de que deseas eliminar al usuario <strong><?= htmlspecialchars($usuario_encontrado['usuario']['usuario']) ?></strong>?</p>

<br>

<form method="POST">
    <button type="submit" name="confirmar" value="sí">Sí, eliminar</button>
    <button type="submit" name="confirmar" value="no">Cancelar</button>
</form>

<h4 class="small" style="margin-top:12px; text-align:left;"><?= htmlspecialchars($version_text) ?></h4>
<p class="small" style="text-align:left;"><?= htmlspecialchars($autor_text) ?></p>

</body>
</html>