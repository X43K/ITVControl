<?php
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario'])) {
    header('Location: index.php');
    exit();
}

// Verificar tipo de usuario
$is_colab = isset($_SESSION['tipo']) && in_array($_SESSION['tipo'], ['Colaborador', 'Administrador', 'SuperAdministrador']);
$is_admin = isset($_SESSION['tipo']) && in_array($_SESSION['tipo'], ['Administrador', 'SuperAdministrador']);
$is_superadmin = isset($_SESSION['tipo']) && $_SESSION['tipo'] === 'SuperAdministrador';

// Verificar si el archivo vehiculos.json existe y es accesible
$vehiculos_file = 'vehiculos.json';
if (!file_exists($vehiculos_file)) {
    file_put_contents($vehiculos_file, json_encode([]));
}

// Cargar vehículos desde el archivo JSON
$vehiculos = json_decode(file_get_contents($vehiculos_file), true);

// Procesar formulario de añadir vehículo
if ($is_colab && $_SERVER['REQUEST_METHOD'] === 'POST') {
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

// Calcular días restantes
function calcular_dias_restantes($caducidad_itv) {
    $fecha_actual = new DateTime('today');
    $fecha_caducidad = new DateTime($caducidad_itv);
    $fecha_caducidad->setTime(0, 0, 0);
    $intervalo = $fecha_actual->diff($fecha_caducidad);
    return (int)$intervalo->format('%r%a');
}

// Obtener color y texto
function obtener_color_y_texto($vehiculo) {
    $estado = $vehiculo['estado'];
    $dias_restantes = calcular_dias_restantes($vehiculo['caducidad_itv']);
    $color = 'verde';
    $texto_dias = '';

    // Colores por rango
    if ($estado == 'BAJA') {
        $color = 'negro';
        $texto_dias = '-';
    } elseif ($dias_restantes < 0) {
        $color = 'rojo_intenso';
    } elseif ($dias_restantes == 0 || $dias_restantes == 1) {
        $color = 'rojo_intenso';
    } elseif ($dias_restantes < 10) {
        $color = 'naranja_intenso';
    } elseif ($dias_restantes <= 20) {
        $color = 'naranja_suave';
    } elseif ($dias_restantes <= 35) {
        $color = 'azul';
    }

    // Texto de días
    if ($estado == 'BAJA') {
        $texto_dias = '-';
    } elseif ($dias_restantes < 0) {
        $dias_pasados = abs($dias_restantes);
        $texto_dias = "Caducada hace " . $dias_pasados . " día" . ($dias_pasados != 1 ? "s" : "");
    } else {
        $texto_dias = $dias_restantes . " día" . ($dias_restantes != 1 ? "s" : "");
    }

    // 🔴 FORZAR ROJO SI ITV RECHAZADA
    if ($estado === 'ITV RECHAZADA') {
        $color = 'rojo_intenso';
    }

    return ['color' => $color, 'texto_dias' => $texto_dias];
}

// Ordenar vehículos
usort($vehiculos, function($a, $b) {
    if ($a['estado'] === 'ITV RECHAZADA' && $b['estado'] !== 'ITV RECHAZADA') return -1;
    if ($b['estado'] === 'ITV RECHAZADA' && $a['estado'] !== 'ITV RECHAZADA') return 1;
    if ($a['estado'] === 'BAJA' && $b['estado'] !== 'BAJA') return 1;
    if ($b['estado'] === 'BAJA' && $a['estado'] !== 'BAJA') return -1;
    return calcular_dias_restantes($a['caducidad_itv']) - calcular_dias_restantes($b['caducidad_itv']);
});

// Formatear fecha
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
    <p style="color: red;"><?= $error ?></p>
<?php endif; ?>

<?php if ($is_colab): ?>
<h2>Añadir Vehículo</h2>
<form method="POST">
    <label>Vehículo:</label><input type="text" name="vehiculo" required><br><br>
    <label>Matrícula:</label><input type="text" name="matricula" required><br><br>
    <label>Tipo:</label>
    <select name="tipo" required>
        <option value="Turismo">Turismo</option>
        <option value="Transporte mercancías hasta 3500 kg">Transporte mercancías hasta 3500 kg</option>
        <option value="Transporte mercancías más de 3500 kg">Transporte mercancías más de 3500 kg</option>
        <option value="Autobuses y microbuses">Autobuses y microbuses</option>
        <option value="Agrícolas">Agrícolas</option>
        <option value="Motocicletas y quads">Motocicletas y quads</option>
    </select><br><br>
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
    <?php if ($is_colab): ?><th>Editar</th><?php endif; ?>
    <?php if ($is_admin): ?><th>Eliminar</th><?php endif; ?>
</tr>
</thead>
<tbody>
<?php foreach ($vehiculos as $vehiculo):
    $info = obtener_color_y_texto($vehiculo);
    $dias_restantes = calcular_dias_restantes($vehiculo['caducidad_itv']);
?>
<tr class="<?= $info['color'] ?>">
    <td><?= htmlspecialchars($vehiculo['vehiculo']) ?></td>
    <td><?= htmlspecialchars($vehiculo['matricula']) ?></td>
    <td><?= htmlspecialchars($vehiculo['tipo']) ?></td>
    <td>
        <?php
        if ($vehiculo['estado'] === 'BAJA') {
            echo 'BAJA';
        } elseif ($vehiculo['estado'] === 'ITV RECHAZADA') {
            echo 'ITV RECHAZADA';
        } elseif ($dias_restantes < 0) {
            echo 'ITV CADUCADA';
        } elseif ($dias_restantes == 0) {
            echo 'CADUCA HOY';
        } elseif ($dias_restantes == 1) {
            echo 'CADUCA MAÑANA';
        } else {
            echo htmlspecialchars($vehiculo['estado']);
        }
        ?>
    </td>
    <td><?= formatear_fecha($vehiculo['caducidad_itv']) ?></td>
    <td><?= $info['texto_dias'] ?></td>
    <?php if ($is_colab): ?>
        <td><a href="editar_vehiculo.php?id=<?= urlencode($vehiculo['matricula']) ?>">Editar</a></td>
    <?php endif; ?>
    <?php if ($is_admin): ?>
        <td><a href="eliminar_vehiculo.php?id=<?= urlencode($vehiculo['matricula']) ?>">Eliminar</a></td>
    <?php endif; ?>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<h4 class="small" style="margin-top:12px;">ITVControl v.1.4</h4>
<p class="small">B174M3 // XaeK</p>
</body>
</html>
