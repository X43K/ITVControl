<?php
session_start();

// Administrador y SuperAdministrador
if (!isset($_SESSION['usuario']) || !in_array($_SESSION['tipo'], ['Administrador','SuperAdministrador'])) {
    header('Location: login.php');
    exit();
}

$is_admin = in_array($_SESSION['tipo'], ['Administrador','SuperAdministrador']);
$is_superadmin = $_SESSION['tipo'] === 'SuperAdministrador';
$usuario_flota = $_SESSION['flota'] ?? '';

// ===========================
// CARGAR USUARIOS Y FLOTA
// ===========================
$usuarios_file = 'usuarios.json';
$usuarios = [];
if (file_exists($usuarios_file)) {
    $usuarios = json_decode(file_get_contents($usuarios_file), true);
}

// Crear lista de flotas únicas
$flotas_disponibles = [];
foreach ($usuarios as $u) {
    if (!empty($u['flota'])) $flotas_disponibles[] = $u['flota'];
}
$flotas_disponibles = array_values(array_unique($flotas_disponibles));

// ===========================
// CARGAR ESTACIONES
// ===========================
$estaciones_file = 'estaciones.json';
if (!file_exists($estaciones_file)) {
    file_put_contents($estaciones_file, json_encode([
        ["nombre"=>"Tambre","flotas"=>["TRALUSA"]],
        ["nombre"=>"Sionlla","flotas"=>["TRALUSA"]],
        ["nombre"=>"Cacheiras","flotas"=>["TRALUSA"]]
    ], JSON_PRETTY_PRINT));
}

$estaciones = json_decode(file_get_contents($estaciones_file), true);
if (!is_array($estaciones)) $estaciones = [];

// ===========================
// AGREGAR NUEVA ESTACIÓN
// ===========================
if (isset($_POST['nueva_estacion']) && trim($_POST['nueva_estacion']) !== '') {
    $nueva = trim($_POST['nueva_estacion']);
    $asignadas = [];

    if ($is_superadmin) {
        $asignadas = $_POST['flotas'] ?? [];
        $asignadas = array_values(array_intersect($asignadas,$flotas_disponibles));
    } else {
        $asignadas = [$usuario_flota];
    }

    // Verificar si ya existe
    $existe = false;
    foreach ($estaciones as &$e) {
        if (strcasecmp($e['nombre'],$nueva)===0) {
            foreach ($asignadas as $f) {
                if (!in_array($f,$e['flotas'] ?? [])) $e['flotas'][]=$f;
            }
            $existe = true;
            break;
        }
    }
    if (!$existe) {
        $estaciones[] = ["nombre"=>$nueva,"flotas"=>$asignadas];
    }

    file_put_contents($estaciones_file,json_encode($estaciones,JSON_PRETTY_PRINT));
    $mensaje = "Estación '$nueva' agregada correctamente.";
}

// ===========================
// EDITAR ESTACIONES
// ===========================
if (isset($_POST['editar_estaciones']) && isset($_POST['estaciones']) && is_array($_POST['estaciones'])) {
    foreach ($_POST['estaciones'] as $i => $nombre) {
        $nombre = trim($nombre);
        if ($nombre === '') continue;

        $flotas_editar = [];
        if ($is_superadmin) {
            $flotas_editar = $_POST['flotas'][$i] ?? [];
            $flotas_editar = array_values(array_intersect($flotas_editar,$flotas_disponibles));
        } else {
            $flotas_editar = [$usuario_flota];
        }

        // Evitar duplicados en otras estaciones
        $duplicado = false;
        foreach ($estaciones as $j=>$e) {
            if ($j!=$i && strcasecmp($e['nombre'],$nombre)===0) {
                foreach ($flotas_editar as $f) {
                    if (!in_array($f,$e['flotas'] ?? [])) $e['flotas'][]=$f;
                }
                $duplicado = true;
                break;
            }
        }
        if (!$duplicado) {
            $estaciones[$i]['nombre'] = $nombre;
            $estaciones[$i]['flotas'] = $flotas_editar;
        }
    }
    file_put_contents($estaciones_file,json_encode($estaciones,JSON_PRETTY_PRINT));
    $mensaje = "Estaciones actualizadas correctamente.";
}

// ===========================
// "ELIMINAR"/DESASIGNAR ESTACIÓN
// ===========================
if (isset($_GET['eliminar'])) {
    $index = (int)$_GET['eliminar'];
    if (isset($estaciones[$index])) {
        if ($is_superadmin) {
            unset($estaciones[$index]); // SuperAdmin elimina totalmente
        } else {
            // Desasignar flota del administrador
            $estaciones[$index]['flotas'] = array_values(array_filter($estaciones[$index]['flotas'], function($f) use($usuario_flota){
                return strcasecmp($f,$usuario_flota)!==0;
            }));
        }
        $estaciones = array_values($estaciones); // Reindexar
        file_put_contents($estaciones_file,json_encode($estaciones,JSON_PRETTY_PRINT));
        $mensaje = "Estación actualizada correctamente.";
    } else {
        $error = "Estación no encontrada.";
    }
}

// ===========================
// FILTRAR ESTACIONES PARA ADMIN
// ===========================
if (!$is_superadmin && $usuario_flota!=='') {
    $estaciones = array_filter($estaciones,function($e) use($usuario_flota){
        return in_array($usuario_flota,$e['flotas'] ?? []);
    });
}

