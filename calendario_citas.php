<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit();
}

$is_colab = isset($_SESSION['tipo']) && in_array($_SESSION['tipo'], ['Colaborador', 'Administrador', 'SuperAdministrador']);
$is_admin = isset($_SESSION['tipo']) && in_array($_SESSION['tipo'], ['Administrador', 'SuperAdministrador']);
$is_superadmin = isset($_SESSION['tipo']) && $_SESSION['tipo'] === 'SuperAdministrador';

$flota_usuario = $_SESSION['flota'] ?? null;

// =====================
// CARGAR CITAS Y VEHÍCULOS
// =====================
$citas_file = 'citas.json';
if (!file_exists($citas_file)) file_put_contents($citas_file, json_encode([]));
$citas = json_decode(file_get_contents($citas_file), true);

$vehiculos_file = 'vehiculos.json';
if (!file_exists($vehiculos_file)) die("El archivo de vehículos no existe.");
$vehiculos = json_decode(file_get_contents($vehiculos_file), true);
usort($vehiculos, fn($a,$b) => strnatcasecmp($a['vehiculo'], $b['vehiculo']));

// =====================
// CARGAR ESTACIONES
// =====================
$estaciones_file = 'estaciones.json';
if (!file_exists($estaciones_file)) {
    file_put_contents($estaciones_file, json_encode(['Tambre','Sionlla','Cacheiras'], JSON_PRETTY_PRINT));
}
$estaciones = json_decode(file_get_contents($estaciones_file), true);

// =====================
// CARGAR FLOTAS EXISTENTES (para SuperAdmin)
// =====================
$usuarios_file = 'usuarios.json';
$flotas_existentes = [];
if ($is_superadmin && file_exists($usuarios_file)) {
    $usuarios = json_decode(file_get_contents($usuarios_file), true);
    foreach ($usuarios as $u) {
        if (!empty($u['flota']) && !in_array($u['flota'],$flotas_existentes)) {
            $flotas_existentes[] = $u['flota'];
        }
    }
}

// =====================
// FUNCIONES
// =====================
function formatear_fecha($fecha) {
    $f = DateTime::createFromFormat('Y-m-d', $fecha);
    return $f ? $f->format('d/m/Y') : $fecha;
}
function mostrarVehiculo($matricula, $vehiculos) {
    if ($matricula === '') return 'Sin asignar';
    foreach ($vehiculos as $v) {
        if ($v['matricula'] === $matricula) return $v['vehiculo'].' - '.$v['matricula'];
    }
    return $matricula;
}

