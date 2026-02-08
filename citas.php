<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit();
}

$is_colab = isset($_SESSION['tipo']) && in_array($_SESSION['tipo'], ['Colaborador', 'Administrador', 'SuperAdministrador']);
$is_admin = isset($_SESSION['tipo']) && in_array($_SESSION['tipo'], ['Administrador', 'SuperAdministrador']);
$is_superadmin = isset($_SESSION['tipo']) && $_SESSION['tipo'] === 'SuperAdministrador';

// =====================
// CARGAR VERSION
// =====================
$version_file = 'version.xk';
if (file_exists($version_file)) {
    $version_content = file_get_contents($version_file);
    preg_match('/<h4.*?>(.*?)<\/h4>/', $version_content, $match_v);
    preg_match('/<p.*?>(.*?)<\/p>/', $version_content, $match_a);
    $version_info = [
        'version' => $match_v[1] ?? 'ITVControl v.2.0',
        'author' => $match_a[1] ?? 'B174M3 // XaeK'
    ];
} else {
    $version_info = ['version'=>'ITVControl v.2.0','author'=>'B174M3 // XaeK'];
}

// =====================
// CARGAR CITAS
// =====================
$citas_file = 'citas.json';
if (!file_exists($citas_file)) file_put_contents($citas_file, json_encode([]));
$citas = json_decode(file_get_contents($citas_file), true);

// =====================
// CARGAR VEHÍCULOS
// =====================
$vehiculos_file = 'vehiculos.json';
if (!file_exists($vehiculos_file)) die("El archivo de vehículos no existe.");
$vehiculos = json_decode(file_get_contents($vehiculos_file), true);
usort($vehiculos, function ($a, $b) {
    return strnatcasecmp($a['vehiculo'], $b['vehiculo']);
});

// =====================
// CARGAR ESTACIONES
// =====================
$estaciones_file = 'estaciones.json';
if (!file_exists($estaciones_file)) {
    file_put_contents($estaciones_file, json_encode(['Tambre','Sionlla','Cacheiras'], JSON_PRETTY_PRINT));
}
$estaciones = json_decode(file_get_contents($estaciones_file), true);

// =====================
// PROCESAR FORMULARIO (ADMIN)
// =====================
if ($is_colab && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fecha_cita'])) {
    if (!empty($_POST['fecha_cita']) && !empty($_POST['hora_cita']) && !empty($_POST['estacion_cita'])) {

        $nueva_cita = [
            'fecha_cita' => $_POST['fecha_cita'],
            'hora_cita' => $_POST['hora_cita'],
            'estacion_cita' => $_POST['estacion_cita'],
            'tipo_cita' => $_POST['tipo_cita'],
            'vehiculo' => $_POST['vehiculo'] ?? ''
        ];

        if (!empty($nueva_cita['vehiculo'])) {
            $vehiculo_encontrado = false;
            foreach ($vehiculos as $vehiculo) {
                if ($vehiculo['matricula'] === $nueva_cita['vehiculo']) {
                    $vehiculo_encontrado = true;
                    break;
                }
            }
            if (!$vehiculo_encontrado) $error = "Vehículo no encontrado.";
        }

        if (empty($error)) {
            $citas[] = $nueva_cita;
            file_put_contents($citas_file, json_encode($citas, JSON_PRETTY_PRINT));
            header('Location: citas.php');
            exit();
        }
    } else {
        $error = "Todos los campos son obligatorios.";
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
        if ($v['matricula'] === $matricula) return $v['vehiculo'] . ' - ' . $v['matricula'];
    }
    return $matricula;
}

// =====================
// FILTRAR Y ORDENAR CITAS FUTURAS
// =====================
$ahora = new DateTime();
$citas = array_filter($citas, fn($cita) => 
    ($dt = DateTime::createFromFormat('Y-m-d H:i', $cita['fecha_cita'].' '.$cita['hora_cita'])) && $dt >= $ahora
);
usort($citas, fn($a,$b) => strtotime($a['fecha_cita'].' '.$a['hora_cita']) <=> strtotime($b['fecha_cita'].' '.$b['hora_cita']));
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Gestionar Citas</title>
<link rel="stylesheet" href="style.css">
<style>
/* ===== COLORES DÍAS ===== */
.dia-rojo { color: red; font-weight:bold; }
.dia-normal { color: inherit; font-weight:bold; }

