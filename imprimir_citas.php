<?php
session_start();

// Verificar login
if (!isset($_SESSION['usuario'])) {
    header('Location: index.php');
    exit();
}

$is_admin = isset($_SESSION['tipo']) && in_array($_SESSION['tipo'], ['Administrador', 'SuperAdministrador']);
$is_superadmin = isset($_SESSION['tipo']) && $_SESSION['tipo'] === 'SuperAdministrador';

// Flota igual que en index.php
$flota_usuario = $_SESSION['flota'] ?? null;
$flota_texto = $is_superadmin ? "Todas las flotas" : ($flota_usuario ? strtoupper($flota_usuario) : "Sin flota asignada");

// =====================
// CARGA DE VEHÍCULOS
// =====================
$vehiculos_file = 'vehiculos.json';
$vehiculos = file_exists($vehiculos_file)
    ? json_decode(file_get_contents($vehiculos_file), true)
    : [];

// =====================
// CARGA DE CITAS
// =====================
$citas_file = 'citas.json';
$citas = file_exists($citas_file)
    ? json_decode(file_get_contents($citas_file), true)
    : [];

// =====================
// FILTRAR CITAS POR FLOTA (NO LOS VEHÍCULOS)
// =====================
if (!$is_superadmin && $flota_usuario) {
    $citas = array_filter($citas, function($c) use ($flota_usuario) {
        return isset($c['flota']) && strtoupper(trim($c['flota'])) === strtoupper(trim($flota_usuario));
    });
}

// =====================
// MES / AÑO
// =====================
$mes_actual  = date('m');
$anio_actual = date('Y');
$mes  = $_GET['mes']  ?? $mes_actual;
$anio = $_GET['anio'] ?? $anio_actual;

// =====================
// MESES EN ESPAÑOL
// =====================
$meses_es = [
    1=>'Enero', 2=>'Febrero', 3=>'Marzo', 4=>'Abril', 5=>'Mayo', 6=>'Junio',
    7=>'Julio', 8=>'Agosto', 9=>'Septiembre', 10=>'Octubre', 11=>'Noviembre', 12=>'Diciembre'
];

// =====================
// FECHA LÍMITE (ahora - 1 hora)
// =====================
$ahora_menos_una_hora = new DateTime();
$ahora_menos_una_hora->modify('-1 hour');

// =====================
// FILTRAR CITAS
// =====================
$citas_filtradas = array_filter($citas, function ($cita) use ($mes, $anio, $ahora_menos_una_hora) {
    if (empty($cita['fecha_cita']) || empty($cita['hora_cita'])) return false;
    $fecha_hora_cita = DateTime::createFromFormat('Y-m-d H:i', $cita['fecha_cita'] . ' ' . $cita['hora_cita']);
    if (!$fecha_hora_cita) return false;
    if ($fecha_hora_cita < $ahora_menos_una_hora) return false;
    return $fecha_hora_cita->format('m') == $mes && $fecha_hora_cita->format('Y') == $anio;
});

// =====================
// ORDENAR CITAS
// =====================
usort($citas_filtradas, function ($a, $b) {
    $fa = strtotime($a['fecha_cita'] . ' ' . ($a['hora_cita'] ?? '00:00'));
    $fb = strtotime($b['fecha_cita'] . ' ' . ($b['hora_cita'] ?? '00:00'));
    return $fa <=> $fb;
});

// =====================
// FUNCIONES
// =====================
function formatear_fecha($fecha) {
    $f = DateTime::createFromFormat('Y-m-d', $fecha);
    return $f ? $f->format('d/m/Y') : $fecha;
}
function mostrar_vehiculo_completo($matricula, $vehiculos) {
    foreach ($vehiculos as $v) {
        if ($v['matricula'] === $matricula) return $v['vehiculo'] . ' - ' . $v['matricula'];
    }
    return '-';
}
function obtener_caducidad_itv($matricula, $vehiculos) {
    foreach ($vehiculos as $v) {
        if ($v['matricula'] === $matricula) return formatear_fecha($v['caducidad_itv']);
    }
    return '-';
}
function obtener_dia_semana_abrev($fecha) {
    $dias = [1=>'lu',2=>'ma',3=>'mi',4=>'ju',5=>'vi',6=>'sa',7=>'do'];
    $f = DateTime::createFromFormat('Y-m-d', $fecha);
    return $f ? $dias[(int)$f->format('N')] : '';
}

// =====================
// TEXTOS
// =====================
$meses_txt = [
    '01'=>'Enero','02'=>'Febrero','03'=>'Marzo','04'=>'Abril',
    '05'=>'Mayo','06'=>'Junio','07'=>'Julio','08'=>'Agosto',
    '09'=>'Septiembre','10'=>'Octubre','11'=>'Noviembre','12'=>'Diciembre'
];
$fecha_impresion = date('d/m/Y H:i');

