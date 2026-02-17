<?php
session_start();

// Verificar login y permisos
if (!isset($_SESSION['usuario']) || !in_array($_SESSION['tipo'], ['Administrador','SuperAdministrador','Colaborador'])) {
    header('Location: index.php'); exit();
}

$is_admin = in_array($_SESSION['tipo'], ['Administrador','SuperAdministrador']);
$is_superadmin = $_SESSION['tipo'] === 'SuperAdministrador';
$is_colab = in_array($_SESSION['tipo'], ['Colaborador','Administrador','SuperAdministrador']);

// Obtener matrícula del vehículo
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: vehiculos.php'); exit();
}
$id_vehiculo = $_GET['id'];

// Cargar vehículos
$vehiculos_file = 'vehiculos.json';
if (!file_exists($vehiculos_file)) die("El archivo de vehículos no existe.");
$vehiculos = json_decode(file_get_contents($vehiculos_file), true);

// Buscar vehículo a editar
$vehiculo_editar = null;
foreach ($vehiculos as &$vehiculo) {
    if ($vehiculo['matricula'] === $id_vehiculo) {
        $vehiculo_editar = &$vehiculo; break;
    }
}
if ($vehiculo_editar === null) die("No se encontró el vehículo: " . htmlspecialchars($id_vehiculo));

// Funciones
function calcular_dias_restantes($caducidad_itv) {
    $fecha_actual = new DateTime('today');
    $fecha_caducidad = new DateTime($caducidad_itv);
    $fecha_caducidad->setTime(0,0,0);
    $intervalo = $fecha_actual->diff($fecha_caducidad);
    return (int)$intervalo->format('%r%a');
}
function obtener_color_y_texto($vehiculo) {
    $estado = $vehiculo['estado'];
    $dias_restantes = calcular_dias_restantes($vehiculo['caducidad_itv']);
    if ($estado==='ITV RECHAZADA') return ['color'=>'rojo_intenso','texto_dias'=>'ITV RECHAZADA'];
    if ($estado==='BAJA') return ['color'=>'negro','texto_dias'=>'-'];
    if ($dias_restantes<0) return ['color'=>'rojo_intenso','texto_dias'=>'Caducada hace '.abs($dias_restantes).' día'.(abs($dias_restantes)==1?'':'s')];
    if ($dias_restantes<=1) return ['color'=>'rojo_intenso','texto_dias'=>$dias_restantes.' día'.($dias_restantes==1?'':'s')];
    if ($dias_restantes<10) return ['color'=>'naranja_intenso','texto_dias'=>$dias_restantes.' días'];
    if ($dias_restantes<=20) return ['color'=>'naranja_suave','texto_dias'=>$dias_restantes.' días'];
    if ($dias_restantes<=35) return ['color'=>'azul','texto_dias'=>$dias_restantes.' días'];
    return ['color'=>'verde','texto_dias'=>$dias_restantes.' días'];
}
function formatear_fecha($fecha) {
    $d = DateTime::createFromFormat('Y-m-d',$fecha);
    return $d ? $d->format('d/m/Y') : $fecha;
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (!empty($_POST['estado']) && !empty($_POST['caducidad_itv'])) {
        $vehiculo_editar['estado'] = $_POST['estado'];
        $vehiculo_editar['caducidad_itv'] = $_POST['caducidad_itv'];
        if (file_put_contents($vehiculos_file,json_encode($vehiculos,JSON_PRETTY_PRINT))) {
            header('Location: vehiculos.php'); exit();
        } else $error="No se pudieron guardar los cambios.";
    } else $error="Todos los campos son obligatorios.";
}

// Cargar versión y autor
$version_file='version.xk';
$version='v.1.0'; $autor='';
if(file_exists($version_file)){
    $lines=file($version_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if(isset($lines[0])) $version=$lines[0];
    if(isset($lines[1])) $autor=$lines[1];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Editar Vehículo</title>
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
</style>
</head>
<body>

<div class="user-info" style="position:fixed;top:10px;right:15px;text-align:right;font-size:14px;">
    <strong><?= $_SESSION['usuario'] ?> | <?= $_SESSION['tipo'] ?></strong>
        <div id="fecha-hora"></div>
</div>

<br>

<h1>
<img src="images/logo.webp" width="30" style="vertical-align:middle;"> Editar Vehículo
</h1>
  
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

<h2>Editar Vehículo</h2>
<?php if(isset($error)): ?><p style="color:red;"><?= htmlspecialchars($error) ?></p><?php endif; ?>

<form method="POST" style="max-width:400px;">
    <label>Vehículo:</label>
    <input type="text" value="<?= htmlspecialchars($vehiculo_editar['vehiculo']) ?>" disabled>
  <br>
    <label>Matrícula:</label>
    <input type="text" value="<?= htmlspecialchars($vehiculo_editar['matricula']) ?>" disabled>
  <br>
    <label>Estado:</label>
    <select name="estado">
        <option value="ACTIVO" <?= $vehiculo_editar['estado']==='ACTIVO'?'selected':'' ?>>ACTIVO</option>
        <option value="ITV RECHAZADA" <?= $vehiculo_editar['estado']==='ITV RECHAZADA'?'selected':'' ?>>ITV RECHAZADA</option>
        <option value="BAJA" <?= $vehiculo_editar['estado']==='BAJA'?'selected':'' ?>>BAJA</option>
    </select>
 <br>
    <label>Caducidad ITV:</label>
    <input type="date" name="caducidad_itv" value="<?= htmlspecialchars($vehiculo_editar['caducidad_itv']) ?>" required>
  <br>
    <input type="submit" value="Guardar Cambios">
</form>

<h4 class="small" style="margin-top:12px;text-align:left;"><?= htmlspecialchars($version) ?></h4>
<p class="small" style="text-align:left;"><?= htmlspecialchars($autor) ?></p>

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