/* ===== MODO OSCURO AUTOMÁTICO ===== */
@media (prefers-color-scheme: dark) {
    body { background:#000; color:#ddd; }
    h1, h2, h3, h4, p, strong { color:#ddd; }
    .menu a img:not([alt="Logo"]) { filter: invert(1); }
}
</style>
</head>
<body>

</br>

<h1><img src="images/logo.webp" alt="Logo" width="30" style="vertical-align: middle;"> Gestionar Citas de ITV</h1>

</br>

<div class="menu">
    <a title="index" href="index.php"><img src="images/index.webp" alt="index" width="80"></a>
    <a title="citas" href="citas.php"><img src="images/citas.webp" alt="citas" width="80"></a>
    <a title="vehiculos" href="vehiculos.php"><img src="images/vehiculos.webp" alt="vehiculos" width="80"></a>

    <?php if ($is_admin): ?>
        <a title="estaciones" href="estaciones.php"><img src="images/estaciones.webp" alt="estaciones" width="80"></a>
    <?php endif; ?>

    <?php if ($is_superadmin): ?>
        <a title="usuarios" href="usuarios.php"><img src="images/usuarios.webp" alt="usuarios" width="80"></a>
    <?php endif; ?>

    <a title="imprimir" href="imprimir.php"><img src="images/imprimir.webp" alt="imprimir" width="80"></a>
    <a title="logout" href="logout.php"><img src="images/logout.webp" alt="logout" width="80"></a>
</div>

<?php if (isset($error)): ?>
    <p style="color:red;"><?= $error ?></p>
<?php endif; ?>

<?php if ($is_colab): ?>

</br>

<h2>Añadir Cita</h2>

</br>

<form method="POST">
    <label>Fecha:</label>
    <input type="date" name="fecha_cita" required><br><br>

    <label>Hora:</label>
    <input type="time" name="hora_cita" required><br><br>

    <label>Estación:</label>
    <select name="estacion_cita" required>
        <?php foreach ($estaciones as $estacion): ?>
            <option value="<?= htmlspecialchars($estacion) ?>"><?= htmlspecialchars($estacion) ?></option>
        <?php endforeach; ?>
    </select><br><br>

    <label>Tipo:</label>
    <select name="tipo_cita">
        <option value="Primera">Primera</option>
        <option value="Segunda">Segunda</option>
    </select><br><br>

    <label>Vehículo:</label>
    <select name="vehiculo">
        <option value="">Sin asignar</option>
        <?php foreach ($vehiculos as $vehiculo): ?>
            <option value="<?= htmlspecialchars($vehiculo['matricula']) ?>">
                <?= htmlspecialchars($vehiculo['vehiculo']) ?> - <?= htmlspecialchars($vehiculo['matricula']) ?>
            </option>
        <?php endforeach; ?>
    </select><br><br>

    <input type="submit" value="Añadir Cita">
</form>
<?php endif; ?>

<h2>Lista de Citas Futuras</h2>
<table>
<thead>
<tr>
    <th>Fecha</th>
    <th>Hora</th>
    <th>Estación</th>
    <th>Tipo</th>
    <th>Vehículo</th>
    <?php if ($is_admin): ?><th>Acciones</th><?php endif; ?>
</tr>
</thead>
<tbody>
<?php if (!empty($citas)): ?>
    <?php foreach ($citas as $cita): ?>
    <?php
        $fecha_dt = DateTime::createFromFormat('Y-m-d', $cita['fecha_cita']);
        $dias_semana = ['do','lu','ma','mi','ju','vi','sa'];
        $dia_abrev = $fecha_dt ? $dias_semana[$fecha_dt->format('w')] : '';
        $clase_dia = in_array($dia_abrev, ['vi','sa','do']) ? 'dia-rojo' : 'dia-normal';
    ?>
    <tr>
        <td class="<?= $clase_dia ?>"><?= $dia_abrev ?> - <?= formatear_fecha($cita['fecha_cita']) ?></td>
        <td><?= htmlspecialchars($cita['hora_cita']) ?></td>
        <td><?= htmlspecialchars($cita['estacion_cita']) ?></td>
        <td><?= htmlspecialchars($cita['tipo_cita']) ?></td>
        <td><?= htmlspecialchars(mostrarVehiculo($cita['vehiculo'], $vehiculos)) ?></td>
        <?php if ($is_admin): ?>
        <td>
            <a href="editar_cita.php?fecha=<?= urlencode($cita['fecha_cita']) ?>&hora=<?= urlencode($cita['hora_cita']) ?>">Editar</a> |
            <a href="eliminar_cita.php?fecha=<?= urlencode($cita['fecha_cita']) ?>&hora=<?= urlencode($cita['hora_cita']) ?>">Eliminar</a>
        </td>
        <?php endif; ?>
    </tr>
    <?php endforeach; ?>
<?php else: ?>
    <tr><td colspan="<?= $is_admin ? 6 : 5 ?>">No hay citas futuras.</td></tr>
<?php endif; ?>
</tbody>
</table>

<h4 class="small version-title" style="text-align:left; margin:4px 0;"><?= htmlspecialchars($version_info['version']) ?></h4>
<p class="small version-author" style="text-align:left; margin:0;"><?= htmlspecialchars($version_info['author']) ?></p>

</body>
</html>
