<?php
session_start();

/* ================= SEGURIDAD ================= */
if (!isset($_SESSION['usuario']) || !in_array($_SESSION['tipo'], ['Administrador','SuperAdministrador'])) {
    header('Location: index.php');
    exit();
}

$is_superadmin = $_SESSION['tipo'] === 'SuperAdministrador';
$flota_usuario = strtoupper(trim($_SESSION['flota'] ?? ''));

/* ================= ARCHIVOS ================= */
$usuarios_file = 'usuarios.json';
$log_file = 'usuarios-fail.log';
$registro_historico_file = 'registro_login.log';

if (!file_exists($usuarios_file)) {
    file_put_contents($usuarios_file, json_encode([]));
}

$usuarios = json_decode(file_get_contents($usuarios_file), true);
if (!is_array($usuarios)) $usuarios = [];

/* ================= RECUPERAR FLOTA SI NO HAY EN LA SESIÓN ================= */
if ($_SESSION['tipo'] === 'Administrador') {
    if ($flota_usuario === '' || $flota_usuario === null) {
        foreach ($usuarios as $u) {
            if (isset($u['usuario']) && strcasecmp($u['usuario'], $_SESSION['usuario']) === 0) {
                if (!empty($u['flota'])) {
                    $flota_usuario = strtoupper(trim($u['flota']));
                    $_SESSION['flota'] = $u['flota'];
                }
                break;
            }
        }
    }

    if ($flota_usuario === '' || $flota_usuario === null) {
        $_SESSION['error_msg'] = 'No se encontró flota asignada para tu usuario. Por favor, inicia sesión de nuevo o contacta con el administrador.';
        header('Location: index.php');
        exit();
    }
}

/* ================= FILTRAR USUARIOS SEGÚN FLOTA ================= */
if ($_SESSION['tipo'] === 'Administrador') {
    $usuarios_filtrados = [];

    foreach ($usuarios as $u) {
        $usuario_flota = strtoupper(trim($u['flota'] ?? ''));

        // Administradores no ven SuperAdministradores
        if (($u['tipo'] ?? '') === 'SuperAdministrador') continue;

        // Solo incluir usuarios de la misma flota
        if ($usuario_flota === $flota_usuario) {
            $usuarios_filtrados[] = $u;
        }
    }

    $usuarios = $usuarios_filtrados;
}

/* ================= ORDENAR ================= */
usort($usuarios, function($a, $b) {
    return strcasecmp($a['usuario'], $b['usuario']);
});

/* ================= VER LOG HISTORICO ================= */
$log_modal_historico = null;
$total_historico = 0;

if (isset($_GET['ver_historico'])) {
    $usuario_log = $_GET['ver_historico'];
    if (file_exists($registro_historico_file)) {
        $lineas = file($registro_historico_file, FILE_IGNORE_NEW_LINES);
        $filtrado = [];
        foreach ($lineas as $linea) {
            if (stripos($linea, "Usuario: $usuario_log") !== false) {
                $filtrado[] = $linea;
            }
        }
        $filtrado = array_reverse($filtrado);
        $total_historico = count($filtrado);
        $log_modal_historico = $filtrado;
    } else {
        $log_modal_historico = [];
    }
}

/* ================= VER LOG ================= */
$log_modal = null;
$total_intentos = 0;

if (isset($_GET['ver_log'])) {
    $usuario_log = $_GET['ver_log'];
    if (file_exists($log_file)) {
        $lineas = file($log_file, FILE_IGNORE_NEW_LINES);
        $filtrado = [];
        foreach ($lineas as $linea) {
            if (stripos($linea, "Usuario: $usuario_log") !== false) {
                $filtrado[] = $linea;
            }
        }
        $filtrado = array_reverse($filtrado);
        $total_intentos = count($filtrado);
        $log_modal = $filtrado;
    } else {
        $log_modal = [];
    }
}