// =====================
// VERSION
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
<title>ITVGestion</title>
<link rel="icon" href="images/logo.webp">
<link rel="stylesheet" href="style.css">
<style>
body{margin:15px;font-family:Arial,sans-serif;color:#000;}

/* Cuadro de usuario */
.user-info{
    position:fixed;top:10px;right:15px;text-align:right;font-size:14px;
    background:rgba(255,255,255,0.6);padding:5px 10px;border-radius:8px;
}
.user-info strong{display:block;}
.user-info small{color:#4a90e2;font-weight:bold;}

/* Título y línea azul */
h1 img{vertical-align:middle;}
hr.linea-azul{border:1px solid #4a90e2;margin:10px 0 20px 0;}

/* Tabla */
table{border-collapse:collapse;width:100%;}
th,td{border:1px solid #ccc;padding:2px 4px;font-size:15px;text-align:left;line-height:1.05;}
th{background:#eee;}
.fila-roja{border-left:5px solid #c00000;}
.fila-azul{border-left:5px solid #004aad;}
.fila-amarilla{border-left:5px solid #c9a600;}
.estado{font-weight:bold;text-transform:uppercase;font-size:10px;}
.matricula{font-size:9px;}
.dia-semana{font-weight:bold;}
.dia-vi,.dia-sa,.dia-do{color:#c00000!important;}

/* Modo oscuro */
@media (prefers-color-scheme: dark){
    body{background:#000;color:#ddd;}
    h1,h2,h3,h4,p,strong,td,th{color:#ddd;}
    th{background:#222;color:#fff;}
    .menu img{filter:invert(1) hue-rotate(180deg);}
    h1 img{filter:none;}
    .user-info{background:rgba(0,0,0,0.5);}
    .user-info small{color:#3399ff;}
    .dia-vi,.dia-sa,.dia-do{color:#ff4d4d!important;}
}

/* Impresión */
@media print{
    @page{size:A4 portrait;margin:12mm;}
    body{font-size:15px;}
    .menu,form,button,.small,.no-imprimir,.user-info{display:none!important;}
    h1{margin:0 0 6px 0;font-size:16px;}
    tbody tr td{font-weight:bold;}
    table,tr,td,th{page-break-inside:avoid!important;}
}
</style>
</head>
<body>

<div class="user-info">
    <strong><?=htmlspecialchars($_SESSION['usuario'])?> | <?=htmlspecialchars($_SESSION['tipo'])?></strong>
    <small><?=htmlspecialchars($flota_texto)?></small>
    <div id="fecha-hora"></div>
</div>

<h1><img src="images/logo.webp" width="30" style="vertical-align:middle;"> Citas ITV</h1>
<hr class="linea-azul">

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

<form method="GET" style="margin:15px 0;">
    <select name="mes">
        <?php foreach($meses_txt as $num=>$nombre): ?>
        <option value="<?=$num?>" <?=$mes==$num?'selected':''?>><?=$nombre?></option>
        <?php endforeach; ?>
    </select>
    <input type="number" name="anio" value="<?=$anio?>" style="width:80px;">
    <button type="submit">Mostrar</button>
    <button type="button" onclick="window.print()">Imprimir</button>
</form>

<div class="print-header">
Impreso el <?= $fecha_impresion ?>
</div>

<table>
<thead>
<tr>
    <th>Día</th><th>Vehículo</th><th>Tipo</th><th>Fecha</th><th>Hora</th>
    <th>Estación</th><th>Caducidad</th><th>Estado</th>
</tr>
</thead>
<tbody>
<?php if(empty($citas_filtradas)): ?>
<tr><td colspan="8">No hay citas para este periodo</td></tr>
<?php else: ?>
<?php foreach($citas_filtradas as $cita):
$clase_fila='';$estado='NORMAL';
$tipo=strtolower($cita['tipo_cita']??'');$vehiculo=$cita['vehiculo']??'';
$fecha_cita=DateTime::createFromFormat('Y-m-d',$cita['fecha_cita']);
$caducidad=null;
foreach($vehiculos as $v){if($v['matricula']===$vehiculo){$caducidad=DateTime::createFromFormat('Y-m-d',$v['caducidad_itv']);break;}}
if($tipo==='primera'&&$fecha_cita&&$caducidad&&$fecha_cita>$caducidad){$clase_fila='fila-roja';$estado='PRIMERA INSPECCIÓN FUERA DE PLAZO';}
elseif($tipo==='segunda'){$clase_fila='fila-azul';$estado='SEGUNDA INSPECCIÓN';}
elseif($tipo==='primera'&&empty($vehiculo)){$clase_fila='fila-amarilla';$estado='PRIMERA INSPECCIÓN SIN VEHÍCULO';}
$dia_abrev=obtener_dia_semana_abrev($cita['fecha_cita']);$dia_class='dia-'.$dia_abrev;
?>
<tr class="<?=$clase_fila?>">
<td class="dia-semana <?=$dia_class?>"><?=$dia_abrev?></td>
<td>
<?php
$vehiculo_completo=mostrar_vehiculo_completo($vehiculo,$vehiculos);
if($vehiculo_completo!=='-'){
$partes=explode(' - ',$vehiculo_completo,2);
echo htmlspecialchars($partes[0]);
if(isset($partes[1]))echo' - <span class="matricula">'.htmlspecialchars($partes[1]).'</span>';
}else echo'-';
?>
</td>
<td><?=htmlspecialchars($cita['tipo_cita']??'-')?></td>
<td><?=formatear_fecha($cita['fecha_cita'])?></td>
<td><?=htmlspecialchars($cita['hora_cita'])?></td>
<td><?=htmlspecialchars($cita['estacion_cita']??'-')?></td>
<td><?=obtener_caducidad_itv($vehiculo,$vehiculos)?></td>
<td class="estado"><?=$estado?></td>
</tr>
<?php endforeach;endif;?>
</tbody>
</table>

<h4 class="small no-imprimir" style="margin-top:12px;"><?=htmlspecialchars($version)?></h4>
<p class="small no-imprimir"><?=htmlspecialchars($autor)?></p>

<script>
function actualizarFechaHora(){
 const d=new Date();
 document.getElementById('fecha-hora').innerText=d.toLocaleDateString('es-ES')+' '+d.toLocaleTimeString('es-ES');
}
actualizarFechaHora();
setInterval(actualizarFechaHora,1000);
</script>
</body>
</html>