// ===========================
// VERSION
// ===========================
$version_file='version.xk';
$version_text=''; $autor_text='';
if(file_exists($version_file)){
    $lines = file($version_file,FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $version_text=$lines[0] ?? '';
    $autor_text=$lines[1] ?? '';
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
/* ===== BASE Y TEXTO ===== */
body {margin:15px;font-family:Arial,sans-serif;color:#000;background:#fff;}
h1,h2,h3,h4,h5,h6,p,strong,span,label {color:#000;}
input, select, textarea {padding:5px;margin:2px 0;border-radius:4px;background:#fff;color:#000;border:1px solid #ccc;}
input[type=submit]{cursor:pointer;}
.verde{color:#4CAF50;} .rojo_intenso{color:#f33;}
table{border-collapse:collapse;width:100%;}
th,td{border:1px solid #ccc;padding:8px;vertical-align:top;}
th{background:#eee;color:#000;}
.user-info{position:fixed;top:10px;right:15px;text-align:right;font-size:14px;background:rgba(255,255,255,0.6);padding:5px 10px;border-radius:8px;}
.user-info strong{display:block;}
.user-info small{color:#4a90e2;font-weight:bold;}
.menu a img{width:80px;vertical-align:middle;}

/* MODO OSCURO */
@media(prefers-color-scheme: dark){
    body{background:#000;color:#ddd;}
    h1,h2,h3,h4,h5,h6,p,strong,span,label{color:#ddd;}
    th{background:#1c75bc;color:#fff;}
    td{color:#ddd;border-color:#555;}
    input, select, textarea{background:#111;color:#fff;border:1px solid #555;}
    input[type=submit]{background:#222;color:#fff;border:1px solid #666;}
    .menu img:not([alt="Logo"]){filter: invert(1) hue-rotate(180deg);}
    h1 img{filter:none;}
    .user-info{background:rgba(0,0,0,0.5);}
    .user-info small{color:#3399ff;}
}
</style>
</head>
<body>

<div class="user-info">
<strong><?=htmlspecialchars($_SESSION['usuario'])?> | <?=htmlspecialchars($_SESSION['tipo'])?></strong>
<small><?= $is_superadmin ? "Todas las flotas" : ($usuario_flota ? strtoupper($usuario_flota) : "Sin flota asignada") ?></small>
<div id="fecha-hora"></div>
</div>

<h1><img src="images/logo.webp" width="30" style="vertical-align: middle;"> Gestionar Estaciones</h1>
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

<?php if(isset($mensaje)): ?><p class="verde"><?=htmlspecialchars($mensaje)?></p><?php endif; ?>
<?php if(isset($error)): ?><p class="rojo_intenso"><?=htmlspecialchars($error)?></p><?php endif; ?>

<h2>Agregar Nueva Estación</h2>
<form method="POST">
    <input type="text" name="nueva_estacion" placeholder="Nombre de la estación" required>
    <?php if($is_superadmin): ?>
        <br><label>Asignar a flotas:</label><br>
        <?php foreach($flotas_disponibles as $f): ?>
            <label style="margin-right:10px;">
                <input type="checkbox" name="flotas[]" value="<?=htmlspecialchars($f)?>">
                <?=htmlspecialchars($f)?>
            </label>
        <?php endforeach; ?>
    <?php endif; ?>
    <br><input type="submit" value="Agregar">
</form>
<br>

<h2>Editar Estaciones</h2>
<form method="POST">
<table>
<thead><tr><th>Nombre</th><th>Flotas</th><th>Acción</th></tr></thead>
<tbody>
<?php foreach($estaciones as $i=>$e): ?>
<tr>
    <td><input type="text" name="estaciones[<?=$i?>]" value="<?=htmlspecialchars($e['nombre'])?>" required></td>
    <td>
        <?php if($is_superadmin): ?>
            <?php foreach($flotas_disponibles as $f): ?>
                <label style="margin-right:10px;">
                    <input type="checkbox" name="flotas[<?=$i?>][]" value="<?=htmlspecialchars($f)?>"
                        <?=in_array($f,$e['flotas'] ?? [])?'checked':''?>>
                    <?=htmlspecialchars($f)?>
                </label>
            <?php endforeach; ?>
        <?php else: ?>
            <?=htmlspecialchars(implode(', ', $e['flotas'] ?? []))?>
        <?php endif; ?>
    </td>
    <td>
<button 
    style="padding:4px 8px; cursor:pointer; background:#cc0000 !important; color:#fff; border-radius:4px;"
    onclick="if(confirm('¿Seguro que quieres eliminar/desasignar esta estación?')){ 
        window.location.href='?eliminar=<?=$i?>'; 
    }">
    <?= $is_superadmin ? 'Eliminar' : 'Desasignar' ?>
</button>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<input type="submit" name="editar_estaciones" value="Guardar Cambios">
</form>

<h4 class="small" style="margin-top:12px;"><?=htmlspecialchars($version_text)?></h4>
<p class="small"><?=htmlspecialchars($autor_text)?></p>

<script>
function actualizarFechaHora(){
    const d=new Date();
    document.getElementById('fecha-hora').innerText=d.toLocaleDateString('es-ES')+' '+d.toLocaleTimeString('es-ES');
}
actualizarFechaHora();
setInterval(actualizarFechaHora,1000);
</script>
</body>
</html>