<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit();
}

$is_colab = isset($_SESSION['tipo']) && in_array($_SESSION['tipo'], ['Colaborador', 'Administrador', 'SuperAdministrador']);
$is_admin = isset($_SESSION['tipo']) && in_array($_SESSION['tipo'], ['Administrador', 'SuperAdministrador']);
$is_superadmin = $_SESSION['tipo'] === 'SuperAdministrador';

$flota_usuario = $_SESSION['flota'] ?? null;

// =====================
// CARGAR VEHÍCULOS
// =====================
$vehiculos_file = 'vehiculos.json';
if (!file_exists($vehiculos_file)) file_put_contents($vehiculos_file, json_encode([]));
$vehiculos = json_decode(file_get_contents($vehiculos_file), true);

// =====================
// CARGAR USUARIOS (para flotas) - Solo superadmin
// =====================
$usuarios_file = 'usuarios.json';
$flotas_existentes = [];
if($is_superadmin && file_exists($usuarios_file)) {
    $usuarios_data = json_decode(file_get_contents($usuarios_file), true);
    foreach($usuarios_data as $u) {
        if(isset($u['flota']) && !in_array($u['flota'], $flotas_existentes)) {
            $flotas_existentes[] = $u['flota'];
        }
    }
}

// =====================
// PROCESAR FORMULARIO NUEVO VEHÍCULO
// =====================
if($is_colab && $_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['vehiculo'])) {
    $nuevo_vehiculo = [
        'vehiculo' => $_POST['vehiculo'],
        'matricula' => $_POST['matricula'],
        'tipo' => $_POST['tipo'],
        'estado' => $_POST['estado'],
        'caducidad_itv' => $_POST['caducidad_itv'],
        'flota' => $is_superadmin ? $_POST['flota'] : $flota_usuario
    ];

    $vehiculos[] = $nuevo_vehiculo;
    file_put_contents($vehiculos_file, json_encode($vehiculos, JSON_PRETTY_PRINT));
    header('Location: vehiculos.php');
    exit();
}

// =====================
// FUNCIONES
// =====================
function calcular_dias_restantes($caducidad_itv) {
    $fecha_actual = new DateTime('today');
    $fecha_caducidad = new DateTime($caducidad_itv);
    $fecha_caducidad->setTime(0,0,0);
    $intervalo = $fecha_actual->diff($fecha_caducidad);
    return (int)$intervalo->format('%r%a');
}

function obtener_color_y_texto($vehiculo) {
    $estado = $vehiculo['estado'];
    $dias = calcular_dias_restantes($vehiculo['caducidad_itv']);
    if ($estado === 'ITV RECHAZADA') return ['color'=>'rojo_intenso','texto_dias'=>'ITV RECHAZADA'];
    if ($estado === 'BAJA') return ['color'=>'negro','texto_dias'=>'-'];
    if ($dias < 0) return ['color'=>'rojo_intenso','texto_dias'=>'Caducada hace '.abs($dias).' día'.(abs($dias)==1?'':'s')];
    if ($dias <= 1) return ['color'=>'rojo_intenso','texto_dias'=>$dias.' día'.($dias==1?'':'s')];
    if ($dias < 10) return ['color'=>'naranja_intenso','texto_dias'=>$dias.' días'];
    if ($dias <= 20) return ['color'=>'naranja_suave','texto_dias'=>$dias.' días'];
    if ($dias <= 35) return ['color'=>'azul','texto_dias'=>$dias.' días'];
    return ['color'=>'verde','texto_dias'=>$dias.' días'];
}

function formatear_fecha($fecha) {
    $f = DateTime::createFromFormat('Y-m-d', $fecha);
    return $f ? $f->format('d/m/Y') : $fecha;
}

// =====================
// FILTRAR VEHÍCULOS SEGÚN FLOTA
// =====================
if (!$is_superadmin && $flota_usuario) {
    $vehiculos = array_filter($vehiculos, fn($v) => strtoupper($v['flota'] ?? '') === strtoupper($flota_usuario));
}

// =====================
// ORDENAR VEHÍCULOS

$orden = $_GET['orden'] ?? 'vehiculo';

usort($vehiculos, function($a, $b) use ($orden){

    switch($orden){

        case 'matricula':
            return strcasecmp($a['matricula'], $b['matricula']);

        case 'tipo':
            return strcasecmp($a['tipo'], $b['tipo']);

        case 'estado':
            return strcasecmp($a['estado'], $b['estado']);

        case 'caducidad':
            return strtotime($a['caducidad_itv']) - strtotime($b['caducidad_itv']);

        case 'dias':
            return calcular_dias_restantes($a['caducidad_itv']) - calcular_dias_restantes($b['caducidad_itv']);

        case 'vehiculo':
        default:
            return strcasecmp($a['vehiculo'], $b['vehiculo']);
    }
});

