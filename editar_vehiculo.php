<?php
session_start();

// Verificar login y permisos (Administrador, SuperAdministrador, Colaborador)
if (!isset($_SESSION['usuario']) || !in_array($_SESSION['tipo'], ['Administrador','SuperAdministrador','Colaborador'])) {
    header('Location: index.php'); exit();
}

$is_admin = in_array($_SESSION['tipo'], ['Administrador','SuperAdministrador']);
$is_superadmin = $_SESSION['tipo'] === 'SuperAdministrador';

// Obtener matrícula del vehículo
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: vehiculos.php'); exit();
}
$id_vehiculo = $_GET['id'];

// Cargar vehículos
$vehiculos_file = 'vehiculos.json';
if (!file_exists($vehiculos_file)) die("El archivo de vehículos no existe.");
$vehiculos = json_decode(file_get_contents($vehiculos_file), true);

// Buscar vehículo a editar
$vehiculo_editar = null;
foreach ($vehiculos as &$vehiculo) {
    if ($vehiculo['matricula'] === $id_vehiculo) {
        $vehiculo_editar = &$vehiculo; break;
    }
}
if ($vehiculo_editar === null) die("No se encontró el vehículo: " . htmlspecialchars($id_vehiculo));

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['estado']) && !empty($_POST['caducidad_itv'])) {
        $vehiculo_editar['estado'] = $_POST['estado'];
        $vehiculo_editar['caducidad_itv'] = $_POST['caducidad_itv'];

        if (file_put_contents($vehiculos_file, json_encode($vehiculos, JSON_PRETTY_PRINT))) {
            header('Location: vehiculos.php'); exit();
        } else $error = "No se pudo guardar los cambios. Verifique permisos del archivo.";
    } else $error = "Todos los campos son obligatorios.";
}

// =====================
// CARGA DE VERSION.XK
// =====================
$version_file = 'version.xk';
$version = 'v.1.0'; $autor = '';
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
<title>Editar Vehículo</title>
<link rel="shortcut icon" href="images/logo.webp">
<style>
body { font-family: Arial, sans-serif; background:#fff; color:#000; padding:15px; }
h1 { font-size:20px; }
label { display:block; margin-top:10px; font-weight:bold; }

/* Vehículo y matrícula */
span.texto-vehiculo { display:block; font-size:25px; font-weight:bold; margin-top:4px; } /* Vehículo +5 puntos */
span.texto-matricula { display:block; font-size:20px; font-weight:bold; margin-top:4px; }

select, input[type=date] { width:250px; padding:4px; margin-top:2px; background:#fff; color:#000; border:1px solid #ccc; }
input[type=submit] { margin-top:12px; padding:6px 12px; background:#004aad; color:#fff; border:none; cursor:pointer; }
input[type=submit]:hover { background:#0066ff; }

.menu { margin:15px 0; }
.menu img { margin-right:5px; vertical-align:middle; }

/* ===== MODO OSCURO AUTOMÁTICO ===== */
@media (prefers-color-scheme: dark) {
    body { background:#000; color:#ddd; }
    h1,label,p { color:#ddd; }

    span.texto-vehiculo,
    span.texto-matricula { color:#ddd; }

    select, input[type=date] {
        background:#222; 
        color:#ddd; 
        border:1px solid #555;
    }

    input[type=submit] {
        background:#0066ff; 
        color:#fff; 
        border:none;
    }
    input[type=submit]:hover { background:#3399ff; }

    .menu img { filter: invert(1) hue-rotate(180deg); }
    h1 img { filter:none; }
}
</style>
</head>
<body>

<div class="user-info" style="position:fixed;top:10px;right:15px;text-align:right;font-size:14px;">
    <strong><?= htmlspecialchars($_SESSION['usuario']) ?> | <?= htmlspecialchars($_SESSION['tipo']) ?></strong>
</div>

<h1>
    <img src="images/logo.webp" width="28" style="vertical-align: middle;">
    Editar Vehículo
</h1>

</br>

<div class="menu">
    <a href="index.php"><img src="images/index.webp" width="80" alt="index"></a>
    <a href="citas.php"><img src="images/citas.webp" width="80" alt="citas"></a>
    <a href="vehiculos.php"><img src="images/vehiculos.webp" width="80" alt="vehiculos"></a>
    <?php if($is_admin): ?><a href="estaciones.php"><img src="images/estaciones.webp" width="80" alt="estaciones"></a><?php endif; ?>
    <?php if($is_superadmin): ?><a href="usuarios.php"><img src="images/usuarios.webp" width="80" alt="usuarios"></a><?php endif; ?>
    <a href="imprimir.php"><img src="images/imprimir.webp" width="80" alt="imprimir"></a>
    <a href="logout.php"><img src="images/logout.webp" width="80" alt="logout"></a>
</div>

</br>

<form method="POST">
    <label>Vehículo:</label>
    <span class="texto-vehiculo"><?= htmlspecialchars($vehiculo_editar['vehiculo']) ?></span>

    <label>Matrícula:</label>
    <span class="texto-matricula"><?= htmlspecialchars($vehiculo_editar['matricula']) ?></span>

    <label>Estado:</label>
    <select name="estado">
        <option value="ACTIVO" <?= $vehiculo_editar['estado']==='ACTIVO'?'selected':'' ?>>ACTIVO</option>
        <option value="ITV RECHAZADA" <?= $vehiculo_editar['estado']==='ITV RECHAZADA'?'selected':'' ?>>ITV RECHAZADA</option>
        <option value="BAJA" <?= $vehiculo_editar['estado']==='BAJA'?'selected':'' ?>>BAJA</option>
    </select>

    <label>Caducidad ITV:</label>
    <input type="date" name="caducidad_itv" value="<?= htmlspecialchars($vehiculo_editar['caducidad_itv']) ?>" required>

    <input type="submit" value="Guardar Cambios">
</form>

<?php if(isset($error)): ?>
<p style="color:red;"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<h4 class="small" style="margin-top:12px;"><?= htmlspecialchars($version) ?></h4>
<p class="small"><?= htmlspecialchars($autor) ?></p>

</body>
</html>
