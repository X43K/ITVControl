<?php
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario'])) {
    header('Location: index.php');
    exit();
}

// Verificar si el usuario es administrador
$is_admin = ($_SESSION['tipo'] == 'Administrador');

// Verificar si el archivo vehiculos.json existe y es accesible
$vehiculos_file = 'vehiculos.json';
if (!file_exists($vehiculos_file)) {
    file_put_contents($vehiculos_file, json_encode([]));
}

// Cargar vehículos desde el archivo JSON
$vehiculos = json_decode(file_get_contents($vehiculos_file), true);

// Procesar formulario de añadir vehículo si es administrador
if ($is_admin && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['vehiculo']) && !empty($_POST['matricula']) && !empty($_POST['estado']) && !empty($_POST['caducidad_itv']) && !empty($_POST['tipo'])) {
        $nuevo_vehiculo = [
            'vehiculo' => $_POST['vehiculo'],
            'matricula' => $_POST['matricula'],
            'tipo' => $_POST['tipo'],
            'estado' => $_POST['estado'],
            'caducidad_itv' => $_POST['caducidad_itv']
        ];

        $vehiculos[] = $nuevo_vehiculo;

        if (file_put_contents($vehiculos_file, json_encode($vehiculos, JSON_PRETTY_PRINT))) {
            header('Location: vehiculos.php');
            exit();
        } else {
            $error = "No se pudo guardar el vehículo. Verifique los permisos del archivo.";
        }
    } else {
        $error = "Todos los campos son obligatorios.";
    }
}

// Función para calcular los días restantes para la caducidad
function calcular_dias_restantes($caducidad_itv) {
    $fecha_actual = new DateTime();
    $fecha_caducidad = new DateTime($caducidad_itv);
    $intervalo = $fecha_actual->diff($fecha_caducidad);
    return (int)$intervalo->format('%r%a'); // puede ser negativo si ya caducó
}

// Función para obtener color y texto según estado y días restantes
function obtener_color_y_texto($vehiculo) {
    $estado = $vehiculo['estado'];
    $dias_restantes = calcular_dias_restantes($vehiculo['caducidad_itv']);
    $texto_dias = $dias_restantes . ' días';
    $color = 'verde'; // default

    if ($estado == 'BAJA') {
        $color = 'negro';
    } elseif ($estado == 'ITV RECHAZADA' || $dias_restantes <= 0) {
        $color = 'rojo_intenso';
        if ($dias_restantes <= 0) $texto_dias = "ITV CADUCADA";
    } elseif ($dias_restantes < 10) {
        $color = 'naranja_intenso';
    } elseif ($dias_restantes >= 10 && $dias_restantes <= 20) {
        $color = 'naranja_suave';
    } elseif ($dias_restantes > 20 && $dias_restantes <= 35) {
        $color = 'azul';
    } else {
        $color = 'verde';
    }

    return ['color' => $color, 'texto_dias' => $texto_dias];
}

// Ordenar vehículos: por días restantes, BAJA al final
usort($vehiculos, function($a, $b) {
    if ($a['estado'] == 'BAJA' && $b['estado'] != 'BAJA') return 1;
    if ($b['estado'] == 'BAJA' && $a['estado'] != 'BAJA') return -1;
    return calcular_dias_restantes($a['caducidad_itv']) - calcular_dias_restantes($b['caducidad_itv']);
});

// Función para formatear fecha en DD/MM/YYYY
function formatear_fecha($fecha) {
    $fecha_obj = DateTime::createFromFormat('Y-m-d', $fecha);
    return $fecha_obj ? $fecha_obj->format('d/m/Y') : $fecha;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestionar Vehículos</title>
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
    <h1><img src="images/logo.webp" alt="Logo" width="30" style="vertical-align: middle;">Impresora</h1>

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

<p><a title="imprimir_caducidades" href="imprimir_caducidades.php">IMPRIMIR CADUCIDADES</a></p>
<p><a title="imprimir_citas" href="imprimir_citas.php">IMPRIMIR CITAS</a></p>

        <h4 class="small" style="margin-top:12px;">ITVControl v.1.2</h4>
        <p class="small">B174M3 // XaeK</p>
</body>
</html>