/* ================= LIMPIAR LOG ================= */
if (isset($_GET['limpiar_log'])) {
    $usuario_limpiar = $_GET['limpiar_log'];
    if (file_exists($log_file)) {
        $lineas = file($log_file);
        $nuevo = [];
        foreach ($lineas as $linea) {
            if (stripos($linea, "Usuario: $usuario_limpiar") === false) {
                $nuevo[] = $linea;
            }
        }
        file_put_contents($log_file, implode('', $nuevo));
    }
    header('Location: usuarios.php');
    exit();
}

/* ================= AÑADIR USUARIO ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario_nuevo = trim($_POST['usuario'] ?? '');
    $pass = $_POST['contraseña'] ?? '';
    $pass2 = $_POST['confirmar_contraseña'] ?? '';
    $tipo = $_POST['tipo'] ?? '';
    $flota = strtoupper(trim($_POST['flota'] ?? ''));

    // Administrador solo puede asignar su propia flota
    if ($_SESSION['tipo'] === 'Administrador') {
        $flota = $flota_usuario;
    }

    // Validaciones
    if ($usuario_nuevo === '' || $pass === '' || $pass2 === '' || $tipo === '') {
        $error = "Todos los campos son obligatorios.";
    } elseif ($tipo !== 'SuperAdministrador' && $flota === '') {
        $error = "La flota es obligatoria para todos los usuarios excepto SuperAdministradores.";
    } elseif ($pass !== $pass2) {
        $error = "Las contraseñas no coinciden.";
    } else {
        $existe = false;
        foreach ($usuarios as $u) {
            if (strcasecmp($u['usuario'], $usuario_nuevo) === 0) {
                $existe = true;
                break;
            }
        }
        if ($existe) {
            $error = "El usuario ya existe.";
        } else {
            $usuarios[] = [
                'usuario' => $usuario_nuevo,
                'contraseña' => password_hash($pass, PASSWORD_DEFAULT),
                'tipo' => $tipo,
                'flota' => $flota
            ];
            usort($usuarios, function($a, $b) { return strcasecmp($a['usuario'], $b['usuario']); });
            file_put_contents($usuarios_file, json_encode(array_values($usuarios), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            header('Location: usuarios.php');
            exit();
        }
    }
}

/* ================= MARCAR LOG HISTORICO ================= */
if (file_exists($registro_historico_file)) {
    $lineas_historico = file($registro_historico_file, FILE_IGNORE_NEW_LINES);
    foreach ($usuarios as &$usuario) {
        $usuario['tiene_historico'] = false;
        foreach ($lineas_historico as $linea) {
            if (stripos($linea, "Usuario: {$usuario['usuario']}") !== false) {
                $usuario['tiene_historico'] = true;
                break;
            }
        }
    }
}
unset($usuario);

/* ================= MARCAR LOGS ================= */
if (file_exists($log_file)) {
    $lineas_log = file($log_file, FILE_IGNORE_NEW_LINES);
    foreach ($usuarios as &$usuario) {
        $usuario['tiene_log'] = false;
        foreach ($lineas_log as $linea) {
            if (stripos($linea, "Usuario: {$usuario['usuario']}") !== false) {
                $usuario['tiene_log'] = true;
                break;
            }
        }
    }
}
unset($usuario);

/* ================= VERSION Y AUTOR ================= */
$version_text = 'v.1.4';
$autor_text = 'B174M3 // XaeK';
if (file_exists('version.xk')) {
    $lines = file('version.xk', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $version_text = $lines[0] ?? $version_text;
    $autor_text = $lines[1] ?? $autor_text;
}

/* ================= TAMAÑO LOGS ================= */
$registro_historico_file = 'registro_login.log';
$tamano_log_mb = 0;

if (file_exists($registro_historico_file)) {
    $tamano_log_bytes = filesize($registro_historico_file);
    $tamano_log_mb = round($tamano_log_bytes / 1024 / 1024, 2); // Convertir a MB
}

/* ================= FORMATO TAMAÑO LOGS ================= */
function formato_tamano($bytes) {
    $unidades = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($unidades) - 1) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, 2) . ' ' . $unidades[$i];
}

