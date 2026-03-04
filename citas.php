<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit();
}

$is_colab = isset($_SESSION['tipo']) && in_array($_SESSION['tipo'], ['Colaborador', 'Administrador', 'SuperAdministrador']);
$is_admin = isset($_SESSION['tipo']) && in_array($_SESSION['tipo'], ['Administrador', 'SuperAdministrador']);
$is_superadmin = isset($_SESSION['tipo']) && $_SESSION['tipo'] === 'SuperAdministrador';

$flota_usuario = $_SESSION['flota'] ?? null;

// =====================
// CARGAR CITAS Y VEHÍCULOS
// =====================
$citas_file = 'citas.json';
if (!file_exists($citas_file)) file_put_contents($citas_file, json_encode([]));
$citas = json_decode(file_get_contents($citas_file), true);

$vehiculos_file = 'vehiculos.json';
if (!file_exists($vehiculos_file)) die("El archivo de vehículos no existe.");
$vehiculos = json_decode(file_get_contents($vehiculos_file), true);
usort($vehiculos, fn($a,$b) => strnatcasecmp($a['vehiculo'], $b['vehiculo']));

// =====================
// CARGAR ESTACIONES
// =====================
$estaciones_file = 'estaciones.json';
if (!file_exists($estaciones_file)) {
    file_put_contents($estaciones_file, json_encode(['Tambre','Sionlla','Cacheiras'], JSON_PRETTY_PRINT));
}
$estaciones = json_decode(file_get_contents($estaciones_file), true);

// =====================
// CARGAR FLOTAS EXISTENTES (para SuperAdmin)
// =====================
$usuarios_file = 'usuarios.json';
$flotas_existentes = [];
if ($is_superadmin && file_exists($usuarios_file)) {
    $usuarios = json_decode(file_get_contents($usuarios_file), true);
    foreach ($usuarios as $u) {
        if (!empty($u['flota']) && !in_array($u['flota'],$flotas_existentes)) {
            $flotas_existentes[] = $u['flota'];
        }
    }
}

// =====================
// VEHÍCULOS DISPONIBLES PARA EL FORMULARIO
// =====================
if ($is_superadmin) {
    // SuperAdmin puede ver todos los vehículos
    $vehiculos_disponibles = $vehiculos;
} else {
    // Colaborador o Administrador: solo vehículos de su propia flota
    $flota_usuario_upper = strtoupper($flota_usuario ?? '');
    $vehiculos_disponibles = array_filter($vehiculos, function($v) use ($flota_usuario_upper) {
        return isset($v['flota']) && strtoupper($v['flota']) === $flota_usuario_upper;
    });
}

