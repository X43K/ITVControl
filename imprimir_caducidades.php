<?php
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario'])) {
    header('Location: index.php');
    exit();
}

// Verificar si es administrador
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
// SELECCIÓN DE MES / AÑO
// =====================
$mes_actual = date('m');
$anio_actual = date('Y');

$mes_seleccionado = $_GET['mes'] ?? $mes_actual;
$anio_seleccionado = $_GET['anio'] ?? $anio_actual;

// =====================
// MES EN ESPAÑOL
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
// FILTRAR VEHÍCULOS QUE CADUCAN EN MES/AÑO SELECCIONADO
// =====================
$vehiculos_filtrados = array_filter($vehiculos, function($v) use ($mes_seleccionado, $anio_seleccionado) {
    $fecha = DateTime::createFromFormat('Y-m-d', $v['caducidad_itv']);
    return $fecha && $fecha->format('m') == str_pad($mes_seleccionado,2,'0',STR_PAD_LEFT)
                 && $fecha->format('Y') == $anio_seleccionado;
});

// =====================
// ORDENAR POR FECHA DE CADUCIDAD DE MENOR A MAYOR
// =====================
usort($vehiculos_filtrados, function($a, $b) {
    $fechaA = strtotime($a['caducidad_itv']);
    $fechaB = strtotime($b['caducidad_itv']);
    return $fechaA <=> $fechaB; 
});

// =====================
// CÁLCULO HORIZONTE SEGURO DE IMPRESIÓN
// =====================
$frecuencia_meses = 24;
$tipos_detectados = [];

foreach ($vehiculos as $v) {
    $tipo = strtolower($v['tipo'] ?? '');

    if (
        str_contains($tipo, 'autobus') ||
        str_contains($tipo, 'microbus') ||
        str_contains($tipo, 'mercador') ||
        str_contains($tipo, 'tractora') ||
        str_contains($tipo, 'remolque')
    ) {
        $frecuencia_meses = min($frecuencia_meses, 6);
        $tipos_detectados[] = 'Semestral';
    } elseif (
        str_contains($tipo, 'turismo') ||
        str_contains($tipo, 'taxi') ||
        str_contains($tipo, 'agricola') ||
        str_contains($tipo, 'obras')
    ) {
        $frecuencia_meses = min($frecuencia_meses, 12);
        $tipos_detectados[] = 'Anual';
    } elseif (
        str_contains($tipo, 'moto') ||
        str_contains($tipo, 'quad') ||
        str_contains($tipo, 'ciclomotor')
    ) {
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
// CARGA DE VERSIÓN Y AUTOR
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
<title>Hoja de Caducidad ITV</title>
<link rel="shortcut icon" href="images/logo.webp">
<link rel="stylesheet" href="style.css">
<style>
body { font-family: Arial, sans-serif; }
table { border-collapse: collapse; width: 100%; margin-top: 10px; }
th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
th { background-color: #eee; }
h1 img { vertical-align: middle; }

.aviso-horizonte { background: #fff3cd; border: 1px solid #ffeeba; padding: 10px; margin-bottom: 10px; font-size: 14px; }
.formulario-filtro { margin-bottom: 15px; }

/* Impresión */
@media print {
    .aviso-horizonte, .formulario-filtro, .menu, .no-imprimir { 
        display: none !important; 
    }
    .solo-impresion { display: block !important; }
}

.solo-impresion { display: none; }
.no-imprimir { display: block; }

/* ===== MODO OSCURO AUTOMÁTICO ===== */
@media (prefers-color-scheme: dark) {
    body { background:#000; color:#ddd; }
    h1,h2,h3,h4,p,strong { color:#ddd; }
    th { background:#222; color:#fff; }
    .menu img { filter: invert(1) hue-rotate(180deg); }
    h1 img { filter:none; } /* logo.webp no se invierte */

    /* Aviso de impresión seguro en modo oscuro */
    .aviso-horizonte {
        background: #333; /* fondo oscuro */
        border-color: #666; /* borde visible */
        color: #ffd700;    /* texto amarillo brillante */
    }

    /* Botones en modo oscuro */
    button {
        background-color: #555;
        color: #fff;
        border: 1px solid #888;
        padding: 4px 8px;
        cursor: pointer;
    }
    button:hover {
        background-color: #777;
    }
}
</style>
</head>
<body>

<div class="user-info no-imprimir" style="position:fixed;top:10px;right:15px;text-align:right;font-size:14px;">
    <strong><?= $_SESSION['usuario'] ?> | <?= $_SESSION['tipo'] ?></strong>
    <div id="fecha-hora"></div>
</div>


</br></br>

<h1>
    <img src="images/logo.webp" alt="Logo" width="30"> Hoja de Caducidad ITV - <?= $meses_es[(int)$mes_seleccionado] ?> <?= $anio_seleccionado ?>
</h1>

</br>

<div class="menu">
    <a href="index.php"><img src="images/index.webp" width="80"></a>
    <a href="citas.php"><img src="images/citas.webp" width="80"></a>
    <a href="vehiculos.php"><img src="images/vehiculos.webp" width="80"></a>
    <?php if ($is_admin): ?><a href="estaciones.php"><img src="images/estaciones.webp" width="80"></a><?php endif; ?>
    <?php if ($is_superadmin): ?><a href="usuarios.php"><img src="images/usuarios.webp" width="80"></a><?php endif; ?>
    <a href="imprimir.php"><img src="images/imprimir.webp" width="80"></a>
    <a href="logout.php"><img src="images/logout.webp" width="80"></a>
</div>

</br>

<p>Fecha de impresión: <?= $fecha_impresion ?></p>

<div class="formulario-filtro">
    <form method="GET">
        <label>Mes: 
            <select name="mes">
                <?php for($m=1;$m<=12;$m++): ?>
                    <option value="<?= $m ?>" <?= $m==$mes_seleccionado?'selected':'' ?>><?= $meses_es[$m] ?></option>
                <?php endfor; ?>
            </select>
        </label>
        <label>Año:
            <input type="number" name="anio" value="<?= $anio_seleccionado ?>" min="2000" max="2100">
        </label>
        <button type="submit">Mostrar</button>
        <button type="button" onclick="window.print()">Imprimir</button>
    </form>
</div>

<?php if (!empty($tipos_detectados)): ?>
<div class="aviso-horizonte">
    ⚠️ Para que aparezcan <strong>todos los vehículos</strong>, es seguro imprimir
    <strong><?= $meses_es[$mes_maximo] ?> <?= $anio_maximo ?></strong> (frecuencia más restrictiva detectada).
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
<?php foreach($vehiculos_filtrados as $v): ?>
<tr>
    <td><?= htmlspecialchars($v['vehiculo']) ?></td>
    <td><?= htmlspecialchars($v['matricula']) ?></td>
    <td><?= htmlspecialchars($v['tipo'] ?? '') ?></td>
    <td><?= htmlspecialchars($v['estado']) ?></td>
    <td><?= formatear_fecha($v['caducidad_itv']) ?></td>
</tr>
<?php endforeach; ?>
<?php if(empty($vehiculos_filtrados)): ?>
<tr><td colspan="5">No hay vehículos que cadquen en el mes seleccionado.</td></tr>
<?php endif; ?>
</tbody>
</table>

<h4 class="small no-imprimir" style="margin-top:12px;"><?= htmlspecialchars($version) ?></h4>
<p class="small no-imprimir"><?= htmlspecialchars($autor) ?></p>

<div class="solo-impresion" style="margin-top:10px;"></div>
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