// Obtener tamaño del log
$registro_historico_file = 'registro_login.log';
$tamano_log_formateado = '0 B';

if (file_exists($registro_historico_file)) {
    $tamano_log_bytes = filesize($registro_historico_file);
    $tamano_log_formateado = formato_tamano($tamano_log_bytes);
}

/* ================= LIMPIAR LOGS ANTERIORES A X AÑOS ================= */
if ($is_superadmin && isset($_POST['borrar_logs_anteriores'])) {
    $anios = intval($_POST['conservar_anios']);
    if ($anios < 1) $anios = 1;

    $registro_historico_file = 'registro_login.log';

    if (file_exists($registro_historico_file)) {
        $lineas = file($registro_historico_file, FILE_IGNORE_NEW_LINES);
        $nuevo = [];

        // Fecha límite: conservar solo los logs desde esta fecha en adelante
        $fecha_limite = strtotime("-$anios years January 1");

        foreach ($lineas as $linea) {
            // Extraemos la fecha del log (primeros 19 caracteres: [YYYY-MM-DD HH:MM:SS])
            if (preg_match('/\[(\d{4}-\d{2}-\d{2}) \d{2}:\d{2}:\d{2}\]/', $linea, $matches)) {
                $fecha_linea = strtotime($matches[1]);
                if ($fecha_linea >= $fecha_limite) {
                    $nuevo[] = $linea;
                }
            }
        }

        file_put_contents($registro_historico_file, implode("\n", $nuevo) . "\n");
        $mensaje = "Logs antiguos borrados, conservando los últimos $anios años.";
    } else {
        $mensaje = "El archivo de logs no existe.";
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>ITVGestion</title>
<link rel="icon" href="images/logo.webp">
<link rel="stylesheet" href="style.css">
<style>
body { margin:15px; font-family:Arial,sans-serif; }

/* ===== CUADRO USUARIO ARRIBA ===== */
.user-info {
    position: fixed;
    top: 10px;
    right: 15px;
    text-align: right;
    font-size: 14px;
    background: rgba(255,255,255,0.6);
    padding: 5px 10px;
    border-radius: 8px;
}
.user-info strong { display: block; }
.user-info small { color:#4a90e2; font-weight:bold; }

/* MODO OSCURO */
@media (prefers-color-scheme: dark) {
    .user-info { background: rgba(0,0,0,0.5); }
    .user-info small { color:#3399ff; }
}
  
/* ===== MODO OSCURO ICONOS MENU ===== */
@media (prefers-color-scheme: dark) {
    .menu img {
        filter: invert(1) hue-rotate(180deg);
    }
    h1 img {
        filter: none;
    }
}
  
@media (prefers-color-scheme: dark) {
    body { background:#000; color:#ddd; }
    h1,h2,h3,h4,p,strong { color:#ddd; }
    
    /* Tablas */
    table {
        background: linear-gradient(135deg, #0b1726, #16233b);
        border-color:#333;
        box-shadow:2px 2px 12px rgba(255,255,255,0.05);
    }
    th { background:#1c75bc; color:#fff; }
    td { border-color:#333; color:#ddd; }
    tr:nth-child(even) td { background:#111827; }
    tr:hover td { background:#1e293b; }

    /* Cuadro info-usuarios */
    .info-usuarios {
        border:2px solid #3399ff;
        background: linear-gradient(135deg, #111827, #1e293b);
        box-shadow: 2px 2px 12px rgba(0,0,0,0.5);
        color:#ddd;
    }
    .info-usuarios::before { color:#3399ff; }

    /* Inputs y selects */
    input, select { background:#111; color:#fff; border:1px solid #555; }
    input[type="submit"], button {
        background: linear-gradient(135deg, #1c75bc, #0066cc);
        border:1px solid #005bb5;
        color:#fff;
    }
    input[type="submit"]:hover, button:hover {
        background: linear-gradient(135deg, #005bb5, #1c75bc);
    }

    /* Iconos del menú */
    .menu img:not([alt="Logo"]) { filter: invert(1) hue-rotate(180deg); }
    h1 img { filter:none; }

    /* User info */
    .user-info { background: rgba(0,0,0,0.5); color:#ddd; }
    .user-info small { color:#3399ff; }

    /* Eye icon en inputs de contraseña */
    .eye-icon { fill:#fff; }
}
  
/* ===== MENU ICONOS ===== */
.menu { margin-bottom:15px; }
.menu a { margin-right:0px; }
.menu img { width:80px; height:auto; vertical-align:middle; transition:filter 0.3s ease; }

/* ===== FORMULARIOS ===== */
input, select { padding:5px; margin:2px 0; border-radius:4px; border:1px solid #aaa; }
input[type="submit"], button {
    cursor:pointer;
    border-radius:6px;
    padding:6px 10px;
    background:linear-gradient(135deg, #4a90e2, #1c75bc);
    border:1px solid #1c75bc;
    color:#fff;
    transition:background 0.3s ease;
}
input[type="submit"]:hover, button:hover { background:linear-gradient(135deg, #1c75bc, #4a90e2); }
.eye-icon { position:absolute; right:5px; top:5px; cursor:pointer; user-select:none; width:22px; height:22px; fill:#000; }

/* ===== TABLA USUARIOS ===== */
table {
    border-collapse:collapse;
    width:100%;
    background:linear-gradient(135deg, #f8fbff, #e9f1ff);
    border-radius:12px;
    overflow:hidden;
    box-shadow:2px 2px 12px rgba(0,0,0,0.1);
}
th, td { border:1px solid #ccc; padding:8px; vertical-align:top; }
th { background:#4a90e2; color:#fff; text-transform:uppercase; letter-spacing:0.5px; }
tr:nth-child(even) td { background:#f6f9ff; }
tr:hover td { background:#dce8ff; }

/* ===== CUADRO INFO TIPOS USUARIO ===== */
.info-usuarios {
    flex:1;
    padding:20px;
    border:2px solid #4a90e2;
    border-radius:12px;
    background: linear-gradient(135deg, #f0f8ff, #dbe9ff);
    box-shadow: 2px 2px 12px rgba(0,0,0,0.15);
    font-size:14px;
    line-height:1.5;
    color: #000;
    transition: all 0.3s ease;
    position: relative;
}
.info-usuarios::before {
    content: "\2139";
    font-size: 22px;
    color: #4a90e2;
    position: absolute;
    top:10px;
    left:10px;
}
.info-usuarios:hover {
    transform: translateY(-2px);
    box-shadow: 4px 4px 14px rgba(0,0,0,0.25);
}

/* MODO OSCURO TABLA Y INFO USUARIO */
@media (prefers-color-scheme: dark) {
    table {
        background: linear-gradient(135deg, #0b1726, #16233b);
        box-shadow: 2px 2px 12px rgba(255,255,255,0.05);
    }
    tr:nth-child(even) td { background:#111827; }
    tr:hover td { background:#1e293b; }

    .info-usuarios {
        border:2px solid #3399ff;
        background: linear-gradient(135deg, #111827, #1e293b);
        box-shadow: 2px 2px 12px rgba(0,0,0,0.5);
        color:#ddd;
    }
    .info-usuarios::before { color:#3399ff; }

    .eye-icon { fill:#fff; }
}
</style>
</head>
<body>

<div class="user-info">
    <strong><?= htmlspecialchars($_SESSION['usuario']) ?> | <?= htmlspecialchars($_SESSION['tipo']) ?></strong>
    <div>
        <small><?= $is_superadmin ? "Todas las flotas" : ($flota_usuario ?: "Sin flota asignada") ?></small>
    </div>
    <div id="fecha-hora"></div>
</div>

<h1><img src="images/logo.webp" alt="Logo" width="30" style="vertical-align:middle;"> Gestionar Usuarios</h1>
<hr style="border:1px solid #4a90e2; margin:10px 0 20px 0;">

<div class="menu">
<a title="index" href="index.php"><img src="images/index.webp" alt="index"></a>
<a title="citas" href="citas.php"><img src="images/citas.webp" alt="citas"></a>
<a title="vehiculos" href="vehiculos.php"><img src="images/vehiculos.webp" alt="vehiculos"></a>
<?php if(in_array($_SESSION['tipo'], ['Administrador','SuperAdministrador'])): ?>
    <a title="estaciones" href="estaciones.php"><img src="images/estaciones.webp" alt="estaciones"></a>
    <a title="seguridad" href="ips_bloqueadas.php"><img src="images/secury.webp" alt="seguridad"></a>
    <a title="usuarios" href="usuarios.php"><img src="images/usuarios.webp" alt="usuarios"></a>
<?php endif; ?>
<a title="imprimir" href="imprimir.php"><img src="images/imprimir.webp" alt="imprimir"></a>
<a title="logout" href="logout.php"><img src="images/logout.webp" alt="logout"></a>
</div>

<br><br><br>

<?php if(isset($error)): ?>
<p class="rojo_intenso"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<h2>Añadir Usuario</h2>
<div style="display:flex; gap:20px; align-items:flex-start;">
<form method="POST" style="flex:1;">
<label>Usuario:</label><input type="text" name="usuario" required><br>

<label>Contraseña:</label>
<div style="position:relative; display:inline-block;">
<input type="password" id="contraseña" name="contraseña" required style="padding-right:35px;">
<svg id="togglePass1" class="eye-icon" onclick="togglePassword('contraseña','togglePass1')" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
<path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12zm11 4a4 4 0 1 0 0-8 4 4 0 0 0 0 8z"/>
<circle cx="12" cy="12" r="2"/>
</svg>
</div><br>

<label>Confirmar Contraseña:</label>
<div style="position:relative; display:inline-block;">
<input type="password" id="confirmar_contraseña" name="confirmar_contraseña" required style="padding-right:35px;">
<svg id="togglePass2" class="eye-icon" onclick="togglePassword('confirmar_contraseña','togglePass2')" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
<path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12zm11 4a4 4 0 1 0 0-8 4 4 0 0 0 0 8z"/>
<circle cx="12" cy="12" r="2"/>
</svg>
</div><br>

<label>Tipo:</label>
<select name="tipo">
<option value="Usuario">Usuario</option>
<option value="Colaborador">Colaborador</option>
<option value="Administrador">Administrador</option>
<?php if($is_superadmin): ?>
<option value="SuperAdministrador">SuperAdministrador</option>
<?php endif; ?>
</select><br>

<?php if($_SESSION['tipo']==='Administrador'): ?>
<input type="hidden" name="flota" value="<?= htmlspecialchars($flota_usuario) ?>">
<strong>Flota:</strong> <?= htmlspecialchars($flota_usuario) ?><br>
<?php else: ?>
Flota:<input type="text" name="flota" <?= $is_superadmin ? '' : 'required' ?> style="text-transform:uppercase;"><br>
<?php endif; ?>

<input type="submit" value="Añadir Usuario">
</form>

<div class="info-usuarios">
<strong>Tipos de usuario:</strong><br><br>
<strong>Usuario</strong> - Puede consultar e imprimir.<br>
<strong>Colaborador</strong> - Todo lo anterior + añadir citas, vehículos y modificar estados/caducidades.<br>
<strong>Administrador</strong> - Todo lo anterior + gestionar estaciones, ver IPs bloqueadas, añadir/modificar/desbloquear/eliminar usuarios.<br>
<strong>SuperAdministrador</strong> - Todo lo anterior + editar id, matricula y flota vehiculo + desbloquear IPs bloqueadas.<br>
  <br>El <strong>SuperAdministrador</strong> es el unico que puede interactuar con todas las flotas. los demas usuarios includos los <strong>Administrador</strong>es unicamente podran interactuar con su flota asignada no pudiento interferir en las demás.
</div>
</div>

<br><br>
<h2>Lista de Usuarios</h2>
<table>
<thead>
<tr>
<th>Usuario</th>
<th>Flota</th>
<th>Tipo</th>
<th>Estado</th>
<th>Acciones</th>
</tr>
</thead>
<tbody>
<?php foreach ($usuarios as $usuario): ?>
<tr>
<td><?= htmlspecialchars($usuario['usuario']) ?></td>
<td><?= htmlspecialchars($usuario['flota'] ?? '-') ?></td>
<td><?= htmlspecialchars($usuario['tipo']) ?></td>
<td>
  <?php if (!empty($usuario['bloqueado'])): ?>
    <span style="color:#cc0000;font-weight:bold;">🔴 Bloqueado</span>

    <?php if (in_array($_SESSION['tipo'], ['Administrador','SuperAdministrador'])): ?>
      <form action="desbloquear_usuario.php" method="post" style="display:inline; margin-left:5px;">
        <input type="hidden" name="usuario" value="<?= htmlspecialchars($usuario['usuario']) ?>">
        <button type="submit"
                style="padding:2px 6px; cursor:pointer; background-color:#3399ff; color:#fff; font-size:12px; border:none; border-radius:4px;"
                onclick="return confirm('¿Estás seguro de que quieres desbloquear a este usuario?');">
          Desbloquear
        </button>
      </form>
    <?php endif; ?>

  <?php else: ?>
    <span style="color:green;">🟢 Activo</span>
  <?php endif; ?>
</td>
<td style="display:flex; gap:5px; flex-wrap:wrap;">
<form action="editar_usuario.php" method="get" style="margin:0;">
<input type="hidden" name="usuario" value="<?= htmlspecialchars($usuario['usuario']) ?>">
<button type="submit" style="padding:4px 8px; cursor:pointer;">Editar</button>
</form>

<form action="eliminar_usuario.php" method="get" style="margin:0;" onsubmit="return confirm('¿Estás seguro de eliminar este usuario?');">
<input type="hidden" name="usuario" value="<?= htmlspecialchars($usuario['usuario']) ?>">
<button type="submit" style="padding:4px 8px; cursor:pointer; background-color:#cc0000; color:#fff;">Eliminar</button>
</form>

<?php if (!empty($usuario['tiene_historico'])): ?>
<form action="usuarios.php" method="get" style="margin:0;">
<input type="hidden" name="ver_historico" value="<?= htmlspecialchars($usuario['usuario']) ?>">
<button type="submit" style="padding:4px 8px; cursor:pointer; background:#b35f00 !important; background-image:none !important; color:#fff !important; font-weight:bold; border:none; border-radius:4px;">Ver Log HISTORICOS</button>
</form>
<?php endif; ?>
  
<?php if (!empty($usuario['tiene_log'])): ?>
<form action="usuarios.php" method="get" style="margin:0;">
<input type="hidden" name="ver_log" value="<?= htmlspecialchars($usuario['usuario']) ?>">
<button type="submit" style="padding:4px 8px; cursor:pointer; background:#cc3333 !important; background-image:none !important;color:#fff !important; font-weight:bold; border:none; border-radius:4px;">Ver Log FAILS</button>
</form>
<?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<br><br><br>
  
<?php if ($is_superadmin): ?>
<p><strong>Tamaño log:</strong> <?= $tamano_log_formateado ?></p>
<h3>Borrar logs HISTORICOS antiguos</h3>
<form method="post" style="margin-bottom:20px;">
    <label>Conservar últimos 
        <input type="number" name="conservar_anios" value="4" min="1" style="width:60px;"> años
    </label>
    <button type="submit" name="borrar_logs_anteriores" 
            style="padding:4px 8px; margin-left:10px; background:#cc0000; color:#fff; border:none; border-radius:4px; cursor:pointer;">
        Aceptar
    </button>
</form>
<?php endif; ?>

<br><br><br>
<br><br><br>


<h4 class="small version-title" style="margin-top:12px; text-align:left;"><?= htmlspecialchars($version_text) ?></h4>
<p class="small version-author" style="text-align:left;"><?= htmlspecialchars($autor_text) ?></p>

  <?php if ($log_modal_historico !== null): ?>
<div id="logModalHistorico" style="
position:fixed;
top:50%;
left:50%;
transform:translate(-50%,-50%);
width:70%;
max-height:70%;
overflow:auto;
background:inherit;
color:inherit;
border:2px solid currentColor;
padding:20px;
z-index:9999;
box-shadow:0 0 20px rgba(0,0,0,0.5);
">
<h3>Log histórico (<?= $total_historico ?>)</h3>

<pre style="font-size:12px; white-space:pre-wrap; word-wrap:break-word;">
<?php
if (empty($log_modal_historico)) {
    echo "No hay registros históricos para este usuario.";
} else {
    foreach ($log_modal_historico as $linea) {
        $linea_html = htmlspecialchars($linea);
        if (stripos($linea, 'ok') !== false) {
            echo "<span style='color:green;'>$linea_html</span>\n";
        } elseif (stripos($linea, 'error') !== false) {
            echo "<span style='color:red;'>$linea_html</span>\n";
        } else {
            echo $linea_html . "\n";
        }
    }
}
?>
</pre>

<div style="margin-top:10px; display:flex; gap:10px; justify-content:flex-end;">
    <button onclick="document.getElementById('logModalHistorico').style.display='none'"
            style="padding:4px 8px; border:1px solid #777; border-radius:4px; cursor:pointer;">
        Cerrar
    </button>
</div>
</div>
<?php endif; ?>
  
<?php if ($log_modal !== null): ?>
<div id="logModal" style="
position:fixed;
top:50%;
left:50%;
transform:translate(-50%,-50%);
width:70%;
max-height:70%;
overflow:auto;
background:inherit;
color:inherit;
border:2px solid currentColor;
padding:20px;
z-index:9999;
box-shadow:0 0 20px rgba(0,0,0,0.5);
">
<h3>Intentos fallidos (<?= $total_intentos ?>)</h3>

<pre style="font-size:12px; white-space:pre-wrap; word-wrap:break-word;">
<?php
if (empty($log_modal)) {
    echo "No hay intentos registrados.";
} else {
    foreach ($log_modal as $linea) {
        echo htmlspecialchars($linea) . "\n";
    }
}
?>
</pre>

<div style="margin-top:10px; display:flex; gap:10px; justify-content:flex-end;">
    <a href="usuarios.php?limpiar_log=<?= urlencode($_GET['ver_log']) ?>"
       onclick="return confirm('¿Eliminar todos los registros de este usuario?')"
       style="color:#cc0000;font-weight:bold; padding:4px 8px; border:1px solid #cc0000; border-radius:4px;">
       Limpiar registros
    </a>

    <button onclick="document.getElementById('logModal').style.display='none'"
            style="padding:4px 8px; border:1px solid #777; border-radius:4px; cursor:pointer;">
        Cerrar
    </button>
</div>
</div>
<?php endif; ?>

<script>
function togglePassword(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById(iconId);
    if(input.type==='password'){ input.type='text'; } else { input.type='password'; }
}

function actualizarFechaHora(){
    const d = new Date();
    document.getElementById('fecha-hora').innerText = d.toLocaleDateString('es-ES')+' '+d.toLocaleTimeString('es-ES');
}
actualizarFechaHora();
setInterval(actualizarFechaHora,1000);
</script>
</body>
</html>