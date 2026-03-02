<?php
session_start();

// Redirigir al login si no hay usuario logueado
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit();
}

$is_admin = isset($_SESSION['tipo']) && in_array($_SESSION['tipo'], ['Administrador', 'SuperAdministrador']);
$is_superadmin = isset($_SESSION['tipo']) && $_SESSION['tipo'] === 'SuperAdministrador';

// Obtener flota del usuario
$flota_usuario = $_SESSION['flota'] ?? null;
$flota_texto = $is_superadmin ? "Todas las flotas" : ($flota_usuario ? strtoupper($flota_usuario) : "Sin flota asignada");

// Cargar vehículos
$vehiculos_file = 'vehiculos.json';
if (!file_exists($vehiculos_file)) die("El archivo de vehículos no existe.");
$vehiculos = json_decode(file_get_contents($vehiculos_file), true);

// Cargar citas
$citas_file = 'citas.json';
if (!file_exists($citas_file)) die("El archivo de citas no existe.");
$citas = json_decode(file_get_contents($citas_file), true);

// =====================
// FUNCIONES
// =====================

function calcular_dias_restantes($caducidad_itv) {
    $fecha_actual = new DateTime('today');
    $fecha_caducidad = new DateTime($caducidad_itv);
    $fecha_caducidad->setTime(0, 0, 0);
    $intervalo = $fecha_actual->diff($fecha_caducidad);
    return (int)$intervalo->format('%r%a');
}

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

function formatear_fecha($fecha) {
    $d = DateTime::createFromFormat('Y-m-d', $fecha);
    return $d ? $d->format('d/m/Y') : $fecha;
}

function obtener_color_y_texto($vehiculo) {
    $estado = $vehiculo['estado'];
    $dias = calcular_dias_restantes($vehiculo['caducidad_itv']);
    if ($estado === 'ITV RECHAZADA') return ['color'=>'rojo_intenso','texto_dias'=>'ITV RECHAZADA'];
    if ($dias < 0) return ['color'=>'rojo_intenso','texto_dias'=>'ITV CADUCADA'];
    if ($dias <= 1) return ['color'=>'rojo_intenso','texto_dias'=>$dias.' día'.($dias==1?'':'s')];
    if ($dias < 10) return ['color'=>'naranja_intenso','texto_dias'=>$dias.' días'];
    if ($dias <= 20) return ['color'=>'naranja_suave','texto_dias'=>$dias.' días'];
    if ($dias <= 35) return ['color'=>'azul','texto_dias'=>$dias.' días'];
    return ['color'=>'verde','texto_dias'=>$dias.' días'];
}

// =====================
// FILTRAR VEHÍCULOS POR FLOTA (si no es SuperAdministrador)
// =====================
if (!$is_superadmin && $flota_usuario) {
    $vehiculos = array_filter($vehiculos, function($v) use ($flota_usuario) {
        return isset($v['flota']) && strtoupper(trim($v['flota'])) === strtoupper(trim($flota_usuario));
    });
}

// =====================
// PRÓXIMA ITV GLOBAL
// =====================
$proxima_itv = null;
$ts_min = null;
$ahora = new DateTime();

foreach ($citas as $cita) {
    if ($cita['tipo_cita'] !== 'Primera') continue;

    // Filtrar por flota si no es superadmin
    if (!$is_superadmin && isset($cita['flota']) && strtoupper($cita['flota']) !== strtoupper($flota_usuario)) continue;

    $dt = DateTime::createFromFormat('Y-m-d H:i', $cita['fecha_cita'].' '.$cita['hora_cita']);
    if ($dt && $dt >= $ahora) {
        $ts = $dt->getTimestamp();
        if ($ts_min === null || $ts < $ts_min) {
            $ts_min = $ts;
            $proxima_itv = $cita;
        }
    }
}

// Filtrar y ordenar vehículos
$vehiculos_filtrados = array_filter($vehiculos, fn($v) => in_array($v['estado'], ['ACTIVO','ITV RECHAZADA']));
usort($vehiculos_filtrados, function($a, $b) {
    if ($a['estado'] === 'ITV RECHAZADA' && $b['estado'] !== 'ITV RECHAZADA') return -1;
    if ($a['estado'] !== 'ITV RECHAZADA' && $b['estado'] === 'ITV RECHAZADA') return 1;
    return calcular_dias_restantes($a['caducidad_itv']) <=> calcular_dias_restantes($b['caducidad_itv']);
});

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
// COMPROBAR ACTUALIZACIÓN DISPONIBLE DESDE GITHUB
// =====================
$github_version_url = 'https://raw.githubusercontent.com/X43K/ITVControl/main/version.xk';
$ultima_version = '';
$ctx = stream_context_create([
    'http' => ['timeout' => 4, 'header' => "User-Agent: ITVControl\r\n"]
]);
$contenido_remoto = @file_get_contents($github_version_url, false, $ctx);