// =====================
// VERSION Y AUTOR
// =====================
$version = 'v.1.0';
$autor = 'Desconocido';
if (file_exists('version.xk')) {
    $lineas = file('version.xk', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (isset($lineas[0])) $version = trim($lineas[0]);
    if (isset($lineas[1])) $autor = trim($lineas[1]);
}

// =====================
// FILTRAR CITAS FUTURAS POR FLOTA
// =====================
$ahora = new DateTime();
$citas_futuras = array_filter($citas, function($cita) use($ahora, $is_superadmin, $flota_usuario) {
    $dt = DateTime::createFromFormat('Y-m-d H:i', ($cita['fecha_cita'] ?? '') . ' ' . ($cita['hora_cita'] ?? '00:00'));
    if (!$dt || $dt < $ahora) return false;
    if (!$is_superadmin) {
        return strtoupper($cita['flota'] ?? '') === strtoupper($flota_usuario ?? '');
    }
    return true; // SuperAdmin ve todas
});

usort($citas_futuras, fn($a,$b) => strtotime($a['fecha_cita'].' '.$a['hora_cita']) <=> strtotime($b['fecha_cita'].' '.$b['hora_cita']));

// =====================
// AGRUPAR CITAS POR MES Y DÍA
// =====================
$citas_por_mes = [];
foreach ($citas_futuras as $c) {
    $dt = DateTime::createFromFormat('Y-m-d', $c['fecha_cita']);
    if ($dt) {
        $mes = $dt->format('Y-m');
        $dia = (int)$dt->format('j');
        if (!isset($citas_por_mes[$mes])) $citas_por_mes[$mes] = [];
        if (!isset($citas_por_mes[$mes][$dia])) $citas_por_mes[$mes][$dia] = [];
        $citas_por_mes[$mes][$dia][] = $c;
    }
}
ksort($citas_por_mes);
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

<meta http-equiv="refresh" content="60">

<style>
/* ===== BASE ===== */
body { margin:15px; font-family:Arial,sans-serif; }

/* ===== USUARIO ===== */
.user-info{
    position:fixed; top:10px; right:15px; text-align:right; font-size:14px;
    background:rgba(255,255,255,0.6); padding:5px 10px; border-radius:8px;
}
.user-info strong{display:block;}
.user-info small{color:#4a90e2;font-weight:bold;}

/* ===== MODO OSCURO ===== */
@media (prefers-color-scheme: dark) {
    body{background:#000;color:#ddd;}
    h1,h2,h3,h4,p,strong{color:#ddd;}
    th{background:#1c75bc;color:#fff;}
    td{color:#ddd;border-color:#555;}
    tr:nth-child(even) td { background:#111827; }
    tr:hover td { background:#1e293b; }

    input, select { background:#111;color:#fff;border:1px solid #555; }
    input[type="submit"] { background: linear-gradient(135deg,#1c75bc,#0066cc); border:1px solid #005bb5; color:#fff; }
    input[type="submit"]:hover { background: linear-gradient(135deg,#005bb5,#1c75bc); }

    .menu a img:not([alt="Logo"]){filter: invert(1) hue-rotate(180deg);}
    h1 img{filter:none;}
    .user-info{background:rgba(0,0,0,0.5);}
    .user-info small{color:#3399ff;}
}

/* ===== CALENDARIO ===== */
.calendario-mes {
    margin-bottom: 50px;
}
.calendario-mes h2 {
    text-align:center;
    margin:20px 0 10px 0;
    color:#000; /* modo claro */
}
table.calendario {
    width:100%;
    border-collapse:collapse;
    margin-bottom:20px;
}
.calendario th, .calendario td {
    border:1px solid #ccc;
    width:14.28%;
    vertical-align:top;
    text-align:left;
    padding:6px;
}
.calendario th {
    background:#333;
    color:#fff;
    text-align:center;
    font-weight:700;
    height:24px; /* altura ajustada a texto */
}
.calendario td.vacio {
    background:#f0f0f0;
}
.calendario .dia {
    font-weight:bold;
    font-size:16px;
    margin-bottom:6px;
}
.cita {
    background:#4a90e2;
    color:#fff;
    font-size:16px;
    border-radius:4px;
    padding:4px 6px;
    margin-bottom:6px;
    display:block;
    word-wrap:break-word;
}


/* ===== COLOR SABADOS/DOMINGOS ===== */
.calendario td:nth-child(6) {
    background-color: #fff59d; /* sábado amarillo */
}

.calendario td:nth-child(7) {
    background-color: #ff8a80; /* domingo rojo */
}

/* MODO OSCURO */
@media (prefers-color-scheme: dark) {
    .calendario td:nth-child(6) {
        background-color: #665c00; /* amarillo oscuro */
    }
    .calendario td:nth-child(7) {
        background-color: #7f1d1d; /* rojo oscuro */
    }
}

/* ===== CABECERA FINES DE SEMANA (AMARILLO/ROJO SUAVES) ===== */
.calendario th:nth-child(6) {
    background-color: #ffd54f; /* sábado amarillo suave */
    color:#000;
}

.calendario th:nth-child(7) {
    background-color: #ef5350; /* domingo rojo suave */
    color:#fff;
}

/* MODO OSCURO */
@media (prefers-color-scheme: dark) {
    .calendario th:nth-child(6) {
        background-color: #bfa134; /* amarillo más apagado */
        color:#000;
    }
    .calendario th:nth-child(7) {
        background-color: #b71c1c; /* rojo más profundo */
        color:#fff;
    }
}

/* ===== MODO OSCURO (ajustes pedidos) ===== */
@media (prefers-color-scheme: dark) {
    .calendario-mes h2 { color:#fff; } /* <-- ahora blanco en modo oscuro */
    .calendario th { background:#1c75bc; color:#fff; height:24px; } /* altura más baja */
    .calendario td { border-color:#555; color:#ddd; }
    .calendario td.vacio { background:#111827; }
    .cita { background:#1c75bc; color:#fff; }
}
/* ===== TIPOS DE CITA (SOLUCIÓN DEFINITIVA) ===== */
.cita.cita-segunda {
    background:#4a90e2;
}

.cita.cita-primera {
    background:#00bcd4;
}

/* MODO OSCURO (pisar el .cita general) */
@media (prefers-color-scheme: dark) {
    .cita.cita-segunda {
        background:#4f6980;
    }
    .cita.cita-primera {
        background:#1c75bc;
    }
}
</style>
</head>
<body>

<div class="user-info">
    <strong><?=htmlspecialchars($_SESSION['usuario'])?> | <?=htmlspecialchars($_SESSION['tipo'])?></strong>
    <small><?= $is_superadmin ? "Todas las flotas" : ($flota_usuario ? strtoupper($flota_usuario) : "Sin flota asignada") ?></small>
    <div id="fecha-hora"></div>
</div>

<h1><img src="images/logo.webp" width="30" style="vertical-align: middle;"> Gestionar citas de ITV</h1>
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

<h2 style="display:inline-flex; align-items:center; gap:8px; margin:0;">
  Calendario de Citas Futuras
  <button class="boton-vista" onclick="window.location.href='citas.php'">Cambiar a modo lista</button>
</h2>

<style>
.boton-vista {
  background:#4a90e2;
  color:#fff;
  border:none;
  padding:4px 10px;
  border-radius:6px;
  cursor:pointer;
  font-size:13px;
  transition:background 0.2s ease-in-out;
}
.boton-vista:hover {
  background:#1c75bc;
}
@media (prefers-color-scheme: dark) {
  .boton-vista {
    background: linear-gradient(135deg,#1c75bc,#0066cc);
    color:#fff;
    border:1px solid #005bb5;
  }
  .boton-vista:hover {
    background: linear-gradient(135deg,#005bb5,#1c75bc);
  }
}
</style>

<?php if (empty($citas_por_mes)): ?>
    <p>No hay citas futuras.</p>
<?php else: ?>

<?php
$meses_nombres = [
    '01'=>'ENERO','02'=>'FEBRERO','03'=>'MARZO','04'=>'ABRIL','05'=>'MAYO','06'=>'JUNIO',
    '07'=>'JULIO','08'=>'AGOSTO','09'=>'SEPTIEMBRE','10'=>'OCTUBRE','11'=>'NOVIEMBRE','12'=>'DICIEMBRE'
];

foreach ($citas_por_mes as $mes => $dias):
    $anio = substr($mes, 0, 4);
    $num_mes = substr($mes, 5, 2);
    ksort($dias);
    $primer_dia = new DateTime("$anio-$num_mes-01");
    $inicio_semana = (int)$primer_dia->format('N');
    $dias_mes = (int)$primer_dia->format('t');
?>
<div class="calendario-mes">
    <h2><?= $meses_nombres[$num_mes] . " " . $anio ?></h2>
    <table class="calendario">
        <thead>
            <tr>
                <th>Lunes</th><th>Martes</th><th>Miércoles</th><th>Jueves</th>
                <th>Viernes</th><th>Sábado</th><th>Domingo</th>
            </tr>
        </thead>
        <tbody>
            <tr>
<?php
$col = 1;

// Calcular desde qué día empezar (primer lunes válido o hoy)
$hoy = new DateTime();
$primer_dia_visible = 1;

if ($anio == $hoy->format('Y') && $num_mes == $hoy->format('m')) {
    $inicio_semana_hoy = clone $hoy;
    $inicio_semana_hoy->modify('monday this week');
    $primer_dia_visible = (int)$inicio_semana_hoy->format('j');
}

// Ajustar inicio real del calendario
$primer_dia = new DateTime("$anio-$num_mes-$primer_dia_visible");
$inicio_semana = (int)$primer_dia->format('N');

// Celdas vacías antes del inicio
for ($v = 1; $v < $inicio_semana; $v++, $col++) {
    echo "<td class='vacio'></td>";
}

// Bucle de días (EMPIEZA en el día válido)
for ($d = $primer_dia_visible; $d <= $dias_mes; $d++, $col++) {
    $citas_dia = $dias[$d] ?? [];
    echo "<td>";
    echo "<div class='dia'>$d</div>";
    foreach ($citas_dia as $c) {
        $veh = htmlspecialchars(mostrarVehiculo($c['vehiculo'] ?? '', $vehiculos));
        $hora = htmlspecialchars($c['hora_cita'] ?? '');
        $tipo = htmlspecialchars($c['tipo_cita'] ?? '');
        $est = htmlspecialchars($c['estacion_cita'] ?? '');
        $tipo_formateado = ($tipo === 'Segunda') ? '2ª' : '1ª';
        $clase_tipo = ($tipo === 'Segunda') ? 'cita-segunda' : 'cita-primera';

        echo "<div class='cita {$clase_tipo}'>{$hora} {$est} {$tipo_formateado}<br>{$veh}</div>";
    }
    echo "</td>";
    if ($col % 7 == 0 && $d < $dias_mes) echo "</tr><tr>";
}

// Relleno final
while ($col % 7 != 1) { echo "<td class='vacio'></td>"; $col++; }
?>
            </tr>
        </tbody>
    </table>
</div>
<?php endforeach; ?>

<?php endif; ?>

<h4 class="small version-title" style="margin-top:12px; text-align:left;"><?= htmlspecialchars($version) ?></h4>
<p class="small version-author" style="text-align:left;"><?= htmlspecialchars($autor) ?></p>

</body>
</html>
