<?php
session_start();

// Verificar login
if (!isset($_SESSION['usuario'])) {
    header('Location: index.php');
    exit();
}

$is_admin = ($_SESSION['tipo'] == 'Administrador');

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
// FILTRAR CITAS
// =====================
$citas_filtradas = array_filter($citas, function ($cita) use ($mes, $anio) {
    $fecha = DateTime::createFromFormat('Y-m-d', $cita['fecha_cita']);
    return $fecha && $fecha->format('m') == $mes && $fecha->format('Y') == $anio;
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
    padding: 3px 6px; /* filas más estrechas */
    line-height: 1.1;
    text-align: left;
}
th {
    background-color: #eee;
}

/* Decorativo (no crítico) */
.fila-roja {
    border-left: 6px solid #c00000;
}
.fila-azul {
    border-left: 6px solid #004aad;
}
.fila-amarilla {
    border-left: 6px solid #c9a600;
}

/* Texto oficial */
.estado {
    font-weight: bold;
    text-transform: uppercase;
    font-size: 12px;
    letter-spacing: 0.4px;
}

/* Matrícula más pequeña */
.matricula {
    font-size: 10px;
}

/* Impresión */
.print-header,
.print-footer {
    display: none;
}

@media print {

    @page {
        margin: 20mm;
    }

    .menu,
    form,
    button,
    h1,
    .small {
        display: none !important;
    }

    .print-header {
        display: block;
        margin-bottom: 15px;
        border-bottom: 2px solid #000;
        padding-bottom: 10px;
    }

    .print-footer {
        display: block;
        margin-top: 20px;
        font-size: 12px;
    }

    /* Ajustes para impresión */
    th, td {
        padding: 3px 4px;
        line-height: 1.1;
    }

    td .matricula {
        font-size: 10px;
    }
}
</style>
</head>

<body>

<h1>
    <img src="images/logo.webp" alt="Logo" width="30" style="vertical-align: middle;">
    Hoja de impresión ITV
</h1>

<div class="menu">
    <a href="index.php"><img src="images/index.webp" width="40"></a>
    <a href="citas.php"><img src="images/citas.webp" width="40"></a>
    <a href="vehiculos.php"><img src="images/vehiculos.webp" width="40"></a>
    <?php if ($is_admin): ?>
        <a href="estaciones.php"><img src="images/estaciones.webp" width="40"></a>
        <a href="usuarios.php"><img src="images/usuarios.webp" width="40"></a>
    <?php endif; ?>
    <a href="imprimir.php"><img src="images/imprimir.webp" width="40"></a>
    <a href="logout.php"><img src="images/logout.webp" width="40"></a>
</div>

<!-- CABECERA IMPRESIÓN -->
<div class="print-header">
    <div style="display:flex; align-items:center;">
        <img src="images/logo.webp" width="40" style="margin-right:10px;">
        <div>
            <h2 style="margin:0;">Hoja de Citas ITV</h2>
            <div>
                <?= $meses_txt[$mes] ?> <?= $anio ?> |
                Impreso el <?= $fecha_impresion ?>
            </div>
        </div>
    </div>
</div>

<!-- FILTRO -->
<form method="GET" style="margin:15px 0;">
    <label>Mes:</label>
    <select name="mes">
        <?php foreach ($meses_txt as $num => $nombre): ?>
            <option value="<?= $num ?>" <?= $mes == $num ? 'selected' : '' ?>>
                <?= $nombre ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label style="margin-left:10px;">Año:</label>
    <input type="number" name="anio" value="<?= $anio ?>" style="width:80px;">

    <button type="submit">Mostrar</button>
    <button type="button" onclick="window.print()">Imprimir</button>
</form>

<!-- TABLA -->
<table>
<thead>
<tr>
    <th>Vehículo</th>
    <th>Tipo</th>
    <th>Día cita</th>
    <th>Hora</th>
    <th>Estación</th>
    <th>Caducidad ITV</th>
    <th>Estado</th>
</tr>
</thead>
<tbody>

<?php if (empty($citas_filtradas)): ?>
<tr>
    <td colspan="7">No hay citas para este periodo</td>
</tr>
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
    $estado = 'PRIMERA INSPECCIÓN SIN VEHÍCULO ASIGNADO';
}
?>

<tr class="<?= $clase_fila ?>">
    <td>
        <?php
        $veh = mostrar_vehiculo_completo($vehiculo, $vehiculos);
        if (strpos($veh, ' - ') !== false) {
            [$nombre, $mat] = explode(' - ', $veh, 2);
            echo htmlspecialchars($nombre) . ' - <span class="matricula">' . htmlspecialchars($mat) . '</span>';
        } else {
            echo htmlspecialchars($veh);
        }
        ?>
    </td>
    <td><?= htmlspecialchars($cita['tipo_cita'] ?? '-') ?></td>
    <td><?= formatear_fecha($cita['fecha_cita']) ?></td>
    <td><?= htmlspecialchars($cita['hora_cita']) ?></td>
    <td><?= htmlspecialchars($cita['estacion'] ?? $cita['estacion_cita'] ?? '-') ?></td>
    <td><?= obtener_caducidad_itv($vehiculo, $vehiculos) ?></td>
    <td class="estado"><?= $estado ?></td>
</tr>

<?php endforeach; ?>
<?php endif; ?>

</tbody>
</table>

<!-- PIE IMPRESIÓN -->
<div class="print-footer">
    <p>
        <strong>Aviso importante:</strong><br>
        Le informamos que, en caso de retraso por parte del usuario, superados los <strong>15 minutos de margen</strong> sobre la hora concertada, esta será anulada a favor de otros usuarios del servicio. Por motivos organizativos, el servicio de inspección empezará en el intervalo de los quince minutos siguientes a la hora concertada.
    </p>
</div>

<h4 class="small" style="margin-top:12px;">ITVControl v1.1</h4>
<p class="small">B174M3 // XaeK</p>

</body>
</html>
