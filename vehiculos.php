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

// Ordenar vehículos: primero "ITV RECHAZADA", luego por días restantes, y "BAJA" al final
usort($vehiculos, function($a, $b) {
    // Prioridad 1: ITV RECHAZADA arriba del todo
    if ($a['estado'] === 'ITV RECHAZADA' && $b['estado'] !== 'ITV RECHAZADA') return -1;
    if ($b['estado'] === 'ITV RECHAZADA' && $a['estado'] !== 'ITV RECHAZADA') return 1;

    // Prioridad 2: BAJA al final
    if ($a['estado'] === 'BAJA' && $b['estado'] !== 'BAJA') return 1;
    if ($b['estado'] === 'BAJA' && $a['estado'] !== 'BAJA') return -1;

    // Prioridad 3: Ordenar por días restantes (menor a mayor)
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
    <link rel="shortcut icon" href="images/logo.webp">
    <link rel="icon" sizes="64x64" href="images/logo.webp">
    <link rel="apple-touch-icon" sices="180x180" href="images/logo.webp">
    <meta charset="UTF-8">
    <title>Gestionar Vehículos</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .negro { background-color: black; color: grey; }
        .rojo_intenso { background-color: #cc0000; color: white; }
        .naranja_intenso { background-color: #ff6600; color: white; }
        .naranja_suave { background-color: #ffae0d; color: white; }
        .azul { background-color: #3399ff; color: white; }
        .verde { background-color: #4CAF50; color: white; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background-color: #eee; }
    </style>
</head>
<body>
    <h1><img src="images/logo.webp" alt="Logo" width="30" style="vertical-align: middle;">Gestionar Vehículos</h1>

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

    <?php if (isset($error)): ?>
        <p style="color: red;"><?= $error ?></p>
    <?php endif; ?>

    <?php if ($is_admin): ?>
        <h2>Añadir Vehículo</h2>
        <form method="POST">
            <label>Vehículo:</label><input type="text" name="vehiculo" required><br><br>
            <label>Matrícula:</label><input type="text" name="matricula" required><br><br>
<label>Tipo:</label>
<select name="tipo" required>
    <option value="Turismo, Transporte mercancías hasta 3500 kg y cuadriciclos">Turismo, Transporte mercancías hasta 3500 kg y cuadriciclos</option>
    <option value="Transporte mercancías más de 3500 kg">Transporte mercancías más de 3500 kg</option>
    <option value="Transporte mercancías más de 3500 kg (Cabeza tractora + Remolque)">Transporte mercancías más de 3500 kg (Cabeza tractora + Remolque)</option>
    <option value="Autobuses y microbuses">Autobuses y microbuses</option>
    <option value="Verificación taxímetro">Verificación taxímetro</option>
    <option value="Periódica taxi con verificación taxímetro">Periódica taxi con verificación taxímetro</option>
    <option value="Periódica taxi sin verificación taxímetro">Periódica taxi sin verificación taxímetro</option>
    <option value="Ciclomotores de 2 y 3 ruedas, motocicletas y quads/vehículos similares y ATVs">Ciclomotores de 2 y 3 ruedas, motocicletas y quads/vehículos similares y ATVs</option>
    <option value="Agrícolas y Obras y Servicios (excepto quads/vehículos similares y ATVs)">Agrícolas y Obras y Servicios (excepto quads/vehículos similares y ATVs)</option>
    <option value="Tractor + Remolque (Agrícolas y Obras y Servicios)">Tractor + Remolque (Agrícolas y Obras y Servicios)</option>
</select>

<br><br>
            <label>Estado:</label>
            <select name="estado">
                <option value="ACTIVO">ACTIVO</option>
                <option value="ITV RECHAZADA">ITV RECHAZADA</option>
                <option value="BAJA">BAJA</option>
            </select><br><br>
            <label>Caducidad ITV:</label><input type="date" name="caducidad_itv" required><br><br>
            <input type="submit" value="Añadir Vehículo">
        </form>
    <?php endif; ?>

    <h2>Lista de Vehículos</h2>
    <table>
        <thead>
            <tr>
                <th>Vehículo</th>
                <th>Matrícula</th>
                <th>Tipo</th>
                <th>Estado</th>
                <th>Caducidad ITV</th>
                <th>Días para Caducar</th>
                <?php if ($is_admin): ?>
                    <th>Acciones</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($vehiculos as $vehiculo): ?>
                <?php 
                    $info = obtener_color_y_texto($vehiculo);
                ?>
                <tr class="<?= $info['color'] ?>">
                    <td><?= htmlspecialchars($vehiculo['vehiculo']) ?></td>
                    <td><?= htmlspecialchars($vehiculo['matricula']) ?></td>
                    <td><?= htmlspecialchars($vehiculo['tipo']) ?></td>
                    <td><?= htmlspecialchars($vehiculo['estado']) ?></td>
                    <td><?= formatear_fecha($vehiculo['caducidad_itv']) ?></td>
                    <td><?= $info['texto_dias'] ?></td>
                    <?php if ($is_admin): ?>
                        <td>
                            <a href="editar_vehiculo.php?id=<?= urlencode($vehiculo['matricula']) ?>">Editar</a> |
                            <a href="eliminar_vehiculo.php?id=<?= urlencode($vehiculo['matricula']) ?>">Eliminar</a>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

        <h4 class="small" style="margin-top:12px;">ITVControl v.1.2</h4>
        <p class="small">B174M3 // XaeK</p>
</body>
</html>