if ($contenido_remoto !== false && strlen(trim($contenido_remoto)) > 0) {
    $lineas = explode("\n", trim($contenido_remoto));
    if (isset($lineas[0])) {
        // Extraer solo el número de versión (por ejemplo: "v.1.0" de "ITVControl v.1.0")
        if (preg_match('/v\.\d+(\.\d+)*/i', $lineas[0], $matches)) {
            $ultima_version = trim($matches[0]);
        }
    }
}


$mostrar_aviso = false;
$version_num = null;
$ultima_version_num = null;
$ultima_version_fallback = 'No se pudo comprobar actualización';

// Extraer número de versión local (solo dígitos y puntos)
if (preg_match('/\b(\d+(\.\d+)+)\b/', $version, $m)) {
    $version_num = $m[1]; // ej: 1.0 o 1.2.3
}

// Obtener versión remota desde GitHub forzando recarga (anti-cache)
$github_version_url = 'https://raw.githubusercontent.com/X43K/ITVControl/main/version.xk?t=' . time();
$ctx = stream_context_create([
    'http' => ['timeout' => 4, 'header' => "User-Agent: ITVControl\r\n"]
]);
$contenido_remoto = @file_get_contents($github_version_url, false, $ctx);
$ultima_version = '';

if ($contenido_remoto !== false && strlen(trim($contenido_remoto)) > 0) {
    $lineas = explode("\n", trim($contenido_remoto));
    if (isset($lineas[0]) && preg_match('/v\.\d+(\.\d+)*/i', $lineas[0], $matches)) {
        $ultima_version = trim($matches[0]);
    }
}

// Extraer número de versión remota (solo dígitos y puntos)
if ($ultima_version && preg_match('/\b(\d+(\.\d+)+)\b/', $ultima_version, $mr)) {
    $ultima_version_num = $mr[1]; // ej: 1.1 o 1.2.3
}


// Comparar versiones si se obtuvo la versión remota
if ($version_num && $ultima_version_num && version_compare($ultima_version_num, $version_num, '>')) {
    $mostrar_aviso = true;
} elseif (!$ultima_version_num) {
    // Mostrar aviso con fallback si no se pudo obtener versión remota
    $mostrar_aviso = true;
}

if ($mostrar_aviso):
?>
<!-- Aviso de nueva versión -->
<div style="background:#ffcc00;border:2px solid #cc9900;padding:10px 15px;margin-bottom:5px;border-radius:8px;">
    <div style="display:flex;align-items:center;gap:12px;">
        <span style="font-weight:bold;font-size:20px;color:red;">¡Existe una actualización disponible!</span>
        <?php if($is_admin || $is_superadmin): ?>
            <form action="actualizar.php" method="post" style="margin:0;">
                <input type="hidden" name="version_actual" value="<?= htmlspecialchars($version_num ?? $version) ?>">
                <input type="hidden" name="version_nueva" value="<?= htmlspecialchars($ultima_version_num ?? $ultima_version_fallback) ?>">
                <button type="submit" style="background:#4a90e2;color:white;border:none;padding:8px 16px;border-radius:6px;font-weight:bold;cursor:pointer;font-size:16px;">
                    Actualizar ahora
                </button>
            </form>
        <?php endif; ?>
    </div>

    <!-- Comparativa de versiones debajo -->
    <div style="font-weight:bold;font-size:12px;color:black;margin-top:4px;">
        Versión actual: <?= htmlspecialchars($version_num ?? $version) ?><br>
        Versión disponible: <?= htmlspecialchars($ultima_version_num ?? $ultima_version_fallback) ?>
    </div>
</div>
<?php endif; ?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>ITVGestion</title>
<link rel="icon" href="images/logo.webp">
<link rel="stylesheet" href="style.css">

<meta http-equiv="refresh" content="60">

