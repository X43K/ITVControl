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

    if (empty($cita['fecha_cita']) || empty($cita['hora_cita'])) {
        return false;
    }

    $fecha_hora_cita = DateTime::createFromFormat(
        'Y-m-d H:i',
        $cita['fecha_cita'] . ' ' . $cita['hora_cita']
    );

    if (!$fecha_hora_cita) {
        return false;
    }

    if ($fecha_hora_cita < $ahora_menos_una_hora) {
        return false;
    }

    return $fecha_hora_cita->format('m') == $mes
        && $fecha_hora_cita->format('Y') == $anio;
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

// NUEVA FUNCION: abreviatura del día
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
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Impresión de Citas ITV</title>

<style>
table {
    border-collapse: collapse;
    width: 100%;
}

th, td {
    border: 1px solid #ccc;
    padding: 2px 4px;
    line-height: 1.05;
    text-align: left;
    font-size: 15px;
}

th {
    background-color: #eee;
}

.fila-roja { border-left: 5px solid #c00000; }
.fila-azul { border-left: 5px solid #004aad; }
.fila-amarilla { border-left: 5px solid #c9a600; }

.fila-azul td,
.fila-azul td * {
    color: #666;
}

.estado {
    font-weight: bold;
    text-transform: uppercase;
    font-size: 10px;
}

.matricula {
    font-size: 9px;
}

.print-header,
.print-footer {
    display: none;
}

/* estilos día semana */
.dia-semana { font-weight: bold; }
.dia-rojo { color: #c00000 !important; }
.dia-negro { color: #000 !important; }

@media print {
    @page {
        size: A4 portrait;
        margin: 12mm;
    }

    body {
        font-size: 15px;
    }

    .menu,
    form,
    button,
    .small {
        display: none !important;
    }

    h1 {
        margin: 0 0 6px 0;
        font-size: 16px;
    }

    .print-header {
        display: block;
        margin-bottom: 6px;
        padding-bottom: 4px;
        border-bottom: 1px solid #000;
        font-size: 12px;
    }

    .print-footer {
        display: block;
        margin-top: 8px;
        font-size: 13px;
        line-height: 1.2;
    }

    tbody tr td {
        font-weight: bold;
    }

    .fila-azul td,
    .fila-azul td * {
        font-weight: normal;
    }

    table,
    tr,
    td,
    th {
        page-break-inside: avoid !important;
    }
}
</style>
</head>

<body>

<h1>
    <img src="images/logo.webp" width="28" style="vertical-align: middle;">
    Hoja de citas ITV
</h1>

<!-- MENU ORIGINAL -->
<div class="menu">
    <a href="index.php"><img src="images/index.webp" width="80"></a>
    <a href="citas.php"><img src="images/citas.webp" width="80"></a>
    <a href="vehiculos.php"><img src="images/vehiculos.webp" width="80"></a>
    <?php if ($is_admin): ?>
        <a href="estaciones.php"><img src="images/estaciones.webp" width="80"></a>
    <?php endif; ?>
    <?php if ($is_superadmin): ?>
        <a href="usuarios.php"><img src="images/usuarios.webp" width="80"></a>
    <?php endif; ?>
    <a href="imprimir.php"><img src="images/imprimir.webp" width="80"></a>
    <a href="logout.php"><img src="images/logout.webp" width="80"></a>
</div>

<!-- FORMULARIO ORIGINAL -->
<form method="GET" style="margin:15px 0;">
    <select name="mes">
        <?php foreach ($meses_txt as $num => $nombre): ?>
            <option value="<?= $num ?>" <?= $mes == $num ? 'selected' : '' ?>>
                <?= $nombre ?>
            </option>
        <?php endforeach; ?>
    </select>
    <input type="number" name="anio" value="<?= $anio ?>" style="width:80px;">
    <button type="submit">Mostrar</button>
    <button type="button" onclick="window.print()">Imprimir</button>
</form>

<!-- CABECERA DE IMPRESION ORIGINAL -->
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

<?php if (empty($citas_filtradas)): ?>
<tr><td colspan="8">No hay citas para este periodo</td></tr>
<?php else: ?>
<?php foreach ($citas_filtradas as $cita): ?>

<?php
$clase_fila = '';
$estado = 'NORMAL';

$tipo = strtolower($cita['tipo_cita'] ?? '');
$vehiculo = $cita['vehiculo'] ?? '';

$fecha_cita = DateTime::createFromFormat('Y-m-d', $cita['fecha_cita']);

$caducidad = null;
foreach ($vehiculos as $v) {
    if ($v['matricula'] === $vehiculo) {
        $caducidad = DateTime::createFromFormat('Y-m-d', $v['caducidad_itv']);
        break;
    }
}

if ($tipo === 'primera' && $fecha_cita && $caducidad && $fecha_cita > $caducidad) {
    $clase_fila = 'fila-roja';
    $estado = 'PRIMERA INSPECCIÓN FUERA DE PLAZO';
} elseif ($tipo === 'segunda') {
    $clase_fila = 'fila-azul';
    $estado = 'SEGUNDA INSPECCIÓN';
} elseif ($tipo === 'primera' && empty($vehiculo)) {
    $clase_fila = 'fila-amarilla';
    $estado = 'PRIMERA INSPECCIÓN SIN VEHÍCULO';
}

$dia_abrev = obtener_dia_semana_abrev($cita['fecha_cita']);
$es_rojo = $fecha_cita && in_array($fecha_cita->format('N'), [5,6,7]);
?>

<tr class="<?= $clase_fila ?>">
    <td class="dia-semana <?= $es_rojo ? 'dia-rojo' : 'dia-negro' ?>"><?= $dia_abrev ?></td>
<td>
    <?php 
        $vehiculo_completo = mostrar_vehiculo_completo($vehiculo, $vehiculos);
        if ($vehiculo_completo !== '-') {
            // separar vehículo y matrícula
            $partes = explode(' - ', $vehiculo_completo, 2);
            echo htmlspecialchars($partes[0]); // vehículo grande
            if (isset($partes[1])) {
                echo ' - <span class="matricula">' . htmlspecialchars($partes[1]) . '</span>';
            }
        } else {
            echo '-';
        }
    ?>
</td>

    <td><?= htmlspecialchars($cita['tipo_cita'] ?? '-') ?></td>
    <td><?= formatear_fecha($cita['fecha_cita']) ?></td>
    <td><?= htmlspecialchars($cita['hora_cita']) ?></td>
    <td><?= htmlspecialchars($cita['estacion_cita'] ?? '-') ?></td>
    <td><?= obtener_caducidad_itv($vehiculo, $vehiculos) ?></td>
    <td class="estado"><?= $estado ?></td>
</tr>

<?php endforeach; ?>
<?php endif; ?>

</tbody>
</table>

<!-- PIE DE PAGINA ORIGINAL -->
<div class="print-footer">
    <p>
        <strong>Aviso importante:</strong><br>
        Le informamos que, en caso de retraso por parte del usuario, superados los <strong>15 minutos de margen</strong> sobre la hora concertada, esta será anulada a favor de otros usuarios del servicio. Por motivos organizativos, el servicio de inspección empezará en el intervalo de los quince minutos siguientes a la hora concertada.
    </p>
</div>

<!-- Esto ya no se imprimirá -->
<h4 class="small no-imprimir" style="margin-top:12px;">ITVControl v.1.3</h4>
<p class="small no-imprimir">B174M3 // XaeK</p>
</body>
</html>
