<?php
session_start();

// Redirigir al login si no hay usuario logueado
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit();
}

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

// Función para obtener cita asignada
function obtener_cita_vehiculo($matricula_vehiculo, $citas) {
    foreach ($citas as $cita) {
        if ($cita['vehiculo'] === $matricula_vehiculo) {
            return $cita;
        }
    }
    return null;
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

// Filtrar solo vehículos activos o con ITV rechazada
$vehiculos_filtrados = array_filter($vehiculos, function ($vehiculo) {
    return in_array($vehiculo['estado'], ['ACTIVO', 'ITV RECHAZADA']);
});

// Ordenar por días restantes
usort($vehiculos_filtrados, function ($a, $b) {
    return calcular_dias_restantes($a['caducidad_itv']) - calcular_dias_restantes($b['caducidad_itv']);
});
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta http-equiv="refresh" content="10">
    <meta charset="UTF-8">
    <title>Página Principal - Gestión de ITV</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .negro { background-color: black; color: white; }
        .rojo_intenso { background-color: #cc0000; color: white; }
        .naranja_intenso { background-color: #ff6600; color: white; }
        .naranja_suave { background-color: #ffcc66; color: black; }
        .azul { background-color: #3399ff; color: white; }
        .verde { background-color: #4CAF50; color: white; }

        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background-color: #eee; }
    </style>
</head>
<body>
    <h1><img src="logo.webp" alt="Logo" width="25" style="vertical-align: middle;">Página Principal - Gestión de ITV</h1>

    <div class="menu">
        <a href="index.php">Página Principal</a>
        <a href="citas.php">Gestionar Citas</a>
        <a href="vehiculos.php">Gestionar Vehículos</a>
        <?php if (isset($_SESSION['tipo']) && $_SESSION['tipo'] === 'Administrador'): ?>
            <a href="estaciones.php">Gestionar Estaciones</a>
            <a href="usuarios.php">Gestionar Usuarios</a>
        <?php endif; ?>
        <a href="logout.php">Cerrar Sesión</a>
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
                $cita = obtener_cita_vehiculo($vehiculo['matricula'], $citas);

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
                        <?php if ($cita): ?>
                            <strong>Fecha:</strong> <?= formatear_fecha($cita['fecha_cita']) ?><br>
                            <strong>Hora:</strong> <?= htmlspecialchars($cita['hora_cita']) ?><br>
                            <strong>Estación:</strong> <?= htmlspecialchars($cita['estacion_cita']) ?>
                        <?php else: ?>
                            Sin cita asignada
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

</body>
</html>
