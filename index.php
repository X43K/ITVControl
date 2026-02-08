<?php
session_start();

// Redirigir al login si no hay usuario logueado
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit();
}

$is_admin = isset($_SESSION['tipo']) && in_array($_SESSION['tipo'], ['Administrador', 'SuperAdministrador']);
$is_superadmin = isset($_SESSION['tipo']) && $_SESSION['tipo'] === 'SuperAdministrador';

// Cargar vehículos
$vehiculos_file = 'vehiculos.json';
if (!file_exists($vehiculos_file)) {
    die("El archivo de vehículos no existe.");
}
$vehiculos = json_decode(file_get_contents($vehiculos_file), true);

// Cargar citas
$citas_file = 'citas.json';
if (!file_exists($citas_file)) {
    die("El archivo de citas no existe.");
}
$citas = json_decode(file_get_contents($citas_file), true);

// =====================
// FUNCIONES
// =====================

// Calcular días restantes
function calcular_dias_restantes($caducidad_itv) {
    $fecha_actual = new DateTime('today');
    $fecha_caducidad = new DateTime($caducidad_itv);
    $fecha_caducidad->setTime(0, 0, 0);
    $intervalo = $fecha_actual->diff($fecha_caducidad);
    return (int)$intervalo->format('%r%a');
}

// Obtener citas futuras de un vehículo
function obtener_citas_vehiculo($matricula_vehiculo, $citas) {
    $fecha_actual = new DateTime();
    $resultado = [];

    foreach ($citas as $cita) {
        if ($cita['vehiculo'] === $matricula_vehiculo) {
            $dt = DateTime::createFromFormat('Y-m-d H:i', $cita['fecha_cita'].' '.$cita['hora_cita']);
            if ($dt && $dt >= $fecha_actual) {
                $cita['timestamp'] = $dt->getTimestamp();
                $resultado[] = $cita;
            }
        }
    }

    usort($resultado, fn($a, $b) => $a['timestamp'] <=> $b['timestamp']);
    return $resultado;
}

// Formatear fecha
function formatear_fecha($fecha) {
    $d = DateTime::createFromFormat('Y-m-d', $fecha);
    return $d ? $d->format('d/m/Y') : $fecha;
}

// Color y texto días
function obtener_color_y_texto($vehiculo) {
    $estado = $vehiculo['estado'];
    $dias = calcular_dias_restantes($vehiculo['caducidad_itv']);

    $color = 'verde';
    $texto = $dias.' días';

    if ($estado === 'ITV RECHAZADA') {
        return ['color'=>'rojo_intenso','texto_dias'=>'ITV RECHAZADA'];
    }
    if ($dias < 0) return ['color'=>'rojo_intenso','texto_dias'=>'ITV CADUCADA'];
    if ($dias <= 1) return ['color'=>'rojo_intenso','texto_dias'=>$dias.' día'.($dias==1?'':'s')];
    if ($dias < 10) return ['color'=>'naranja_intenso','texto_dias'=>$dias.' días'];
    if ($dias <= 20) return ['color'=>'naranja_suave','texto_dias'=>$dias.' días'];
    if ($dias <= 35) return ['color'=>'azul','texto_dias'=>$dias.' días'];

    return ['color'=>$color,'texto_dias'=>$texto];
}

// =====================
// PRÓXIMA ITV GLOBAL
// =====================
$proxima_itv = null;
$ts_min = null;
$ahora = new DateTime();

foreach ($citas as $cita) {
    $dt = DateTime::createFromFormat('Y-m-d H:i', $cita['fecha_cita'].' '.$cita['hora_cita']);
    if ($dt && $dt >= $ahora) {
        $ts = $dt->getTimestamp();
        if ($ts_min === null || $ts < $ts_min) {
            $ts_min = $ts;
            $proxima_itv = $cita;
        }
    }
}

// Filtrar vehículos visibles
$vehiculos_filtrados = array_filter($vehiculos, fn($v) =>
    in_array($v['estado'], ['ACTIVO','ITV RECHAZADA'])
);

usort($vehiculos_filtrados, fn($a,$b) =>
    calcular_dias_restantes($a['caducidad_itv']) <=> calcular_dias_restantes($b['caducidad_itv'])
);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta http-equiv="refresh" content="60">
<title>Página Principal - Gestión de ITV</title>
<link rel="icon" href="images/logo.webp">
<link rel="stylesheet" href="style.css">

