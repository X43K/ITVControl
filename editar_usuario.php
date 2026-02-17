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
    if (!empty($_POST['usuario']) && !empty($_POST['contraseña']) && !empty($_POST['confirmar_contraseña']) && !empty($_POST['tipo'])) {
        if ($_POST['contraseña'] !== $_POST['confirmar_contraseña']) {
            $error = "Las contraseñas no coinciden.";
        } else {
            $usuario['usuario'] = $_POST['usuario'];
            $usuario['contraseña'] = password_hash($_POST['contraseña'], PASSWORD_DEFAULT);
            $usuario['tipo'] = $_POST['tipo'];

            if (file_put_contents($usuarios_file, json_encode($usuarios, JSON_PRETTY_PRINT))) {
                header('Location: usuarios.php');
                exit();
            } else {
                $error = "No se pudo actualizar el usuario. Verifique los permisos del archivo.";
            }
        }
    } else {
        $error = "Todos los campos son obligatorios.";
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
<title>Editar Usuario</title>
<link rel="shortcut icon" href="images/logo.webp">
<link rel="icon" sizes="64x64" href="images/logo.webp">
<link rel="apple-touch-icon" sizes="180x180" href="images/logo.webp">
<link rel="stylesheet" href="style.css">
<style>
body { margin:15px; font-family:Arial,sans-serif; }

/* Menú */
.menu { margin-bottom:15px; }
.menu a { margin-right:0px; }
.menu img { width:80px; height:auto; vertical-align:middle; transition:filter 0.3s ease; }
h1 img { vertical-align:middle; }

/* Formulario */
input, select { padding:5px; margin:2px 0; border-radius:4px; }
input[type="submit"] { cursor:pointer; }

/* Íconos de ojo */
.eye-icon {
    position:absolute;
    right:5px;
    top:5px;
    cursor:pointer;
    user-select:none;
    width:22px;
    height:22px;
    fill:#000;
}
@media (prefers-color-scheme: dark) {
    .eye-icon { fill:#fff; }
}

/* Mensajes de error */
.rojo_intenso { color:#cc0000; }

/* ===== MODO OSCURO AUTOMÁTICO ===== */
@media (prefers-color-scheme: dark) {
    body { background:#000; color:#ddd; }
    h1,h2,h3,h4,p,strong { color:#ddd; }
    input, select { background:#111; color:#fff; border:1px solid #555; }
    input[type="submit"] { background:#222; color:#fff; border:1px solid #666; }
    .menu img:not([alt="Logo"]) { filter: invert(1) hue-rotate(180deg); }
    h1 img { filter:none; }
    .rojo_intenso { color:#f33; }
}
</style>
</head>
<body>

<br>

<h1><img src="images/logo.webp" width="30"> Editar Usuario: <?= htmlspecialchars($usuario['usuario']) ?></h1>

<br>

    <div class="menu">
        <a title="index" href="index.php"><img src="images/index.webp" alt="index" width="80"></a>
        <a title="citas" href="citas.php"><img src="images/citas.webp" alt="citas" width="80"></a>
        <a title="vehiculos" href="vehiculos.php"><img src="images/vehiculos.webp" alt="vehiculos" width="80"></a>
        <?php if ($is_admin): ?>
            <a title="estaciones" href="estaciones.php"><img src="images/estaciones.webp" alt="estaciones" width="80"></a>
            <a title="seguridad" href="ips_bloqueadas.php"><img src="images/secury.webp" alt="seguridad" width="80"></a>
        <?php endif; ?>
        <?php if ($is_superadmin): ?>
            <a title="usuarios" href="usuarios.php"><img src="images/usuarios.webp" alt="usuarios" width="80"></a>
        <?php endif; ?>
        <a title="imprimir" href="imprimir.php"><img src="images/imprimir.webp" alt="imprimir" width="80"></a>
        <a title="logout" href="logout.php"><img src="images/logout.webp" alt="logout" width="80"></a>
    </div>

<br>

<?php if (isset($error)): ?>
    <p class="rojo_intenso"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<h2>Editar Usuario</h2>

<br>

<form method="POST">
    <label>Usuario:</label>
    <input type="text" name="usuario" value="<?= htmlspecialchars($usuario['usuario']) ?>" required><br><br>

    <label>Contraseña:</label>
    <div style="position:relative; display:inline-block;">
        <input type="password" id="contraseña" name="contraseña" required style="padding-right:35px;">
        <svg id="togglePass1" class="eye-icon" onclick="togglePassword('contraseña', 'togglePass1')" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
            <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12zm11 4a4 4 0 1 0 0-8 4 4 0 0 0 0 8z"/>
            <circle cx="12" cy="12" r="2"/>
        </svg>
    </div>
    <br><br>

    <label>Confirmar Contraseña:</label>
    <div style="position:relative; display:inline-block;">
        <input type="password" id="confirmar_contraseña" name="confirmar_contraseña" required style="padding-right:35px;">
        <svg id="togglePass2" class="eye-icon" onclick="togglePassword('confirmar_contraseña', 'togglePass2')" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
            <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12zm11 4a4 4 0 1 0 0-8 4 4 0 0 0 0 8z"/>
            <circle cx="12" cy="12" r="2"/>
        </svg>
    </div>
    <br><br>

    <label>Tipo:</label>
    <select name="tipo">
        <option value="Usuario" <?= $usuario['tipo'] === 'Usuario' ? 'selected' : '' ?>>Usuario</option>
        <option value="Colaborador" <?= $usuario['tipo'] === 'Colaborador' ? 'selected' : '' ?>>Colaborador</option>
        <option value="Administrador" <?= $usuario['tipo'] === 'Administrador' ? 'selected' : '' ?>>Administrador</option>
        <option value="SuperAdministrador" <?= $usuario['tipo'] === 'SuperAdministrador' ? 'selected' : '' ?>>SuperAdministrador</option>
    </select><br><br>

    <input type="submit" value="Actualizar Usuario">
</form>

<h4 class="small" style="margin-top:12px; text-align:left;"><?= htmlspecialchars($version_text) ?></h4>
<p class="small" style="text-align:left;"><?= htmlspecialchars($autor_text) ?></p>

<script>
// Mostrar / ocultar contraseñas (SVG cambia)
function togglePassword(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById(iconId);
    const isPassword = input.type === "password";
    input.type = isPassword ? "text" : "password";
    icon.innerHTML = isPassword
        ? '<path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12zm11 4a4 4 0 0 1-4-4h-2a6 6 0 0 0 12 0h-2a4 4 0 0 1-4 4z"/><circle cx="12" cy="12" r="2"/>'
        : '<path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12zm11 4a4 4 0 1 0 0-8 4 4 0 0 0 0 8z"/><circle cx="12" cy="12" r="2"/>';
}

// Validar contraseñas iguales antes de enviar
document.querySelector("form").addEventListener("submit", function(e) {
    const pass1 = document.getElementById("contraseña").value;
    const pass2 = document.getElementById("confirmar_contraseña").value;
    if (pass1 !== pass2) {
        e.preventDefault();
        alert("Las contraseñas no coinciden. Por favor, verifícalas.");
    }
});
</script>

</body>
</html>