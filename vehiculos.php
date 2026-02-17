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

// =====================
// FUNCIONES
// =====================
function calcular_dias_restantes($caducidad_itv) {
    $fecha_actual = new DateTime('today');
    $fecha_caducidad = new DateTime($caducidad_itv);
    $fecha_caducidad->setTime(0, 0, 0);
    $intervalo = $fecha_actual->diff($fecha_caducidad);
    return (int)$intervalo->format('%r%a');
}

function obtener_color_y_texto($vehiculo) {
    $estado = $vehiculo['estado'];
    $dias_restantes = calcular_dias_restantes($vehiculo['caducidad_itv']);

    if ($estado === 'ITV RECHAZADA') {
        return ['color'=>'rojo_intenso','texto_dias'=>'ITV RECHAZADA'];
    }
    if ($estado === 'BAJA') return ['color'=>'negro','texto_dias'=>'-'];
    if ($dias_restantes < 0) return ['color'=>'rojo_intenso','texto_dias'=>'Caducada hace '.abs($dias_restantes).' día'.(abs($dias_restantes)==1?'':'s')];
    if ($dias_restantes <= 1) return ['color'=>'rojo_intenso','texto_dias'=>$dias_restantes.' día'.($dias_restantes==1?'':'s')];
    if ($dias_restantes < 10) return ['color'=>'naranja_intenso','texto_dias'=>$dias_restantes.' días'];
    if ($dias_restantes <= 20) return ['color'=>'naranja_suave','texto_dias'=>$dias_restantes.' días'];
    if ($dias_restantes <= 35) return ['color'=>'azul','texto_dias'=>$dias_restantes.' días'];

    return ['color'=>'verde','texto_dias'=>$dias_restantes.' días'];
}

function formatear_fecha($fecha) {
    $fecha_obj = DateTime::createFromFormat('Y-m-d', $fecha);
    return $fecha_obj ? $fecha_obj->format('d/m/Y') : $fecha;
}

// =====================
// ORDENAR VEHÍCULOS
// =====================
usort($vehiculos, function($a, $b) {
    if ($a['estado'] === 'ITV RECHAZADA' && $b['estado'] !== 'ITV RECHAZADA') return -1;
    if ($b['estado'] === 'ITV RECHAZADA' && $a['estado'] !== 'ITV RECHAZADA') return 1;
    if ($a['estado'] === 'BAJA' && $b['estado'] !== 'BAJA') return 1;
    if ($b['estado'] === 'BAJA' && $a['estado'] !== 'BAJA') return -1;
    return calcular_dias_restantes($a['caducidad_itv']) - calcular_dias_restantes($b['caducidad_itv']);
});

// =====================
// CARGAR VERSIÓN Y AUTOR
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
<title>Gestionar Vehículos</title>
<link rel="icon" href="images/logo.webp">
<link rel="stylesheet" href="style.css">

<style>
.negro{background:black;color:grey}
.rojo_intenso{background:#cc0000;color:white}
.naranja_intenso{background:#ff6600;color:white}
.naranja_suave{background:#ffae0d;color:white}
.azul{background:#3399ff;color:white}
.verde{background:#4CAF50;color:white}

table{border-collapse:collapse;width:100%}
th,td{border:1px solid #ccc;padding:8px;text-align:left}
th{background:#eee}

/* ===== MODO OSCURO AUTOMÁTICO ===== */
@media (prefers-color-scheme: dark) {
    body{background:#000;color:#ddd}
    h1,h2,h3,h4,p,strong{color:#ddd}
    th{background:#222;color:#fff}
    .menu img{filter: invert(1) hue-rotate(180deg)}
    h1 img{filter:none} /* logo.webp NO se invierte */
}
  body {
    margin: 15px;       /* margen superior, inferior, izquierdo y derecho */
    font-family: Arial, sans-serif; /* fuente consistente */
}

</style>
</head>
<body>

<div class="user-info" style="position:fixed;top:10px;right:15px;text-align:right;font-size:14px;">
    <strong><?= $_SESSION['usuario'] ?> | <?= $_SESSION['tipo'] ?></strong>
        <div id="fecha-hora"></div>
</div>

<br>

<h1>
<img src="images/logo.webp" width="30" style="vertical-align: middle;"> Gestionar Vehículos
</h1>

<br>

    <div class="menu">
        <a title="index" href="index.php"><img src="images/index.webp" alt="index" width="80"></a>
        <a title="citas" href="citas.php"><img src="images/citas.webp" alt="citas" width="80"></a>
        <a title="vehiculos" href="vehiculos.php"><img src="images/vehiculos.webp" alt="vehiculos" width="80"></a>
        <?php if ($is_admin): ?>
            <a title="estaciones" href="estaciones.php"><img src="images/estaciones.webp" alt="estaciones" width="80"></a>
            <a title="seguridad" href="ips_bloqueadas.php"><img src="images/secury.webp" alt="seguridad" width="80"></a>
        <?php endif; ?>
        <?php if ($is_superadmin): ?>
            <a title="usuarios" href="usuarios.php"><img src="images/usuarios.webp" alt="usuarios" width="80"></a>
        <?php endif; ?>
        <a title="imprimir" href="imprimir.php"><img src="images/imprimir.webp" alt="imprimir" width="80"></a>
        <a title="logout" href="logout.php"><img src="images/logout.webp" alt="logout" width="80"></a>
    </div>

<?php if(isset($error)): ?>
<p style="color:red"><?= $error ?></p>
<?php endif; ?>

<?php if($is_colab): ?>

<br>

<h2>Añadir Vehículo</h2>

<br>

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
<?php foreach($vehiculos as $vehiculo):
$info = obtener_color_y_texto($vehiculo);
$dias_restantes = calcular_dias_restantes($vehiculo['caducidad_itv']);
?>
<tr class="<?= $info['color'] ?>">
    <td><?= htmlspecialchars($vehiculo['vehiculo']) ?></td>
    <td><?= htmlspecialchars($vehiculo['matricula']) ?></td>
    <td><?= htmlspecialchars($vehiculo['tipo']) ?></td>
    <td>
        <?php
        if ($vehiculo['estado'] === 'BAJA') echo 'BAJA';
        elseif ($vehiculo['estado'] === 'ITV RECHAZADA') echo 'ITV RECHAZADA';
        elseif ($dias_restantes < 0) echo 'ITV CADUCADA';
        elseif ($dias_restantes == 0) echo 'CADUCA HOY';
        elseif ($dias_restantes == 1) echo 'CADUCA MAÑANA';
        else echo htmlspecialchars($vehiculo['estado']);
        ?>
    </td>
    <td><?= formatear_fecha($vehiculo['caducidad_itv']) ?></td>
    <td><?= $info['texto_dias'] ?></td>
    <?php if($is_colab): ?><td><a href="editar_vehiculo.php?id=<?= urlencode($vehiculo['matricula']) ?>">Editar</a></td><?php endif; ?>
    <?php if($is_admin): ?><td><a href="eliminar_vehiculo.php?id=<?= urlencode($vehiculo['matricula']) ?>">Eliminar</a></td><?php endif; ?>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<h4 class="small" style="margin-top:12px;text-align:left;"><?= htmlspecialchars($version) ?></h4>
<p class="small" style="text-align:left;"><?= htmlspecialchars($autor) ?></p>
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