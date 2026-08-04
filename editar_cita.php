<?php
session_name('ITVCONTROL_SESSID');
session_start();

// =====================
// VERIFICAR LOGIN Y PERMISOS
// =====================
if (!isset($_SESSION['usuario']) || !in_array($_SESSION['tipo'], ['Administrador', 'SuperAdministrador', 'Colaborador'])) {
    header('Location: index.php');
    exit();
}

$is_admin = in_array($_SESSION['tipo'], ['Administrador', 'SuperAdministrador']);
$is_superadmin = $_SESSION['tipo'] === 'SuperAdministrador';
$is_colab = in_array($_SESSION['tipo'], ['Colaborador', 'Administrador', 'SuperAdministrador']);

$flota_usuario = $_SESSION['flota'] ?? null;
$flota_texto = $is_superadmin ? "Todas las flotas" : ($flota_usuario ? strtoupper($flota_usuario) : "Sin flota asignada");

// =====================
// CARGAR CITAS Y VEHÍCULOS
// =====================
$citas_file = 'citas.json';
if (!file_exists($citas_file)) die("El archivo de citas no existe.");
$citas = json_decode(file_get_contents($citas_file), true);

$vehiculos_file = 'vehiculos.json';
if (!file_exists($vehiculos_file)) die("El archivo de vehículos no existe.");
$vehiculos = json_decode(file_get_contents($vehiculos_file), true);

// Filtrar vehículos según flota (excepto SuperAdmin)
if (!$is_superadmin && $flota_usuario) {
    $vehiculos = array_filter($vehiculos, fn($v) => isset($v['flota']) && strtoupper(trim($v['flota'])) === strtoupper(trim($flota_usuario)));
}

// Ordenar vehículos alfabéticamente
usort($vehiculos, fn($a, $b) => strnatcasecmp($a['vehiculo'], $b['vehiculo']));

// =====================
// CARGAR ESTACIONES
// =====================
$estaciones_file = 'estaciones.json';
if (!file_exists($estaciones_file)) {
    file_put_contents($estaciones_file, json_encode(['Tambre','Sionlla','Cacheiras'], JSON_PRETTY_PRINT));
}
$estaciones = json_decode(file_get_contents($estaciones_file), true);

// =====================
// BUSCAR CITA A EDITAR
// =====================
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: citas.php');
    exit();
}
$id_cita = $_GET['id'];

$cita_editar = null;
foreach ($citas as &$cita) {
    if (isset($cita['id_cita']) && $cita['id_cita'] === $id_cita) {
        $cita_editar = &$cita;
        break;
    }
}
if ($cita_editar === null) die("No se encontró la cita con el ID proporcionado.");

// =====================
// PROCESAR FORMULARIO
// =====================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $flota_cita = $is_superadmin ? ($_POST['flota_cita'] ?? '') : $flota_usuario;

    if (!empty($_POST['fecha_cita']) && !empty($_POST['hora_cita']) && !empty($_POST['estacion_cita']) && !empty($_POST['tipo_cita']) && (!empty($flota_cita))) {
        $cita_editar['fecha_cita'] = $_POST['fecha_cita'];
        $cita_editar['hora_cita'] = $_POST['hora_cita'];
        $cita_editar['estacion_cita'] = $_POST['estacion_cita'];
        $cita_editar['tipo_cita'] = $_POST['tipo_cita'];
        $cita_editar['vehiculo'] = $_POST['vehiculo'] ?? '';
        $cita_editar['flota'] = $flota_cita;

        if (file_put_contents($citas_file, json_encode($citas, JSON_PRETTY_PRINT))) {
            header('Location: citas.php');
            exit();
        } else {
            $error = "No se pudo guardar la cita. Verifique los permisos del archivo.";
        }
    } else {
        $error = $is_superadmin ? "Todos los campos, incluida la flota, son obligatorios." : "Todos los campos son obligatorios.";
    }
}

// =====================
// FUNCIONES
// =====================
function formatear_fecha($fecha) {
    $fecha_obj = DateTime::createFromFormat('Y-m-d', $fecha);
    return $fecha_obj ? $fecha_obj->format('d/m/Y') : $fecha;
}

// =====================
// CARGAR VERSION Y AUTOR
// =====================
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
<title>ITVControl</title>
<link rel="icon" href="images/logo.webp">
<link rel="manifest" href="manifest.json">
<link rel="apple-touch-icon" sizes="180x180" href="images/logo.webp">
<link rel="stylesheet" href="style.css">