// =====================
// VERSION Y AUTOR
$version = 'v.1.0'; $autor = 'Desconocido';
if(file_exists('version.xk')){
    $lineas = file('version.xk', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if(isset($lineas[0])) $version=$lineas[0];
    if(isset($lineas[1])) $autor=$lineas[1];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>ITVGestion</title>
<link rel="icon" href="images/logo.webp">
<link rel="manifest" href="manifest.json">
<link rel="apple-touch-icon" sizes="180x180" href="images/logo.webp">
<link rel="stylesheet" href="style.css">

<style>
body { margin:15px; font-family:Arial,sans-serif; }
table{border-collapse:collapse;width:100%;}
th,td{border:1px solid #ccc;padding:8px;vertical-align:top;}
td.dias-numero{white-space:nowrap;}
ul{margin:0;padding-left:18px;}
.user-info{
    position:fixed; top:10px; right:15px; text-align:right; font-size:14px;
    background:rgba(255,255,255,0.6); padding:5px 10px; border-radius:8px;
}
.user-info strong{display:block;}
.user-info small{color:#4a90e2;font-weight:bold;}
.boton-editar{background:#4CAF50;color:white;padding:3px 7px;border-radius:4px;text-decoration:none;}
.boton-eliminar{background:#cc0000;color:white;padding:3px 7px;border-radius:4px;text-decoration:none;}
input, select {padding:5px; margin:2px 0; border-radius:4px;}
input[type="submit"] {cursor:pointer; background:#4CAF50;color:white;border:none;padding:5px 8px;}
@media (prefers-color-scheme: dark){
    body{background:#000;color:#ddd;}
    h1,h2,h3,h4,p,strong{color:#ddd;}
    th{background:#1c75bc;color:#fff;}
    .menu img{filter: invert(1) hue-rotate(180deg);}
    .user-info{background:rgba(0,0,0,0.5);}
    .user-info small{color:#3399ff;}
}
.negro{background:black;color:grey;}
.rojo_intenso{background:#cc0000;color:white;}
.naranja_intenso{background:#ff6600;color:white;}
.naranja_suave{background:#ffae0d;color:white;}
.azul{background:#3399ff;color:white;}
.verde{background:#4CAF50;color:white;}

/* FORMULARIO estilo citas.php */
input, select { 
    padding:5px; 
    margin:2px 0; 
    border-radius:4px; 
    border:1px solid #ccc; 
    background:#fff; 
    color:#000; 
}
input[type="submit"] { 
    cursor:pointer; 
    background:#4a90e2; 
    color:#fff; 
    border:none; 
    padding:6px 12px; 
    border-radius:4px; 
}
input[type="submit"]:hover { 
    background:#1c75bc; 
}

/* MODO OSCURO */
@media (prefers-color-scheme: dark){
    input, select { 
        background:#111; 
        color:#fff; 
        border:1px solid #555; 
    }
    input[type="submit"] { 
        background: linear-gradient(135deg,#1c75bc,#0066cc); 
        border:1px solid #005bb5; 
        color:#fff; 
    }
    input[type="submit"]:hover { 
        background: linear-gradient(135deg,#005bb5,#1c75bc); 
    }
}

</style>
</head>
<body>

<div class="user-info">
    <strong><?= htmlspecialchars($_SESSION['usuario']) ?> | <?= htmlspecialchars($_SESSION['tipo']) ?></strong>
    <small><?= $is_superadmin ? "Todas las flotas" : ($flota_usuario ? strtoupper($flota_usuario) : "Sin flota asignada") ?></small>
    <div id="fecha-hora"></div>
</div>

<h1><img src="images/logo.webp" width="30" style="vertical-align: middle;"> Gestionar Vehículos</h1>
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

<script>
function actualizarFechaHora(){
    const d=new Date();
    document.getElementById('fecha-hora').innerText =
        d.toLocaleDateString('es-ES')+' '+d.toLocaleTimeString('es-ES');
}
actualizarFechaHora();
setInterval(actualizarFechaHora,1000);
</script>

<?php if($is_colab): ?>
<h2>Añadir Nuevo Vehículo</h2>
<form method="POST">
    <label>Vehículo:</label><input type="text" name="vehiculo" required><br>
    <label>Matrícula:</label><input type="text" name="matricula" required><br>
    <label>Tipo:</label>
    <select name="tipo" required>
        <option value="Turismo, Transporte mercancías hasta 3500 kg y cuadriciclos">Turismo, Transporte mercancías hasta 3500 kg y cuadriciclos</option>
        <option value="Transporte mercancías más de 3500 kg">Transporte mercancías más de 3500 kg</option>
        <option value="Cabeza tractora + Remolque">Cabeza tractora + Remolque</option>
        <option value="Autobuses y microbuses">Autobuses y microbuses</option>
        <option value="Verificación taxímetro">Verificación taxímetro</option>
        <option value="Periódica taxi con verificación taxímetro">Periódica taxi con verificación taxímetro</option>
        <option value="Periódica taxi sin verificación taxímetro">Periódica taxi sin verificación taxímetro</option>
        <option value="Ciclomotores de 2 y 3 ruedas, motocicletas">Ciclomotores de 2 y 3 ruedas, motocicletas</option>
        <option value="Quadsvehículos similares y ATVs">Quadsvehículos similares y ATVs</option>
        <option value="Obras y Servicios (excepto quadsvehículos similares y ATVs)">Obras y Servicios (excepto quadsvehículos similares y ATVs)</option>
        <option value="Tractor Sin Remolque (Agrícolas y de Obras y Servicios)">Tractor Sin Remolque (Agrícolas y de Obras y Servicios)</option>
        <option value="Tractor Con Remolque (Agrícolas y de Obras y Servicios)">Tractor Con Remolque (Agrícolas y de Obras y Servicios)</option>
    </select><br>
    <label>Estado:</label>
    <select name="estado">
        <option value="ACTIVO">ACTIVO</option>
        <option value="ITV RECHAZADA">ITV RECHAZADA</option>
        <option value="BAJA">BAJA</option>
    </select><br>
    <label>Caducidad ITV:</label><input type="date" name="caducidad_itv" required><br>

    <?php if($is_superadmin): ?>
        <label>Flota:</label>
        <select name="flota" required>
            <?php foreach($flotas_existentes as $f): ?>
                <option value="<?= htmlspecialchars($f) ?>"><?= htmlspecialchars($f) ?></option>
            <?php endforeach; ?>
        </select><br>
    <?php endif; ?>
    <br>
    <input type="submit" value="Añadir Vehículo">
</form>
<hr>
<?php endif; ?>

<h2>Lista de Vehículos</h2>
<form method="GET">
    <label>Ordenar por:</label>
    <select name="orden" onchange="this.form.submit()">
        <option value="vehiculo" <?= ($orden ?? '')=='vehiculo'?'selected':'' ?>>Vehículo</option>
        <option value="matricula" <?= ($orden ?? '')=='matricula'?'selected':'' ?>>Matrícula</option>
        <option value="tipo" <?= ($orden ?? '')=='tipo'?'selected':'' ?>>Tipo</option>
        <option value="estado" <?= ($orden ?? '')=='estado'?'selected':'' ?>>Estado</option>
        <option value="caducidad" <?= ($orden ?? '')=='caducidad'?'selected':'' ?>>Caducidad ITV</option>
        <option value="dias" <?= ($orden ?? '')=='dias'?'selected':'' ?>>Días</option>
    </select>
</form>
<br>
<table>
<thead>
<tr>
<th>Vehículo</th><th>Matrícula</th><th>Tipo</th><th>Estado</th><th>Caducidad ITV</th><th>Días</th>
<?php if($is_colab): ?><th>Acciones</th><?php endif; ?>
</tr>
</thead>
<tbody>
<?php foreach($vehiculos as $v):
$info = obtener_color_y_texto($v);
$puede_editar = $is_superadmin || ($is_colab && strtoupper($v['flota']??'')===strtoupper($flota_usuario));
$puede_eliminar = $is_superadmin || ($is_admin && strtoupper($v['flota']??'')===strtoupper($flota_usuario));
?>
<tr class="<?= $info['color'] ?>">
<td><?= htmlspecialchars($v['vehiculo']) ?></td>
<td><?= htmlspecialchars($v['matricula']) ?></td>
<td><?= htmlspecialchars($v['tipo']) ?></td>
<td><?= htmlspecialchars($v['estado']) ?></td>
<td><?= formatear_fecha($v['caducidad_itv']) ?></td>
<td><?= htmlspecialchars($info['texto_dias']) ?></td>
<?php if($is_colab): ?>
<td>
<?php if($puede_editar): ?>
<a href="editar_vehiculo.php?id=<?= urlencode($v['matricula']) ?>" class="boton-editar">Editar</a>
<?php endif; ?>
<?php if($puede_eliminar): ?>
<a href="eliminar_vehiculo.php?id=<?= urlencode($v['matricula']) ?>" class="boton-eliminar">Eliminar</a>
<?php endif; ?>
</td>
<?php endif; ?>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<h4 class="small" style="text-align:left; margin:4px 0;"><?= htmlspecialchars($version) ?></h4>
<p class="small" style="text-align:left; margin:0;"><?= htmlspecialchars($autor) ?></p>

</body>
</html>
