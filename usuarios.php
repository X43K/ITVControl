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
$log_file = 'usuarios-fail.log';

if (!file_exists($usuarios_file)) {
    file_put_contents($usuarios_file, json_encode([]));
}

$usuarios = json_decode(file_get_contents($usuarios_file), true);
if (!is_array($usuarios)) $usuarios = [];

/* ================= VER LOG ================= */
$log_modal = null;
$total_intentos = 0;

if (isset($_GET['ver_log'])) {
    $usuario_log = $_GET['ver_log'];

    if (file_exists($log_file)) {
        $lineas = file($log_file, FILE_IGNORE_NEW_LINES);
        $filtrado = [];

        foreach ($lineas as $linea) {
            if (stripos($linea, "Usuario: $usuario_log") !== false) {
                $filtrado[] = $linea;
            }
        }

        $filtrado = array_reverse($filtrado);
        $total_intentos = count($filtrado);
        $log_modal = $filtrado;
    } else {
        $log_modal = [];
    }
}

/* ================= LIMPIAR LOG ================= */
if (isset($_GET['limpiar_log'])) {
    $usuario_limpiar = $_GET['limpiar_log'];

    if (file_exists($log_file)) {
        $lineas = file($log_file);
        $nuevo = [];

        foreach ($lineas as $linea) {
            if (stripos($linea, "Usuario: $usuario_limpiar") === false) {
                $nuevo[] = $linea;
            }
        }

        file_put_contents($log_file, implode('', $nuevo));
    }

    header('Location: usuarios.php');
    exit();
}

// Procesar formulario de añadir usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['usuario']) && !empty($_POST['contraseña']) && !empty($_POST['confirmar_contraseña']) && !empty($_POST['tipo'])) {
        if ($_POST['contraseña'] !== $_POST['confirmar_contraseña']) {
            $error = "Las contraseñas no coinciden.";
        } else {
            $nuevo_usuario = [
                'usuario' => $_POST['usuario'],
                'contraseña' => password_hash($_POST['contraseña'], PASSWORD_DEFAULT),
                'tipo' => $_POST['tipo']
            ];

            $usuarios[] = $nuevo_usuario;
            file_put_contents($usuarios_file, json_encode($usuarios, JSON_PRETTY_PRINT));

            header('Location: usuarios.php');
            exit();
        }
    } else {
        $error = "Todos los campos son obligatorios.";
    }
}

