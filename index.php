<?php
session_start();

// Redirigir al login si no hay usuario logueado
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit();
}

$is_admin = ($_SESSION['tipo'] == 'Administrador');

// Cargar vehículos
$vehiculos_file = 'vehiculos.json';
if (!file_exists($vehiculos_file)) {
    die("El archivo de vehículos no existe.");
}
$vehiculos = json_decode(file_get_contents($vehiculos_file), true);

// Cargar citas
$citas_file = 'citas.json';
if (!file_exists($citas_file)) {
    die("El archivo de citas no existe.");
}
$citas = json_decode(file_get_contents($citas_file), true);

// Función para calcular días restantes
function calcular_dias_restantes($caducidad_itv) {
    $fecha_actual = new DateTime();
    $fecha_caducidad = new DateTime($caducidad_itv);
    $intervalo = $fecha_actual->diff($fecha_caducidad);
    return (int)$intervalo->format('%r%a');
}

// Función para obtener citas asignadas a un vehículo (solo futuras)
function obtener_citas_vehiculo($matricula_vehiculo, $citas) {
    $fecha_actual = new DateTime();
    $citas_vehiculo = [];
    foreach ($citas as $cita) {
        if ($cita['vehiculo'] === $matricula_vehiculo) {
            $fecha_hora_cita = DateTime::createFromFormat('Y-m-d H:i', $cita['fecha_cita'] . ' ' . $cita['hora_cita']);
            if ($fecha_hora_cita && $fecha_hora_cita >= $fecha_actual) {
                $citas_vehiculo[] = $cita;
            }
        }
    }
    return $citas_vehiculo;
}

// Formatear fecha DD/MM/YYYY
function formatear_fecha($fecha) {
    $fecha_obj = DateTime::createFromFormat('Y-m-d', $fecha);
    return $fecha_obj ? $fecha_obj->format('d/m/Y') : $fecha;
}

// Determinar color y texto de días restantes
function obtener_color_y_texto($vehiculo) {
    $estado = $vehiculo['estado'];
    $dias_restantes = calcular_dias_restantes($vehiculo['caducidad_itv']);
    $texto_dias = $dias_restantes . ' días';
    $color = 'verde'; // por defecto

    if ($estado == 'BAJA') {
        $color = 'negro';
        $texto_dias = '-';
    } elseif ($estado == 'ITV RECHAZADA') {
        $color = 'rojo_intenso';
        $texto_dias = 'ITV RECHAZADA';
    } elseif ($dias_restantes <= 0) {
        $color = 'rojo_intenso';
        $texto_dias = 'ITV CADUCADA';
    } elseif ($dias_restantes < 10) {
        $color = 'naranja_intenso';
    } elseif ($dias_restantes <= 20) {
        $color = 'naranja_suave';
    } elseif ($dias_restantes <= 35) {
        $color = 'azul';
    }

    return ['color' => $color, 'texto_dias' => $texto_dias];
}

// Filtrar solo vehículos activos o con ITV rechazada (BAJA se oculta)
$vehiculos_filtrados = array_filter($vehiculos, function ($vehiculo) {
    return in_array($vehiculo['estado'], ['ACTIVO', 'ITV RECHAZADA']);
});

// Ordenar: primero los "ITV RECHAZADA", luego por días restantes
usort($vehiculos_filtrados, function ($a, $b) {
    // Si uno es "ITV RECHAZADA" y el otro no, va primero
    if ($a['estado'] === 'ITV RECHAZADA' && $b['estado'] !== 'ITV RECHAZADA') {
        return -1;
    }
    if ($b['estado'] === 'ITV RECHAZADA' && $a['estado'] !== 'ITV RECHAZADA') {
        return 1;
    }

    // Si ambos tienen el mismo estado, ordenar por días restantes (menor a mayor)
    return calcular_dias_restantes($a['caducidad_itv']) - calcular_dias_restantes($b['caducidad_itv']);
});

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta http-equiv="refresh" content="60">
    <link rel="shortcut icon" href="images/logo.webp">
    <link rel="icon" sizes="64x64" href="images/logo.webp">
    <link rel="apple-touch-icon" sices="180x180" href="images/logo.webp">
    <meta charset="UTF-8">
    <title>Página Principal - Gestión de ITV</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .negro { background-color: black; color: grey; }
        .rojo_intenso { background-color: #cc0000; color: white; }
        .naranja_intenso { background-color: #ff6600; color: white; }
        .naranja_suave { background-color: #ffae0d; color: white; }
        .azul { background-color: #3399ff; color: white; }
        .verde { background-color: #4CAF50; color: white; }

        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; vertical-align: top; }
        th { background-color: #eee; }
        ul { margin:0; padding-left:18px; }
    </style>
</head>
<body>
    <h1><img src="images/logo.webp" alt="Logo" width="30" style="vertical-align: middle;">Página Principal - Gestión de ITV</h1>

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

    <h2>Vehículos</h2>
    <table>
        <thead>
            <tr>
                <th>Vehículo</th>
                <th>Matrícula</th>
                <th>Tipo</th>
                <th>Estado</th>
                <th>Caducidad ITV</th>
                <th>Días para Caducar ITV</th>
                <th>Cita Asignada</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($vehiculos_filtrados as $vehiculo):
                $info_color = obtener_color_y_texto($vehiculo);
                $citas_vehiculo = obtener_citas_vehiculo($vehiculo['matricula'], $citas);

                // Ajustar estado mostrado
                $estado_mostrar = $vehiculo['estado'];
                $dias_restantes = calcular_dias_restantes($vehiculo['caducidad_itv']);
                if ($vehiculo['estado'] == 'ITV RECHAZADA') {
                    $estado_mostrar = 'ITV RECHAZADA';
                } elseif ($dias_restantes <= 0) {
                    $estado_mostrar = 'ITV CADUCADA';
                }
            ?>
                <tr class="<?= $info_color['color'] ?>">
                    <td><?= htmlspecialchars($vehiculo['vehiculo']) ?></td>
                    <td><?= htmlspecialchars($vehiculo['matricula']) ?></td>
                    <td><?= isset($vehiculo['tipo']) ? htmlspecialchars($vehiculo['tipo']) : '-' ?></td>
                    <td><?= $estado_mostrar ?></td>
                    <td><?= formatear_fecha($vehiculo['caducidad_itv']) ?></td>
                    <td><?= $info_color['texto_dias'] ?></td>
                    <td>
                        <?php if (!empty($citas_vehiculo)): ?>
                            <ul>
                            <?php foreach ($citas_vehiculo as $cita): ?>
                                <li>
                                    <strong>Fecha:</strong> <?= formatear_fecha($cita['fecha_cita']) ?>, 
                                    <strong>Hora:</strong> <?= htmlspecialchars($cita['hora_cita']) ?>, 
                                    <strong>Estación:</strong> <?= htmlspecialchars($cita['estacion_cita']) . ' ' . ($cita['tipo_cita']==='Primera'?'1ª':'2ª') ?>
                                </li>
                            <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            Sin cita asignada
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h4 class="small" style="margin-top:12px;">ITVControl v.1.2</h4>
    <p class="small">B174M3 // XaeK</p>
</body>

</html>
