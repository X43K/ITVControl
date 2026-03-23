<?php
session_start();

// Verificar login
if (!isset($_SESSION['usuario'])) {
    header('Location: index.php');
    exit();
}

// Roles
$is_admin = isset($_SESSION['tipo']) && in_array($_SESSION['tipo'], ['Administrador', 'SuperAdministrador']);
$is_superadmin = $_SESSION['tipo'] === 'SuperAdministrador';

// Flota
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
// FILTRAR POR FLOTA
// =====================
if (!$is_superadmin && $flota_usuario) {
    $vehiculos = array_filter($vehiculos, function($v) use ($flota_usuario) {
        return isset($v['flota']) && strtoupper(trim($v['flota'])) === strtoupper(trim($flota_usuario));
    });
}

// =====================
// SELECCIÓN DE MES / AÑO
// =====================
$mes_actual = date('m');
$anio_actual = date('Y');
$mes_seleccionado = $_GET['mes'] ?? $mes_actual;
$anio_seleccionado = $_GET['anio'] ?? $anio_actual;

// =====================
// MESES EN ESPAÑOL
// =====================
$meses_es = [
    1=>'Enero', 2=>'Febrero', 3=>'Marzo', 4=>'Abril', 5=>'Mayo', 6=>'Junio',
    7=>'Julio', 8=>'Agosto', 9=>'Septiembre', 10=>'Octubre', 11=>'Noviembre', 12=>'Diciembre'
];

// =====================
// FUNCIONES
// =====================
function formatear_fecha($fecha) {
    $f = DateTime::createFromFormat('Y-m-d', $fecha);
    return $f ? $f->format('d/m/Y') : $fecha;
}

// =====================
// FILTRAR VEHÍCULOS POR MES/AÑO
// =====================
$vehiculos_filtrados = array_filter($vehiculos, function($v) use ($mes_seleccionado, $anio_seleccionado) {
    $fecha = DateTime::createFromFormat('Y-m-d', $v['caducidad_itv']);
    return $fecha && $fecha->format('m') == str_pad($mes_seleccionado,2,'0',STR_PAD_LEFT)
                 && $fecha->format('Y') == $anio_seleccionado;
});

// =====================
// ORDENAR POR FECHA
// =====================
usort($vehiculos_filtrados, function($a, $b) {
    $fechaA = strtotime($a['caducidad_itv']);
    $fechaB = strtotime($b['caducidad_itv']);
    return $fechaA <=> $fechaB;
});

// =====================
// HORIZONTE SEGURO
// =====================
$frecuencia_meses = 24;
$tipos_detectados = [];

foreach ($vehiculos as $v) {
    $tipo = strtolower($v['tipo'] ?? '');

    if (str_contains($tipo, 'autobus') ||
        str_contains($tipo, 'microbus') ||
        str_contains($tipo, 'mercador') ||
        str_contains($tipo, 'tractora') ||
        str_contains($tipo, 'remolque')) {
        $frecuencia_meses = min($frecuencia_meses, 6);
        $tipos_detectados[] = 'Semestral';
    } elseif (str_contains($tipo, 'turismo') ||
              str_contains($tipo, 'taxi') ||
              str_contains($tipo, 'agricola') ||
              str_contains($tipo, 'obras')) {
        $frecuencia_meses = min($frecuencia_meses, 12);
        $tipos_detectados[] = 'Anual';
    } elseif (str_contains($tipo, 'moto') ||
              str_contains($tipo, 'quad') ||
              str_contains($tipo, 'ciclomotor')) {
        $frecuencia_meses = min($frecuencia_meses, 24);
        $tipos_detectados[] = 'Bienal';
    }
}

$fecha_limite_obj = (new DateTime())->modify("+$frecuencia_meses months");
$fecha_limite_obj->modify('-1 month');

$mes_maximo = (int)$fecha_limite_obj->format('m');
$anio_maximo = $fecha_limite_obj->format('Y');

$fecha_impresion = date('d/m/Y H:i');

// =====================
// INFO DE IMPRESIÓN Y VERSION
// =====================
$fecha_impresion = date('d/m/Y H:i');

$version_file = 'version.xk';
$version = 'v.1.0';
$autor = '';
if (file_exists($version_file)) {
    $lines = file($version_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (isset($lines[0])) $version = $lines[0];
    if (isset($lines[1])) $autor = $lines[1];
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
table{border-collapse:collapse;width:100%;margin-top:10px;}
th,td{border:1px solid #ccc;padding:2px 4px;font-size:15px;text-align:left;line-height:1.05;}
th{background:#eee;}

/* Modo oscuro */
@media (prefers-color-scheme: dark){
    body{background:#000;color:#ddd;}
    h1,h2,h3,h4,p,strong,td,th{color:#ddd;}
    th{background:#222;color:#fff;}
    .menu img{filter:invert(1) hue-rotate(180deg);}
    h1 img{filter:none;}
    .user-info{background:rgba(0,0,0,0.5);}
    .user-info small{color:#3399ff;}
}

/* Impresión */
@media print{
    @page{size:A4 portrait;margin:12mm;}
    body{font-size:15px;}
    .menu,form,button,.small,.no-imprimir,.user-info{display:none!important;}
    h1{margin:0 0 6px 0;font-size:16px;color:#000!important;}
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

<h1><img src="images/logo.webp" width="30" style="vertical-align:middle;"> Caducidades ITV</h1>
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
    <label>Mes:
        <select name="mes">
            <?php for($m=1;$m<=12;$m++): ?>
                <option value="<?=$m?>" <?=$m==$mes_seleccionado?'selected':''?>><?=$meses_es[$m]?></option>
            <?php endfor; ?>
        </select>
    </label>
    <label>Año:
        <input type="number" name="anio" value="<?=$anio_seleccionado?>" min="2000" max="2100">
    </label>
    <button type="submit">Mostrar</button>
    <button type="button" onclick="window.print()">Imprimir</button>
</form>

<div class="print-header">
<?= $meses_es[(int)$mes_seleccionado] ?> <?= $anio_seleccionado ?><br>Impreso el <?= $fecha_impresion ?>
</div>

<?php if (!empty($tipos_detectados)): ?>
<div class="aviso-horizonte no-imprimir" style="color:orange;">
⚠️ Para que aparezcan todos los vehículos es seguro imprimir
<strong style="color:red;"><?= $meses_es[$mes_maximo] ?> <?= $anio_maximo ?></strong>.
</div>
<?php endif; ?>

<table>
<thead>
<tr>
    <th>Vehículo</th>
    <th>Matrícula</th>
    <th>Tipo</th>
    <th>Estado</th>
    <th>Caducidad ITV</th>
</tr>
</thead>
<tbody>
<?php if(empty($vehiculos_filtrados)): ?>
<tr><td colspan="5">No hay vehículos que caduquen en el mes seleccionado.</td></tr>
<?php else: ?>
<?php foreach($vehiculos_filtrados as $v): ?>
<tr>
    <td><?=htmlspecialchars($v['vehiculo'])?></td>
    <td><?=htmlspecialchars($v['matricula'])?></td>
    <td><?=htmlspecialchars($v['tipo'] ?? '')?></td>
    <td><?=htmlspecialchars($v['estado'])?></td>
    <td><?=formatear_fecha($v['caducidad_itv'])?></td>
</tr>
<?php endforeach; ?>
<?php endif; ?>
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
