<?php
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario'])) {
    header('Location: index.php');
    exit();
}

// Verificar si es administrador
$is_admin = ($_SESSION['tipo'] == 'Administrador');

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
$frecuencia_meses = 24; // por defecto bienal
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
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Hoja de impresión Caducidad ITV</title>
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
</style>
</head>
<body>

<h1>
    <img src="images/logo.webp" alt="Logo" width="30"> Hoja de impresión Caducidad ITV - <?= $meses_es[(int)$mes_seleccionado] ?> <?= $anio_seleccionado ?>
</h1>
<div class="menu">
    <a title="index" href="index.php"><img src="images/index.webp" alt="index" width="40" style="vertical-align: middle;"></a>
    <a title="citas" href="citas.php"><img src="images/citas.webp" alt="citas" width="40" style="vertical-align: middle;"></a>
    <a title="vehiculos" href="vehiculos.php"><img src="images/vehiculos.webp" alt="vehiculos" width="40" style="vertical-align: middle;"></a>
    <?php if ($is_admin): ?>
    <a title="estaciones" href="estaciones.php"><img src="images/estaciones.webp" alt="estaciones" width="40" style="vertical-align: middle;"></a>
    <a title="usuarios" href="usuarios.php"><img src="images/usuarios.webp" alt="usuarios" width="40" style="vertical-align: middle;"></a>
    <?php endif; ?>
    <a title="imprimir" href="imprimir.php"><img src="images/imprimir.webp" alt="imprimir" width="40" style="vertical-align: middle;"></a>
    <a title="logout" href="logout.php"><img src="images/logout.webp" alt="logout" width="40" style="vertical-align: middle;"></a>
</div>
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

<!-- Esto ya no se imprimirá -->
<h4 class="small no-imprimir" style="margin-top:12px;">ITVControl v.1.1</h4>
<p class="small no-imprimir">B174M3 // XaeK</p>

<!-- Solo visible en impresión -->
<div class="solo-impresion" style="margin-top:10px;">

</div>

</body>
</html>