// =====================
// PROCESAR FORMULARIO
// =====================
if ($is_colab && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fecha_cita'])) {

    $fecha_cita = $_POST['fecha_cita'] ?? '';
    $hora_cita = $_POST['hora_cita'] ?? '';
    $estacion_cita = $_POST['estacion_cita'] ?? '';
    $tipo_cita = $_POST['tipo_cita'] ?? 'Primera';
    $vehiculo = $_POST['vehiculo'] ?? '';

    // Determinar flota de la cita
    if ($is_superadmin) {
        $flota_cita = $_POST['flota'] ?? '';
        if (empty($flota_cita)) $error = "Debes seleccionar una flota para la cita.";
    } else {
        $flota_cita = $flota_usuario ?? '';
    }

    if (empty($error) && $fecha_cita && $hora_cita && $estacion_cita) {
        // Validar vehículo si se ha seleccionado
        if ($vehiculo !== '') {
            $vehiculo_valido = false;
            foreach ($vehiculos as $v) {
                if ($v['matricula'] === $vehiculo) {
                    $vehiculo_valido = true;
                    break;
                }
            }
            if (!$vehiculo_valido) $error = "Vehículo no permitido o no encontrado.";
        }

        if (empty($error)) {
            // Generar ID único de cita
            $anio = substr(date('Y'), -3);
            $prefijo = 'AA';
            $ultimo_num = 0;
            foreach ($citas as $c) {
                if (isset($c['id_cita']) && preg_match('/^'.$anio.$prefijo.'(\d{3})$/', $c['id_cita'], $m)) {
                    $num = intval($m[1]);
                    if ($num > $ultimo_num) $ultimo_num = $num;
                }
            }
            $id_cita = $anio.$prefijo.str_pad($ultimo_num + 1,3,'0',STR_PAD_LEFT);

            // Añadir cita
            $nueva_cita = [
                'fecha_cita' => $fecha_cita,
                'hora_cita' => $hora_cita,
                'estacion_cita' => $estacion_cita,
                'tipo_cita' => $tipo_cita,
                'vehiculo' => $vehiculo,
                'flota' => $flota_cita,
                'id_cita' => $id_cita
            ];
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
        if ($v['matricula'] === $matricula) return $v['vehiculo'].' - '.$v['matricula'];
    }
    return $matricula;
}
// =====================
// VERSION Y AUTOR
// =====================
$version = 'v.1.0';
$autor = 'Desconocido';
if (file_exists('version.xk')) {
    $lineas = file('version.xk', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (isset($lineas[0])) $version = trim($lineas[0]);
    if (isset($lineas[1])) $autor = trim($lineas[1]);
}
// =====================
// FILTRAR CITAS FUTURAS POR FLOTA
// =====================
$ahora = new DateTime();
$citas = array_filter($citas, function($cita) use($ahora, $is_superadmin, $flota_usuario) {
    $dt = DateTime::createFromFormat('Y-m-d H:i', $cita['fecha_cita'].' '.$cita['hora_cita']);
    if (!$dt || $dt < $ahora) return false;
    if (!$is_superadmin) {
        return strtoupper($cita['flota'] ?? '') === strtoupper($flota_usuario ?? '');
    }
    return true; // SuperAdmin ve todas
});
usort($citas, fn($a,$b) => strtotime($a['fecha_cita'].' '.$a['hora_cita']) <=> strtotime($b['fecha_cita'].' '.$b['hora_cita']));
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>ITVGestion</title>
<link rel="icon" href="images/logo.webp">
<link rel="stylesheet" href="style.css">
<style>
/* ===== BASE ===== */
body { margin:15px; font-family:Arial,sans-serif; }

/* ===== USUARIO ===== */
.user-info{
    position:fixed; top:10px; right:15px; text-align:right; font-size:14px;
    background:rgba(255,255,255,0.6); padding:5px 10px; border-radius:8px;
}
.user-info strong{display:block;}
.user-info small{color:#4a90e2;font-weight:bold;}

/* ===== TABLA ===== */
table { border-collapse: collapse; width: 100%; table-layout:auto; }
th, td { border:1px solid #ccc; padding:8px; vertical-align:top; }
th { background:#eee; }

/* Columnas iguales a index.php */
th:nth-child(1), td:nth-child(1){white-space:normal;word-break:keep-all;width:1%;max-width:100%;}
th:nth-child(2), td:nth-child(2){white-space:nowrap;}
th:nth-child(3), td:nth-child(3){white-space:normal;word-break:keep-all;}
th:nth-child(4), td:nth-child(4){white-space:normal;word-break:keep-all;width:auto;min-width:80px;}
th:nth-child(5), td:nth-child(5){white-space:nowrap;}
th:nth-child(6), td:nth-child(6){white-space:normal;word-break:keep-all;width:auto;min-width:80px;}
th:nth-child(7), td:nth-child(7){white-space:nowrap;}

tr:nth-child(even) td { background:#f8f8f8; }
tr:hover td { background:#dce6f1; }

/* FORMULARIO */
input, select { padding:5px; margin:2px 0; border-radius:4px; border:1px solid #ccc; background:#fff; color:#000; }
input[type="submit"] { cursor:pointer; background:#4a90e2; color:#fff; border:none; padding:6px 12px; border-radius:4px; }
input[type="submit"]:hover { background:#1c75bc; }

/* DIAS */
.dia-rojo { color:red; font-weight:bold; }
.dia-normal { color:inherit; font-weight:bold; }

/* MODO OSCURO */
@media (prefers-color-scheme: dark) {
    body{background:#000;color:#ddd;}
    h1,h2,h3,h4,p,strong{color:#ddd;}
    th{background:#1c75bc;color:#fff;}
    td{color:#ddd;border-color:#555;}
    tr:nth-child(even) td { background:#111827; }
    tr:hover td { background:#1e293b; }

    input, select { background:#111;color:#fff;border:1px solid #555; }
    input[type="submit"] { background: linear-gradient(135deg,#1c75bc,#0066cc); border:1px solid #005bb5; color:#fff; }
    input[type="submit"]:hover { background: linear-gradient(135deg,#005bb5,#1c75bc); }

    .menu a img:not([alt="Logo"]){filter: invert(1) hue-rotate(180deg);}
    h1 img{filter:none;}
    .user-info{background:rgba(0,0,0,0.5);}
    .user-info small{color:#3399ff;}
}
.boton-editar{
    background:#4CAF50;
    color:white;
    padding:3px 7px;
    border-radius:4px;
    text-decoration:none;
}
.boton-eliminar{
    background:#cc0000;
    color:white;
    padding:3px 7px;
    border-radius:4px;
    text-decoration:none;
}
.boton-editar:hover { background:#45a049; }
.boton-eliminar:hover { background:#b30000; }
</style>
</head>
<body>

<div class="user-info">
    <strong><?=htmlspecialchars($_SESSION['usuario'])?> | <?=htmlspecialchars($_SESSION['tipo'])?></strong>
    <small><?= $is_superadmin ? "Todas las flotas" : ($flota_usuario ? strtoupper($flota_usuario) : "Sin flota asignada") ?></small>
    <div id="fecha-hora"></div>
</div>

<h1><img src="images/logo.webp" width="30" style="vertical-align: middle;"> Gestionar citas de ITV</h1>
<hr style="border:1px solid #4a90e2; margin:10px 0 20px 0;">

    <div class="menu">
      <a title="index" href="index.php"><img src="images/index.webp" alt="index" width="80"></a>
      <a title="citas" href="citas.php"><img src="images/citas.webp" alt="citas" width="80"></a>
      <a title="vehiculos" href="vehiculos.php"><img src="images/vehiculos.webp" alt="vehiculos" width="80"></a>
     <?php if(in_array($_SESSION['tipo'], ['Administrador','SuperAdministrador'])): ?>
      <a title="estaciones" href="estaciones.php"><img src="images/estaciones.webp" alt="estaciones" width="80"></a>
      <a title="seguridad" href="ips_bloqueadas.php"><img src="images/secury.webp" alt="seguridad" width="80"></a>
      <a title="usuarios" href="usuarios.php"><img src="images/usuarios.webp" alt="usuarios" width="80"></a>
     <?php endif; ?>
      <a title="imprimir" href="imprimir.php"><img src="images/imprimir.webp" alt="imprimir" width="80"></a>
      <a title="logout" href="logout.php"><img src="images/logout.webp" alt="logout" width="80"></a>
    </div>
  
    <br><br><br>

<script>
function actualizarFechaHora(){
    const d=new Date();
    document.getElementById('fecha-hora').innerText =
        d.toLocaleDateString('es-ES')+' '+d.toLocaleTimeString('es-ES');
}
actualizarFechaHora();
setInterval(actualizarFechaHora,1000);
</script>


<?php if(isset($error)): ?>
<p class="dia-rojo"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<?php if($is_colab): ?>
<h2>Añadir Cita</h2>
<form method="POST">
    <label>Fecha:</label><input type="date" name="fecha_cita" required><br>
    <label>Hora:</label><input type="time" name="hora_cita" required><br>
    <label>Estación:</label><select name="estacion_cita" required>
      <?php foreach($estaciones as $e): ?>
      <?php
      // Solo mostrar estaciones que incluyan la flota del usuario, salvo SuperAdmin
      if(!$is_superadmin && !in_array($flota_usuario, $e['flotas'] ?? [])) continue;
      ?>
      <option value="<?=htmlspecialchars($e['nombre'])?>"><?=htmlspecialchars($e['nombre'])?></option>
      <?php endforeach; ?>
     </select><br>
    <label>Tipo:</label><select name="tipo_cita">
        <option value="Primera">Primera</option>
        <option value="Segunda">Segunda</option>
    </select><br>
    <label>Vehículo:</label>
    <select name="vehiculo">
        <option value="">Sin asignar</option>
        <?php foreach($vehiculos_disponibles as $v): ?>
        <option value="<?= htmlspecialchars($v['matricula']) ?>"><?= htmlspecialchars($v['vehiculo'].' - '.$v['matricula']) ?></option>
        <?php endforeach; ?>
    </select><br>

    <?php if($is_superadmin): ?>
        <label>Flota:</label><select name="flota" required>
            <?php foreach($flotas_existentes as $f): ?>
                <option value="<?= htmlspecialchars($f) ?>"><?= htmlspecialchars($f) ?></option>
            <?php endforeach; ?>
        </select><br>
    <?php endif; ?>
    <br>

    <input type="submit" value="Añadir Cita">
</form>
<?php endif; ?>
  
  <h2 style="display:inline-flex; align-items:center; gap:8px; margin:0;">
  Lista de Citas Futuras
  <button class="boton-vista" onclick="window.location.href='calendario_citas.php'">Cambiar a modo calendario</button>
</h2>

<style>
.boton-vista {
  background:#4a90e2;
  color:#fff;
  border:none;
  padding:4px 10px;
  border-radius:6px;
  cursor:pointer;
  font-size:13px;
  transition:background 0.2s ease-in-out;
}
.boton-vista:hover {
  background:#1c75bc;
}
@media (prefers-color-scheme: dark) {
  .boton-vista {
    background: linear-gradient(135deg,#1c75bc,#0066cc);
    color:#fff;
    border:1px solid #005bb5;
  }
  .boton-vista:hover {
    background: linear-gradient(135deg,#005bb5,#1c75bc);
  }
}
</style>
  
<table>
<thead>
<tr>
    <th>ID</th>
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
    <tr>
        <td><?= htmlspecialchars($cita['id_cita'] ?? '-') ?></td>
        <td><?= formatear_fecha($cita['fecha_cita']) ?></td>
        <td><?= htmlspecialchars($cita['hora_cita']) ?></td>
        <td><?= htmlspecialchars($cita['estacion_cita']) ?></td>
        <td><?= htmlspecialchars($cita['tipo_cita']) ?></td>
        <td><?= htmlspecialchars(mostrarVehiculo($cita['vehiculo'], $vehiculos)) ?></td>
      <?php if ($is_admin): ?>
        <td>
      <?php if($is_admin): ?>
            <a href="editar_cita.php?id=<?= urlencode($cita['id_cita']) ?>" class="boton-editar">Editar</a>
            | 
            <a href="eliminar_cita.php?id=<?= urlencode($cita['id_cita']) ?>" class="boton-eliminar">Eliminar</a>
        <?php endif; ?>
        </td>
        <?php endif; ?>
    </tr>
    <?php endforeach; ?>
<?php else: ?>
    <tr><td colspan="<?= $is_admin ? 7 : 6 ?>">No hay citas futuras.</td></tr>
<?php endif; ?>
</tbody>
</table>

<h4 class="small version-title" style="margin-top:12px; text-align:left;"><?= htmlspecialchars($version) ?></h4>
<p class="small version-author" style="text-align:left;"><?= htmlspecialchars($autor) ?></p>

</body>
</html>