<style>
/* ===== COLORES DE ESTADO ===== */
.negro{background:black;color:grey}
.rojo_intenso{background:#cc0000;color:white}
.naranja_intenso{background:#ff6600;color:white}
.naranja_suave{background:#ffae0d;color:white}
.azul{background:#3399ff;color:white}
.verde{background:#4CAF50;color:white}

/* ===== TABLAS ===== */
table{border-collapse:collapse;width:100%; table-layout:auto;}
th,td{border:1px solid #ccc;padding:8px;vertical-align:top}
th{background:#eee}

/* ===== ANCHOS DE COLUMNAS (tus originales) ===== */
th:nth-child(1), td:nth-child(1){white-space:normal;word-break:keep-all;width:1%;max-width:100%;}
th:nth-child(2), td:nth-child(2){white-space:nowrap;}
th:nth-child(3), td:nth-child(3){white-space:normal;word-break:keep-all;}
th:nth-child(4), td:nth-child(4){white-space:normal;word-break:keep-all;width:auto;min-width:80px;}
th:nth-child(5), td:nth-child(5){white-space:nowrap;}
th:nth-child(6), td:nth-child(6){white-space:normal;word-break:keep-all;width:auto;min-width:80px;}
th:nth-child(7), td:nth-child(7){white-space:nowrap;}
td.dias-numero{white-space:nowrap;}
ul{margin:0;padding-left:18px}

/* ===== INFO DE USUARIO ===== */
.user-info{
    position:fixed;
    top:10px;
    right:15px;
    text-align:right;
    font-size:14px;
    background:rgba(255,255,255,0.6);
    padding:5px 10px;
    border-radius:8px;
}
.user-info strong{display:block;}
.user-info small{color:#4a90e2;font-weight:bold;}

/* ===== MODO OSCURO ===== */
@media (prefers-color-scheme: dark){
    .user-info{background:rgba(0,0,0,0.5);}
    .user-info small{color:#3399ff;}
}

/* ===== PRÓXIMA ITV ===== */
.proxima-itv{
    position:fixed;
    top:100px;
    right:20px;
    width:260px;
    border:2px solid #000;
    background:#f8f8f8;
}
.proxima-itv .titulo{
    background:#4a90e2;
    color:#fff;
    text-align:center;
    font-weight:bold;
    font-size:18px;
    padding:8px;
    border-bottom:1px solid #000;
}
.proxima-itv .fila{display:flex;border-top:1px solid #000;}
.proxima-itv .label{width:40%;padding:6px;font-weight:bold;border-right:1px solid #000;}
.proxima-itv .valor{width:60%;padding:6px;}

/* ===== MODO OSCURO ===== */
@media (prefers-color-scheme: dark) {
    body{background:#000;color:#ddd;}
    h1,h2,h3,h4,p,strong{color:#ddd;}
    th{background:#1c75bc;color:#fff;}
    .proxima-itv{background:#111;border-color:#555;color:#ddd;}
    .proxima-itv .titulo{background:#1c75bc;}
    .menu img{filter: invert(1) hue-rotate(180deg);}
    h1 img{filter:none;}
    .user-info{background:rgba(0,0,0,0.5);}
    .user-info small{color:#3399ff;}
}

/* ===== BASE ===== */
body { margin:15px; font-family:Arial,sans-serif; }
</style>
</head>
<body>

<div class="user-info">
    <strong><?= htmlspecialchars($_SESSION['usuario']) ?> | <?= htmlspecialchars($_SESSION['tipo']) ?></strong>
    <small><?= htmlspecialchars($flota_texto) ?></small>
    <div id="fecha-hora"></div>
</div>

<h1><img src="images/logo.webp" width="30" style="vertical-align: middle;"> Página Principal - Gestión de ITV</h1>
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

<?php if($proxima_itv): ?>
<div class="proxima-itv">
    <div class="titulo">PRÓXIMA ITV</div>
    <div class="fila"><div class="label">FECHA</div><div class="valor"><?= formatear_fecha($proxima_itv['fecha_cita']) ?></div></div>
    <div class="fila"><div class="label">HORA</div><div class="valor"><?= $proxima_itv['hora_cita'] ?></div></div>
    <div class="fila"><div class="label">ESTACIÓN</div><div class="valor"><?= htmlspecialchars($proxima_itv['estacion_cita']) ?> <?= $proxima_itv['tipo_cita']==='Primera'?'1ª':'2ª' ?></div></div>
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
<td><?= htmlspecialchars($v['vehiculo']) ?></td>
<td><?= htmlspecialchars($v['matricula']) ?></td>
<td><?= htmlspecialchars($v['tipo'] ?? '-') ?></td>
<td><?= htmlspecialchars($v['estado']) ?></td>
<td><?= formatear_fecha($v['caducidad_itv']) ?></td>
<td><?= htmlspecialchars($info['texto_dias']) ?></td>
<td>
<?php if($citas_v): ?><ul>
<?php foreach($citas_v as $c): ?>
<li style="<?= ($proxima_itv &&
    $c['fecha_cita']===$proxima_itv['fecha_cita'] &&
    $c['hora_cita']===$proxima_itv['hora_cita'] &&
    $c['vehiculo']===$proxima_itv['vehiculo']) ? 'color:red;font-weight:bold;' : '' ?>">
<strong><?= formatear_fecha($c['fecha_cita']) ?></strong> <?= htmlspecialchars($c['hora_cita']) ?> – <?= htmlspecialchars($c['estacion_cita']) ?> <?= $c['tipo_cita']==='Primera'?'1ª':'2ª' ?>
</li>
<?php endforeach; ?></ul><?php else: ?>Sin cita<?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<h4 class="small version-title" style="margin-top:12px; text-align:left;"><?= htmlspecialchars($version) ?></h4>
<p class="small version-author" style="text-align:left;"><?= htmlspecialchars($autor) ?></p>

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
