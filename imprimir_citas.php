<?php
session_start();

// Verificar login
if (!isset($_SESSION['usuario'])) {
    header('Location: index.php');
    exit();
}

$is_admin = isset($_SESSION['tipo']) && in_array($_SESSION['tipo'], ['Administrador', 'SuperAdministrador']);
$is_superadmin = isset($_SESSION['tipo']) && $_SESSION['tipo'] === 'SuperAdministrador';

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
// MES / AÑO
// =====================
$mes_actual  = date('m');
$anio_actual = date('Y');

$mes  = $_GET['mes']  ?? $mes_actual;
$anio = $_GET['anio'] ?? $anio_actual;

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
        if ($v['matricula'] === $matricula) {
            return $v['vehiculo'] . ' - ' . $v['matricula'];
        }
    }
    return '-';
}

function obtener_caducidad_itv($matricula, $vehiculos) {
    foreach ($vehiculos as $v) {
        if ($v['matricula'] === $matricula) {
            return formatear_fecha($v['caducidad_itv']);
        }
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
// CARGA DE VERSION.XK
// =====================
$version_file = 'version.xk';
$version = 'v.1.0';
$autor = '';
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
<title>Impresion de Citas ITV</title>
<link rel="icon" href="images/logo.webp">
<link rel="stylesheet" href="style.css">
<style>
table { border-collapse: collapse; width: 100%; }
th, td { border: 1px solid #ccc; padding: 2px 4px; font-size: 15px; text-align: left; line-height: 1.05; }
th { background-color: #eee; }

.fila-roja { border-left: 5px solid #c00000; }
.fila-azul { border-left: 5px solid #004aad; }
.fila-amarilla { border-left: 5px solid #c9a600; }
.fila-azul td, .fila-azul td * { color:#666; }

.estado { font-weight:bold; text-transform:uppercase; font-size:10px; }
.matricula { font-size:9px; }

.print-header, .print-footer { display:none; }

/* Colores de días en modo claro */
.dia-lu { color:#000 !important; }
.dia-ma { color:#000 !important; }
.dia-mi { color:#000 !important; }
.dia-ju { color:#000 !important; }
.dia-vi { color:#c00000 !important; } /* viernes rojo */
.dia-sa { color:#c00000 !important; } /* sábado rojo */
.dia-do { color:#c00000 !important; } /* domingo rojo */

.dia-semana { font-weight:bold; }

/* Impresión */
@media print {
    @page { size:A4 portrait; margin:12mm; }
    body { font-size:15px; }
    .menu,
    form,
    button,
    .small,
    .no-imprimir,
    .user-info {
        display:none !important;
    }
    h1 { margin:0 0 6px 0; font-size:16px; }
    .print-header, .print-footer { display:block; font-size:12px; line-height:1.2; }
    tbody tr td { font-weight:bold; }
    .fila-azul td, .fila-azul td * { font-weight:normal; }
    table,tr,td,th { page-break-inside:avoid !important; }
}

/* ===== MODO OSCURO AUTOMÁTICO ===== */
@media (prefers-color-scheme: dark) {
    body { background:#000; color:#ddd; }
    h1,h2,h3,h4,p,strong,td,th { color:#ddd; }
    th { background:#222; color:#fff; }
    .menu img { filter: invert(1) hue-rotate(180deg); }
    h1 img { filter:none; } /* logo.webp no se invierte */

    /* Colores de días adaptados para modo oscuro */
    .dia-lu { color:#ddd !important; } 
    .dia-ma { color:#ddd !important; } 
    .dia-mi { color:#ddd !important; } 
    .dia-ju { color:#ddd !important; } 
    .dia-vi { color:#ff4d4d !important; } /* viernes */
    .dia-sa { color:#ff4d4d !important; } /* sábado */
    .dia-do { color:#ff4d4d !important; } /* domingo */
}
</style>
</head>
<body>

<div class="user-info" style="position:fixed;top:10px;right:15px;text-align:right;font-size:14px;">
    <strong><?= $_SESSION['usuario'] ?> | <?= $_SESSION['tipo'] ?></strong>
        <div id="fecha-hora"></div>
</div>

<br>

<h1>
<img src="images/logo.webp" width="30" style="vertical-align: middle;"> Citas ITV
</h1>

<br>


<div class="menu">
    <a href="index.php"><img src="images/index.webp" width="80"></a>
    <a href="citas.php"><img src="images/citas.webp" width="80"></a>
    <a href="vehiculos.php"><img src="images/vehiculos.webp" width="80"></a>
    <?php if($is_admin): ?><a href="estaciones.php"><img src="images/estaciones.webp" width="80"></a><?php endif; ?>
    <?php if($is_superadmin): ?><a href="usuarios.php"><img src="images/usuarios.webp" width="80"></a><?php endif; ?>
    <a href="imprimir.php"><img src="images/imprimir.webp" width="80"></a>
    <a href="logout.php"><img src="images/logout.webp" width="80"></a>
</div>

<br>

<!-- FORMULARIO -->
<form method="GET" style="margin:15px 0;">
    <select name="mes">
        <?php foreach ($meses_txt as $num => $nombre): ?>
            <option value="<?= $num ?>" <?= $mes == $num ? 'selected' : '' ?>><?= $nombre ?></option>
        <?php endforeach; ?>
    </select>
    <input type="number" name="anio" value="<?= $anio ?>" style="width:80px;">
    <button type="submit">Mostrar</button>
    <button type="button" onclick="window.print()">Imprimir</button>
</form>

<div class="print-header">
    <?= $meses_txt[$mes] ?> <?= $anio ?> — Impreso el <?= $fecha_impresion ?>
</div>

<!-- TABLA -->
<table>
<thead>
<tr>
    <th>Día</th>
    <th>Vehículo</th>
    <th>Tipo</th>
    <th>Fecha</th>
    <th>Hora</th>
    <th>Estación</th>
    <th>Caducidad</th>
    <th>Estado</th>
</tr>
</thead>
<tbody>

<?php if(empty($citas_filtradas)): ?>
<tr><td colspan="8">No hay citas para este periodo</td></tr>
<?php else: ?>
<?php foreach($citas_filtradas as $cita):
    $clase_fila = ''; $estado = 'NORMAL';
    $tipo = strtolower($cita['tipo_cita'] ?? '');
    $vehiculo = $cita['vehiculo'] ?? '';
    $fecha_cita = DateTime::createFromFormat('Y-m-d', $cita['fecha_cita']);
    $caducidad = null;
    foreach($vehiculos as $v){ if($v['matricula']===$vehiculo){ $caducidad=DateTime::createFromFormat('Y-m-d',$v['caducidad_itv']); break; } }
    if($tipo==='primera' && $fecha_cita && $caducidad && $fecha_cita>$caducidad){ $clase_fila='fila-roja'; $estado='PRIMERA INSPECCIÓN FUERA DE PLAZO'; }
    elseif($tipo==='segunda'){ $clase_fila='fila-azul'; $estado='SEGUNDA INSPECCIÓN'; }
    elseif($tipo==='primera' && empty($vehiculo)){ $clase_fila='fila-amarilla'; $estado='PRIMERA INSPECCIÓN SIN VEHÍCULO'; }

    $dia_abrev = obtener_dia_semana_abrev($cita['fecha_cita']);
    $dia_class = 'dia-' . $dia_abrev; // clase para el color
?>
<tr class="<?= $clase_fila ?>">
    <td class="dia-semana <?= $dia_class ?>"><?= $dia_abrev ?></td>
    <td>
        <?php 
            $vehiculo_completo = mostrar_vehiculo_completo($vehiculo,$vehiculos);
            if($vehiculo_completo!=='-'){
                $partes = explode(' - ',$vehiculo_completo,2);
                echo htmlspecialchars($partes[0]);
                if(isset($partes[1])) echo ' - <span class="matricula">'.htmlspecialchars($partes[1]).'</span>';
            } else echo '-';
        ?>
    </td>
    <td><?= htmlspecialchars($cita['tipo_cita'] ?? '-') ?></td>
    <td><?= formatear_fecha($cita['fecha_cita']) ?></td>
    <td><?= htmlspecialchars($cita['hora_cita']) ?></td>
    <td><?= htmlspecialchars($cita['estacion_cita'] ?? '-') ?></td>
    <td><?= obtener_caducidad_itv($vehiculo,$vehiculos) ?></td>
    <td class="estado"><?= $estado ?></td>
</tr>
<?php endforeach; ?>
<?php endif; ?>

</tbody>
</table>

<div class="print-footer">
    <p><strong>Aviso importante:</strong><br>
    Le informamos que, en caso de retraso por parte del usuario, superados los <strong>15 minutos de margen</strong> sobre la hora concertada, esta será anulada a favor de otros usuarios del servicio. Por motivos organizativos, el servicio de inspección empezará en el intervalo de los quince minutos siguientes a la hora concertada.
    </p>
</div>

<h4 class="small no-imprimir" style="margin-top:12px;"><?= htmlspecialchars($version) ?></h4>
<p class="small no-imprimir"><?= htmlspecialchars($autor) ?></p>
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
