<?php
session_start();

/* ================= SEGURIDAD BASE ================= */
if (!isset($_SESSION['usuario']) || !in_array($_SESSION['tipo'], ['Administrador', 'SuperAdministrador'])) {
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

// Obtener flota del usuario
$flota_usuario = $_SESSION['flota'] ?? null;
$flota_texto = $is_superadmin ? "Todas las flotas" : ($flota_usuario ? strtoupper($flota_usuario) : "Sin flota asignada");

/* ================= CONTROL DE FLOTA ================= */
if ($_SESSION['tipo'] === 'Administrador') {
    $flota_usuario = $usuario['flota'] ?? '';

    if ($flota_admin !== $flota_usuario) {
        die("No tienes permiso para editar este usuario.");
    }

    if ($usuario['tipo'] === 'SuperAdministrador') {
        die("No tienes permiso para editar este usuario.");
    }
}

// =====================
// FUNCIONES
// =====================
function formatear_fecha($fecha) {
    $fecha_obj = DateTime::createFromFormat('Y-m-d', $fecha);
    return $fecha_obj ? $fecha_obj->format('d/m/Y') : $fecha;
}

/* ================= PROCESAR FORMULARIO ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nuevo_usuario = trim($_POST['usuario'] ?? '');
    $pass = $_POST['contraseña'] ?? '';
    $pass2 = $_POST['confirmar_contraseña'] ?? '';
    $nuevo_tipo = $_POST['tipo'] ?? '';

    if ($nuevo_usuario === '' || $nuevo_tipo === '') {
        $error = "Usuario y tipo son obligatorios.";
    } elseif ($pass !== $pass2) {
        $error = "Las contraseñas no coinciden.";
    } else {

        /* ===== Restricciones para Administrador ===== */
        if ($_SESSION['tipo'] === 'Administrador') {
            if ($nuevo_tipo === 'SuperAdministrador') {
                die("No tienes permiso para asignar ese tipo.");
            }
            $usuario['flota'] = $flota_admin; // forzar misma flota
        }

        /* ===== Actualizar datos ===== */
        $usuario['usuario'] = $nuevo_usuario;
        $usuario['tipo'] = $nuevo_tipo;

        if ($is_superadmin && isset($_POST['flota'])) {
            $usuario['flota'] = strtoupper(trim($_POST['flota']));
        }

        // SOLO actualizar la contraseña si se ha rellenado
        if ($pass !== '') {
            $usuario['contraseña'] = password_hash($pass, PASSWORD_DEFAULT);
        }

        if (file_put_contents($usuarios_file, json_encode($usuarios, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
            header('Location: usuarios.php');
            exit();
        } else {
            $error = "No se pudo actualizar el usuario.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>ITVGestion</title>
<link rel="icon" href="images/logo.webp">
<link rel="stylesheet" href="style.css">
<style>
body{margin:15px;font-family:Arial,sans-serif;}
input, select{padding:4px; margin-top:4px; width:250px;}
input[type=submit]{padding:6px 12px;background:#004aad;color:#fff;border:none;cursor:pointer;}
input[type=submit]:hover{background:#0066ff;}
.negro{background:black;color:grey;}
.rojo_intenso{background:#cc0000;color:white;}
.naranja_intenso{background:#ff6600;color:white;}
.naranja_suave{background:#ffae0d;color:white;}
.azul{background:#3399ff;color:white;}
.verde{background:#4CAF50;color:white;}
/* Modo oscuro */
@media (prefers-color-scheme: dark){
    body{background:#000;color:#ddd;}
    input, select{background:#222;color:#ddd;border:1px solid #555;}
    input[type=submit]{background:#0066ff;color:#fff;}
    input[type=submit]:hover{background:#3399ff;}
    .menu img{filter: invert(1) hue-rotate(180deg);}
    h1 img{filter:none;}
}
h1, h2{color:inherit;}

/* ===== INFO DE USUARIO ===== */
.user-info{
    position: fixed;
    top: 10px;
    right: 15px;
    text-align: right;
    font-size: 14px;
    background: rgba(255,255,255,0.6); /* mismo fondo que el código 2 */
    padding: 5px 10px; /* relleno idéntico */
    border-radius: 8px;
}
.user-info strong{ display: block; }
.user-info small{ color: #4a90e2; font-weight: bold; }

/* ===== MODO OSCURO ===== */
@media (prefers-color-scheme: dark){
    .user-info{ background: rgba(0,0,0,0.5); }
    .user-info small{ color: #3399ff; }
}
  
</style>
</head>
<body>

<div class="user-info">
    <strong><?= htmlspecialchars($_SESSION['usuario']) ?> | <?= htmlspecialchars($_SESSION['tipo']) ?></strong>
    <small><?= htmlspecialchars($flota_texto) ?></small>
    <div id="fecha-hora"></div>
</div>

<h1><img src="images/logo.webp" width="30" style="vertical-align: middle;"> Editar Usuario </h1>
<hr style="border:1px solid #4a90e2; margin:10px 0 20px 0;">

    <div class="menu">
      <a title="index" href="index.php"><img src="images/index.webp" alt="index" width="80"></a>
      <a title="citas" href="citas.php"><img src="images/citas.webp" alt="citas" width="80"></a>
      <a title="vehiculos" href="vehiculos.php"><img src="images/vehiculos.webp" alt="vehiculos" width="80"></a>
     <?php if(in_array($_SESSION['tipo'], ['Administrador','SuperAdministrador'])): ?>
      <a title="estaciones" href="estaciones.php"><img src="images/estaciones.webp" alt="estaciones" width="80"></a>
      <a title="seguridad" href="ips_bloqueadas.php"><img src="images/secury.webp" alt="seguridad" width="80"></a>
      <a title="usuarios" href="usuarios.php"><img src="images/usuarios.webp" alt="usuarios" width="80"></a>
     <?php endif; ?>
      <a title="imprimir" href="imprimir.php"><img src="images/imprimir.webp" alt="imprimir" width="80"></a>
      <a title="logout" href="logout.php"><img src="images/logout.webp" alt="logout" width="80"></a>
    </div>
  
    <br><br><br>
  
<h1>Editar Usuario: <?= htmlspecialchars($usuario['usuario']) ?></h1>

<?php if (isset($error)): ?>
<p class="rojo_intenso"><strong><?= htmlspecialchars($error) ?></strong></p>
<?php endif; ?>

<form method="POST" style="max-width:400px;">
    Usuario:<br>
    <input type="text" name="usuario" value="<?= htmlspecialchars($usuario['usuario']) ?>" required><br><br>

    Contraseña (dejar vacía para mantener la actual):<br>
    <input type="password" name="contraseña"><br><br>

    Confirmar Contraseña:<br>
    <input type="password" name="confirmar_contraseña"><br><br>

    Tipo:<br>
    <select name="tipo">
        <option value="Usuario" <?= $usuario['tipo'] === 'Usuario' ? 'selected' : '' ?>>Usuario</option>
        <option value="Colaborador" <?= $usuario['tipo'] === 'Colaborador' ? 'selected' : '' ?>>Colaborador</option>
        <option value="Administrador" <?= $usuario['tipo'] === 'Administrador' ? 'selected' : '' ?>>Administrador</option>
        <?php if ($is_superadmin): ?>
            <option value="SuperAdministrador" <?= $usuario['tipo'] === 'SuperAdministrador' ? 'selected' : '' ?>>SuperAdministrador</option>
        <?php endif; ?>
    </select><br><br>

    <?php if ($is_superadmin): ?>
    Flota:<br>
    <input type="text" name="flota" value="<?= htmlspecialchars($usuario['flota'] ?? '') ?>" required style="text-transform:uppercase;"><br><br>
    <?php else: ?>
    <strong>Flota:</strong> <?= htmlspecialchars($usuario['flota'] ?? '') ?><br><br>
    <?php endif; ?>

    <input type="submit" value="Actualizar Usuario">
</form>

<script>
function actualizarFechaHora(){
    const d=new Date();
    document.getElementById('fecha-hora').innerText =
        d.toLocaleDateString('es-ES')+' '+d.toLocaleTimeString('es-ES');
}
actualizarFechaHora();
setInterval(actualizarFechaHora,1000);
</script>
  
</body>
</html>