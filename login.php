<?php
include __DIR__ . '/check_bloqueo.php';
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
    font-size:26px;
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

/* Ícono del ojo */
.eye-icon {
    position:absolute;
    right:5px;
    top:6px;
    cursor:pointer;
    user-select:none;
    width:22px;
    height:22px;
    fill:#000;
}
@media (prefers-color-scheme: dark) {
    .eye-icon { fill:#fff; }
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
    input[type=text],
    input[type=password] {
        background:#222;
        color:#ddd;
        border:1px solid #555;
    }
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
    <div style="position:relative; display:inline-block;">
        <input type="password" id="contraseña" name="contraseña" autocomplete="current-password" required style="padding-right:35px;">
        <svg id="togglePass" class="eye-icon" onclick="togglePassword('contraseña','togglePass')" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
            <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12zm11 4a4 4 0 1 0 0-8 4 4 0 0 0 0 8z"/>
            <circle cx="12" cy="12" r="2"/>
        </svg>
    </div>

    <br><br>
    <input type="submit" value="Iniciar Sesión">
</form>

<?php if(isset($error)): ?>
<p style="color:red;"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<h4 class="small" style="margin-top:12px;"><?= htmlspecialchars($version) ?></h4>
<p class="small"><?= htmlspecialchars($autor) ?></p>

<script>
// Mostrar / ocultar contraseña (SVG cambia)
function togglePassword(inputId, iconId){
    const input = document.getElementById(inputId);
    const icon = document.getElementById(iconId);
    const isPassword = input.type === "password";
    input.type = isPassword ? "text" : "password";
    icon.innerHTML = isPassword
        ? '<path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12zm11 4a4 4 0 0 1-4-4h-2a6 6 0 0 0 12 0h-2a4 4 0 0 1-4 4z"/><circle cx="12" cy="12" r="2"/>'
        : '<path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12zm11 4a4 4 0 1 0 0-8 4 4 0 0 0 0 8z"/><circle cx="12" cy="12" r="2"/>';
}
</script>

</body>
</html>