<style>
body{margin:15px;font-family:Arial,sans-serif;}
input, select{padding:4px; margin-top:4px; width:250px;}
input[type=submit]{padding:6px 12px;background:#004aad;color:#fff;border:none;cursor:pointer;}
input[type=submit]:hover{background:#0066ff;}
/* Colores de botones adicionales */
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
    .user-info #fecha-hora{ color:#ddd; }
}
</style>
<script>
function validarCita(form) {
    return true;
}

function actualizarFechaHora(){
    const elem = document.getElementById('fecha-hora');
    if (!elem) return;
    const d = new Date();
    elem.textContent = d.toLocaleDateString('es-ES') + ' ' + d.toLocaleTimeString('es-ES');
}
window.addEventListener('load', actualizarFechaHora);
setInterval(actualizarFechaHora, 1000);
</script>
</head>
<body>

<!-- ===== USUARIO/TIPO/FLOTA/FECHA-HORA ===== -->
<div class="user-info">
    <strong><?= htmlspecialchars($_SESSION['usuario']) ?> | <?= htmlspecialchars($_SESSION['tipo']) ?></strong>
    <small><?= htmlspecialchars($flota_texto) ?></small>
    <div id="fecha-hora"></div>
</div>

<h1><img src="images/logo.webp" width="30" style="vertical-align: middle;"> Editar Cita </h1>
<hr style="border:1px solid #4a90e2; margin:10px 0 20px 0;">

<!-- ===== MENU ===== -->
<div class="menu">
  <a title="index" href="index.php"><img src="images/index.webp" alt="index" width="80"></a>
  <a title="citas" href="citas.php"><img src="images/citas.webp" alt="citas" width="80"></a>
  <a title="vehiculos" href="vehiculos.php"><img src="images/vehiculos.webp" alt="vehiculos" width="80"></a>
  <?php if($is_admin): ?>
    <a title="estaciones" href="estaciones.php"><img src="images/estaciones.webp" alt="estaciones" width="80"></a>
    <a title="seguridad" href="ips_bloqueadas.php"><img src="images/secury.webp" alt="seguridad" width="80"></a>
    <a title="usuarios" href="usuarios.php"><img src="images/usuarios.webp" alt="usuarios" width="80"></a>
  <?php endif; ?>
  <a title="imprimir" href="imprimir.php"><img src="images/imprimir.webp" alt="imprimir" width="80"></a>
  <a title="logout" href="logout.php"><img src="images/logout.webp" alt="logout" width="80"></a>
</div>

<br><br><br>

<!-- ===== FORMULARIO ===== -->
<form method="POST" onsubmit="return validarCita(this);">
    <label><strong>ID de Cita:</strong> <?= htmlspecialchars($cita_editar['id_cita']) ?></label><br>

    <label>Fecha de Cita:</label>
    <input type="date" name="fecha_cita" value="<?= htmlspecialchars($cita_editar['fecha_cita']) ?>" required><br>

    <label>Hora de Cita:</label>
    <input type="time" name="hora_cita" value="<?= htmlspecialchars($cita_editar['hora_cita']) ?>" required><br>

<label>Estación:</label>
<select name="estacion_cita" required>
    <?php
    // Aseguramos que $estaciones sea un array
    if (!is_array($estaciones)) $estaciones = [];

    foreach ($estaciones as $estacion_obj) {
        // Estacion es ahora un objeto con nombre y flotas
        $nombre_estacion = $estacion_obj['nombre'] ?? '';
        $flotas_estacion = $estacion_obj['flotas'] ?? [];

        // Solo mostrar estaciones de la flota del usuario o todas si SuperAdmin
        if (!$is_superadmin && $flota_usuario && !in_array(strtoupper(trim($flota_usuario)), array_map('strtoupper', $flotas_estacion))) {
            continue;
        }

        $selected = ($cita_editar['estacion_cita'] ?? '') === $nombre_estacion ? 'selected' : '';
        echo '<option value="'.htmlspecialchars($nombre_estacion).'" '.$selected.'>'.htmlspecialchars($nombre_estacion).'</option>';
    }
    ?>
</select><br>

    <label>Tipo de Cita:</label>
    <select name="tipo_cita">
        <option value="Primera" <?= ($cita_editar['tipo_cita'] ?? '')==='Primera'?'selected':'' ?>>Primera</option>
        <option value="Segunda" <?= ($cita_editar['tipo_cita'] ?? '')==='Segunda'?'selected':'' ?>>Segunda</option>
    </select><br>

<label>Vehículo:</label>
<select name="vehiculo">
    <option value="">Sin asignar</option>
    <?php
    $flota_usuario = $_SESSION['flota'] ?? '';
    foreach ($vehiculos as $vehiculo):
        // Mostrar solo vehículos de la flota del usuario o el vehículo ya asignado a la cita
        if (!$is_superadmin && isset($vehiculo['flota']) && strtoupper(trim($vehiculo['flota'])) !== strtoupper(trim($flota_usuario)) && $vehiculo['matricula'] !== ($cita_editar['vehiculo'] ?? '')) continue;
    ?>
        <option value="<?= htmlspecialchars($vehiculo['matricula']) ?>" <?= ($cita_editar['vehiculo'] ?? '') === $vehiculo['matricula'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($vehiculo['vehiculo']) ?> - <?= htmlspecialchars($vehiculo['matricula']) ?>
        </option>
    <?php endforeach; ?>
</select><br>

    <?php if ($is_superadmin): ?>
        <label>Flota:</label>
        <select name="flota_cita" required>
            <option value="">Seleccionar Flota</option>
            <?php
            $usuarios_file = 'usuarios.json';
            $flotas = [];
            if (file_exists($usuarios_file)) {
                $usuarios = json_decode(file_get_contents($usuarios_file), true);
                foreach ($usuarios as $u) {
                    if (isset($u['flota']) && !in_array($u['flota'], $flotas)) {
                        $flotas[] = $u['flota'];
                    }
                }
            }
            foreach ($flotas as $flota):
            ?>
                <option value="<?= htmlspecialchars($flota) ?>" <?= ($cita_editar['flota'] ?? '') === $flota ? 'selected' : '' ?>>
                    <?= htmlspecialchars($flota) ?>
                </option>
            <?php endforeach; ?>
        </select><br>
    <?php endif; ?>

    <input type="submit" value="Guardar Cambios">
</form>

<?php if (isset($error)): ?>
    <p class="rojo_intenso"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<h4 class="small" style="text-align:left; margin-top:12px;"><?= htmlspecialchars($version_text) ?></h4>
<p class="small" style="text-align:left;"><?= htmlspecialchars($autor_text) ?></p>

</body>
</html>