<style>
.negro{background:black;color:grey}
.rojo_intenso{background:#cc0000;color:white}
.naranja_intenso{background:#ff6600;color:white}
.naranja_suave{background:#ffae0d;color:white}
.azul{background:#3399ff;color:white}
.verde{background:#4CAF50;color:white}

table{border-collapse:collapse;width:100%}
th,td{border:1px solid #ccc;padding:8px;vertical-align:top}
th{background:#eee}
ul{margin:0;padding-left:18px}

.user-info{
    position:fixed;
    top:10px;
    right:15px;
    text-align:right;
    font-size:14px;
}

/* PRÓXIMA ITV */
.proxima-itv{
    position:fixed;
    top:90px;
    right:20px;
    width:260px;
    border:2px solid #000;
    background:#f8f8f8;
}
.proxima-itv .titulo{
    background:#d9968c;
    text-align:center;
    font-weight:bold;
    font-size:20px;
    padding:8px;
}
.proxima-itv .fila{
    display:flex;
    border-top:1px solid #000;
}
.proxima-itv .label{
    width:40%;
    padding:6px;
    font-weight:bold;
    border-right:1px solid #000;
}
.proxima-itv .valor{
    width:60%;
    padding:6px;
}
</style>
</head>

<body>

<div class="user-info">
    <strong><?= $_SESSION['usuario'] ?> | <?= $_SESSION['tipo'] ?></strong>
    <div id="fecha-hora"></div>
</div>

<h1>
<img src="images/logo.webp" width="30">
Página Principal - Gestión de ITV
</h1>

<div class="menu">
    <a href="index.php"><img src="images/index.webp" width="80"></a>
    <a href="citas.php"><img src="images/citas.webp" width="80"></a>
    <a href="vehiculos.php"><img src="images/vehiculos.webp" width="80"></a>
    <?php if($is_admin): ?><a href="estaciones.php"><img src="images/estaciones.webp" width="80"></a><?php endif; ?>
    <?php if($is_superadmin): ?><a href="usuarios.php"><img src="images/usuarios.webp" width="80"></a><?php endif; ?>
    <a href="imprimir.php"><img src="images/imprimir.webp" width="80"></a>
    <a href="logout.php"><img src="images/logout.webp" width="80"></a>
</div>

<?php if($proxima_itv): ?>
<div class="proxima-itv">
    <div class="titulo">PRÓXIMA ITV</div>
    <div class="fila"><div class="label">FECHA</div><div class="valor"><?= formatear_fecha($proxima_itv['fecha_cita']) ?></div></div>
    <div class="fila"><div class="label">HORA</div><div class="valor"><?= $proxima_itv['hora_cita'] ?></div></div>
    <div class="fila"><div class="label">ESTACIÓN</div>
        <div class="valor">
            <?= $proxima_itv['estacion_cita'] ?>
            <?= $proxima_itv['tipo_cita']==='Primera'?'1ª':'2ª' ?>
        </div>
    </div>
</div>
<?php endif; ?>

<h2>Vehículos</h2>

<table>
<thead>
<tr>
<th>Vehículo</th><th>Matrícula</th><th>Tipo</th>
<th>Estado</th><th>Caducidad ITV</th>
<th>Días</th><th>Cita Asignada</th>
</tr>
</thead>
<tbody>

<?php foreach($vehiculos_filtrados as $v):
$info = obtener_color_y_texto($v);
$citas_v = obtener_citas_vehiculo($v['matricula'],$citas);
?>
<tr class="<?= $info['color'] ?>">
<td><?= $v['vehiculo'] ?></td>
<td><?= $v['matricula'] ?></td>
<td><?= $v['tipo'] ?? '-' ?></td>
<td><?= $v['estado'] ?></td>
<td><?= formatear_fecha($v['caducidad_itv']) ?></td>
<td><?= $info['texto_dias'] ?></td>
<td>
<?php if($citas_v): ?>
<ul>
<?php foreach($citas_v as $c): ?>
<li style="<?= ($proxima_itv &&
    $c['fecha_cita']===$proxima_itv['fecha_cita'] &&
    $c['hora_cita']===$proxima_itv['hora_cita'] &&
    $c['vehiculo']===$proxima_itv['vehiculo'])
    ? 'color:red;font-weight:bold;' : '' ?>">
<strong><?= formatear_fecha($c['fecha_cita']) ?></strong>
<?= $c['hora_cita'] ?> –
<?= $c['estacion_cita'] ?> <?= $c['tipo_cita']==='Primera'?'1ª':'2ª' ?>
</li>
<?php endforeach; ?>
</ul>
<?php else: ?>Sin cita<?php endif; ?>
</td>
</tr>
<?php endforeach; ?>

</tbody>
</table>

<h4 class="small" style="margin-top:12px;">ITVControl v.1.4</h4>
<p class="small">B174M3 // XaeK</p>

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
