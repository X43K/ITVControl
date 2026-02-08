<?php
session_start();

// Si ya está logueado, redirigir
if (isset($_SESSION['usuario'])) {
    header('Location: index.php'); exit();
}

$usuarios_file = 'usuarios.json';

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!file_exists($usuarios_file)) die("El archivo de usuarios no existe.");
    $usuarios = json_decode(file_get_contents($usuarios_file), true);

    $usuario_input = $_POST['usuario'] ?? '';
    $contraseña_input = $_POST['contraseña'] ?? '';

    $usuario_encontrado = false;
    foreach ($usuarios as $usuario) {
        if ($usuario['usuario'] === $usuario_input) {
            $usuario_encontrado = true;
            if (password_verify($contraseña_input, $usuario['contraseña'])) {
                $_SESSION['usuario'] = $usuario['usuario'];
                $_SESSION['tipo'] = $usuario['tipo'];
                header('Location: index.php'); exit();
            } else {
                $error = "Contraseña incorrecta.";
            }
            break;
        }
    }

    if (!$usuario_encontrado) $error = "Usuario no encontrado.";
}

// =====================
// CARGA DE VERSION.XK
// =====================
$version_file = 'version.xk';
$version = 'v.1.0'; $autor = '';
if(file_exists($version_file)){
    $lines = file($version_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if(isset($lines[0])) $version = $lines[0];
    if(isset($lines[1])) $autor = $lines[1];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Iniciar Sesión</title>

<link rel="shortcut icon" href="images/logo.webp">
<link rel="icon" sizes="64x64" href="images/logo.webp">
<link rel="apple-touch-icon" sizes="180x180" href="images/logo.webp">

<style>
body {
    font-family: Arial, sans-serif;
    background:#fff;
    color:#000;
    padding:20px;
}
h1 {
    font-size:26px; /* Aumentado de 20px a 26px */
    margin-bottom:20px;
}
h1 img {
    vertical-align: middle;
    width:30px;
}
label {
    display:block;
    margin-top:10px;
    font-weight:bold;
}
input[type=text],
input[type=password] {
    width:250px;
    padding:5px;
    margin-top:2px;
    border:1px solid #ccc;
    background:#fff;
    color:#000;
}
input[type=submit] {
    margin-top:12px;
    padding:6px 12px;
    cursor:pointer;
    background:#004aad;
    color:#fff;
    border:none;
}
input[type=submit]:hover {
    background:#0066ff;
}

/* ===== MODO OSCURO AUTOMÁTICO ===== */
@media (prefers-color-scheme: dark) {
    body {
        background:#000;
        color:#ddd;
    }
    h1,label,p {
        color:#ddd;
    }

    /* Campos de texto */
    input[type=text],
    input[type=password] {
        background:#222;
        color:#ddd;
        border:1px solid #555;
    }

    /* Botón */
    input[type=submit] {
        background:#0066ff;
        color:#fff;
        border:none;
    }
    input[type=submit]:hover {
        background:#3399ff;
    }
}
</style>
</head>
<body>

<h1>
    <img src="images/logo.webp" alt="Logo">
    Iniciar Sesión
</h1>

<form method="POST" action="">
    <label for="usuario">Usuario:</label>
    <input type="text" id="usuario" name="usuario" autocomplete="username" autocapitalize="none" spellcheck="false" required>

    <label for="contraseña">Contraseña:</label>
    <input type="password" id="contraseña" name="contraseña" autocomplete="current-password" required>

    <br><br>
    <input type="submit" value="Iniciar Sesión">
</form>

<?php if(isset($error)): ?>
<p style="color:red;"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<h4 class="small" style="margin-top:12px;"><?= htmlspecialchars($version) ?></h4>
<p class="small"><?= htmlspecialchars($autor) ?></p>

</body>
</html>