// ======= Preprocesar si hay registros de log para cada usuario =======
if (file_exists($log_file)) {
    $lineas_log = file($log_file, FILE_IGNORE_NEW_LINES);
    foreach ($usuarios as &$usuario) {
        $usuario['tiene_log'] = false;
        foreach ($lineas_log as $linea) {
            if (stripos($linea, "Usuario: {$usuario['usuario']}") !== false) {
                $usuario['tiene_log'] = true;
                break;
            }
        }
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
<title>Gestionar Usuarios</title>
<link rel="shortcut icon" href="images/logo.webp">
<link rel="icon" sizes="64x64" href="images/logo.webp">
<link rel="apple-touch-icon" sizes="180x180" href="images/logo.webp">
<link rel="stylesheet" href="style.css">
<style>
body { margin:15px; font-family:Arial,sans-serif; }
.menu { margin-bottom:15px; }
.menu a { margin-right:0px; }
.menu img { width:80px; height:auto; vertical-align:middle; transition:filter 0.3s ease; }
h1 img { vertical-align:middle; }
input, select { padding:5px; margin:2px 0; border-radius:4px; }
input[type="submit"] { cursor:pointer; }
.eye-icon { position:absolute; right:5px; top:5px; cursor:pointer; user-select:none; width:22px; height:22px; fill:#000; }
@media (prefers-color-scheme: dark) { .eye-icon { fill:#fff; } }
table { border-collapse:collapse; width:100%; }
th, td { border:1px solid #ccc; padding:8px; vertical-align:top; }
th { background:#eee; }
a { text-decoration:underline; }
.rojo_intenso { color:#cc0000; }
.user-info { position: fixed; top: 10px; right: 15px; text-align: right; font-size: 14px; color: inherit; }
.proxima-itv { display:none; }
.info-usuarios { flex:1; padding:20px; border:2px solid #4a90e2; border-radius:12px; background: linear-gradient(135deg, #f0f8ff, #dbe9ff); box-shadow: 2px 2px 12px rgba(0,0,0,0.15); font-size:14px; line-height:1.5; color: #000; transition: all 0.3s ease; position: relative; }
.info-usuarios::before { content: "\2139"; font-size: 22px; color: #4a90e2; position: absolute; top:10px; left:10px; }
.info-usuarios:hover { transform: translateY(-2px); box-shadow: 4px 4px 14px rgba(0,0,0,0.25); }
@media (prefers-color-scheme: dark) {
    body { background:#000; color:#ddd; }
    h1,h2,h3,h4,p,strong { color:#ddd; }
    th { background:#222; color:#fff; }
    input, select { background:#111; color:#fff; border:1px solid #555; }
    input[type="submit"] { background:#222; color:#fff; border:1px solid #666; }
    .menu img:not([alt="Logo"]) { filter: invert(1) hue-rotate(180deg); }
    h1 img { filter:none; }
    .rojo_intenso { color:#f33; }
    .info-usuarios { border:2px solid #3399ff; background: linear-gradient(135deg, #111827, #1e293b); box-shadow: 2px 2px 12px rgba(0,0,0,0.5); color:#ddd; }
    .info-usuarios::before { color:#3399ff; }
}
</style>
</head>
<body>

<div class="user-info">
    <strong><?= htmlspecialchars($_SESSION['usuario']) ?> | <?= htmlspecialchars($_SESSION['tipo']) ?></strong>
    <div id="fecha-hora"></div>
</div>
<br>

<h1>
    <img src="images/logo.webp" alt="Logo" width="30"> Gestionar Usuarios</h1>
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

<h2>Añadir Usuario</h2>
<br>

<div style="display:flex; gap:20px; align-items:flex-start;">
    <form method="POST" style="flex:1;">
        <label>Usuario:</label><input type="text" name="usuario" required><br><br>

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
            <option value="Usuario">Usuario</option>
            <option value="Colaborador">Colaborador</option>
            <option value="Administrador">Administrador</option>
            <option value="SuperAdministrador">SuperAdministrador</option>
        </select><br><br>

        <input type="submit" value="Añadir Usuario">
    </form>

    <div class="info-usuarios">
        <strong>Tipos de usuario:</strong><br><br>
        <strong>Usuario</strong> - Puede consultar e imprimir.<br>
        <strong>Colaborador</strong> - Puede hacer todo lo anterior + añadir citas, añadir vehículos y modificar estados y caducidades vehículos.<br>
        <strong>Administrador</strong> - Puede hacer todo lo anterior + modificar/eliminar citas, eliminar vehículos y gestionar estaciones.<br>
        <strong>SuperAdministrador</strong> - Puede hacer todo lo anterior + añadir/modificar/eliminar usuarios.
    </div>
</div>
<br><br>

<h2>Lista de Usuarios</h2>
<table>
    <thead>
        <tr>
            <th>Usuario</th>
            <th>Tipo</th>
            <th>Estado</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($usuarios as $usuario): ?>
            <tr>
                <td><?= htmlspecialchars($usuario['usuario']) ?></td>
                <td><?= htmlspecialchars($usuario['tipo']) ?></td>
                <td>
                    <?php if (!empty($usuario['bloqueado'])): ?>
                        <span style="color:#cc0000;font-weight:bold;">🔴 Bloqueado</span>
                    <?php else: ?>
                        <span style="color:green;">🟢 Activo</span>
                    <?php endif; ?>
                </td>
                <td style="display:flex; gap:5px; flex-wrap:wrap;">
                    <form action="editar_usuario.php" method="get" style="margin:0;">
                        <input type="hidden" name="usuario" value="<?= htmlspecialchars($usuario['usuario']) ?>">
                        <button type="submit" style="padding:4px 8px; cursor:pointer;">Editar</button>
                    </form>

                    <form action="eliminar_usuario.php" method="get" style="margin:0;" onsubmit="return confirm('¿Estás seguro de eliminar este usuario?');">
                        <input type="hidden" name="usuario" value="<?= htmlspecialchars($usuario['usuario']) ?>">
                        <button type="submit" style="padding:4px 8px; cursor:pointer; background-color:#cc0000; color:#fff;">Eliminar</button>
                    </form>

                    <!-- Ver intentos solo si hay registros -->
                    <?php if (!empty($usuario['tiene_log'])): ?>
                        <form action="usuarios.php" method="get" style="margin:0;">
                            <input type="hidden" name="ver_log" value="<?= htmlspecialchars($usuario['usuario']) ?>">
                            <button type="submit" style="padding:4px 8px; cursor:pointer; background-color:#4a90e2; color:#fff;">Ver intentos</button>
                        </form>
                    <?php endif; ?>

                    <?php if (!empty($usuario['bloqueado'])): ?>
                        <form action="desbloquear_usuario.php" method="post" style="margin:0;">
                            <input type="hidden" name="usuario" value="<?= htmlspecialchars($usuario['usuario']) ?>">
                            <button type="submit" style="padding:4px 8px; cursor:pointer; background-color:#28a745; color:#fff;">Debloquear</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<h4 class="small" style="margin-top:12px; text-align:left;"><?= htmlspecialchars($version_text) ?></h4>
<p class="small" style="text-align:left;"><?= htmlspecialchars($autor_text) ?></p>

<script>
function actualizarFechaHora(){
    const d=new Date();
    document.getElementById('fecha-hora').innerText = d.toLocaleDateString('es-ES')+' '+d.toLocaleTimeString('es-ES');
}
actualizarFechaHora();
setInterval(actualizarFechaHora,1000);

function togglePassword(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById(iconId);
    const isPassword = input.type === "password";
    input.type = isPassword ? "text" : "password";
    icon.innerHTML = isPassword
        ? '<path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12zm11 4a4 4 0 0 1-4-4h-2a6 6 0 0 0 12 0h-2a4 4 0 0 1-4 4z"/><circle cx="12" cy="12" r="2"/>'
        : '<path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12zm11 4a4 4 0 1 0 0-8 4 4 0 0 0 0 8z"/><circle cx="12" cy="12" r="2"/>';
}

document.querySelector("form").addEventListener("submit", function(e) {
    const pass1 = document.getElementById("contraseña").value;
    const pass2 = document.getElementById("confirmar_contraseña").value;
    if (pass1 !== pass2) {
        e.preventDefault();
        alert("Las contraseñas no coinciden. Por favor, verifícalas.");
    }
});
</script>

<?php if ($log_modal !== null): ?>
<div style="
position:fixed;
top:50%;
left:50%;
transform:translate(-50%,-50%);
width:70%;
max-height:70%;
overflow:auto;
background:inherit;
color:inherit;
border:2px solid currentColor;
padding:20px;
z-index:9999;
box-shadow:0 0 20px rgba(0,0,0,0.5);
">
<h3>Intentos fallidos (<?= $total_intentos ?>)</h3>

<pre style="font-size:12px;">
<?php
if (empty($log_modal)) {
    echo "No hay intentos registrados.";
} else {
    foreach ($log_modal as $linea) {
        echo htmlspecialchars($linea) . "\n";
    }
}
?>
</pre>

<br>

<a href="usuarios.php?limpiar_log=<?= urlencode($_GET['ver_log']) ?>"
onclick="return confirm('¿Eliminar todos los registros de este usuario?')"
style="color:#cc0000;font-weight:bold;">
Limpiar registros
</a>

<br><br>
<a href="usuarios.php" style="font-weight:bold;">Cerrar</a>
</div>
<?php endif; ?>

</body>
</html>
