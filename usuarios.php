<?php
session_start();

// Verificar si el usuario está logueado y tiene permisos
if (!isset($_SESSION['usuario']) || !in_array($_SESSION['tipo'], ['SuperAdministrador'])) {
    header('Location: index.php');
    exit();
}

// Variables para menú
$is_admin = isset($_SESSION['tipo']) && in_array($_SESSION['tipo'], ['Administrador', 'SuperAdministrador']);
$is_superadmin = isset($_SESSION['tipo']) && $_SESSION['tipo'] === 'SuperAdministrador';

// Cargar usuarios
$usuarios_file = 'usuarios.json';
if (!file_exists($usuarios_file)) {
    file_put_contents($usuarios_file, json_encode([])); // Crear archivo vacío si no existe
}

$usuarios = json_decode(file_get_contents($usuarios_file), true);

// Procesar formulario de añadir usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['usuario']) && !empty($_POST['contraseña']) && !empty($_POST['tipo'])) {
        $nuevo_usuario = [
            'usuario' => $_POST['usuario'],
            'contraseña' => password_hash($_POST['contraseña'], PASSWORD_DEFAULT),
            'tipo' => $_POST['tipo']
        ];

        $usuarios[] = $nuevo_usuario;
        file_put_contents($usuarios_file, json_encode($usuarios, JSON_PRETTY_PRINT));

        header('Location: usuarios.php');
        exit();
    } else {
        $error = "Todos los campos son obligatorios.";
    }
}

// Cargar versión y autor desde version.xk
$version_text = 'v.1.4';
$autor_text = 'B174M3 // XaeK';
if (file_exists('version.xk')) {
    $lines = file('version.xk', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $version_text = $lines[0] ?? $version_text;
    $autor_text = $lines[1] ?? $autor_text;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Gestionar Usuarios</title>
<link rel="shortcut icon" href="images/logo.webp">
<link rel="icon" sizes="64x64" href="images/logo.webp">
<link rel="apple-touch-icon" sizes="180x180" href="images/logo.webp">
<link rel="stylesheet" href="style.css">
<style>
body { margin:15px; font-family:Arial,sans-serif; }

/* Menú */
.menu { margin-bottom:15px; }
.menu a { margin-right:5px; }
.menu img { width:80px; height:auto; vertical-align:middle; transition:filter 0.3s ease; }
h1 img { vertical-align:middle; }

/* Formulario */
input, select { padding:5px; margin:2px 0; border-radius:4px; }
input[type="submit"] { cursor:pointer; }

/* Tablas */
table { border-collapse:collapse; width:100%; }
th, td { border:1px solid #ccc; padding:8px; vertical-align:top; }
th { background:#eee; }
a { text-decoration:underline; }

/* Mensajes de error */
.rojo_intenso { color:#cc0000; }

/* ===== MODO OSCURO AUTOMÁTICO ===== */
@media (prefers-color-scheme: dark) {
    body { background:#000; color:#ddd; }
    h1,h2,h3,h4,p,strong { color:#ddd; }
    th { background:#222; color:#fff; }
    input, select { background:#111; color:#fff; border:1px solid #555; }
    input[type="submit"] { background:#222; color:#fff; border:1px solid #666; }
    .menu img:not([alt="Logo"]) { filter: invert(1) hue-rotate(180deg); }
    h1 img { filter:none; }
    .rojo_intenso { color:#f33; }
}
</style>
</head>
<body>
	
</br>
	
<h1>
    <img src="images/logo.webp" alt="Logo" width="30">
    Gestionar Usuarios
</h1>
	
</br>
	
<div class="menu">
    <a href="index.php"><img src="images/index.webp" alt="index"></a>
    <a href="citas.php"><img src="images/citas.webp" alt="citas"></a>
    <a href="vehiculos.php"><img src="images/vehiculos.webp" alt="vehiculos"></a>
    <?php if ($is_admin): ?>
        <a href="estaciones.php"><img src="images/estaciones.webp" alt="estaciones"></a>
    <?php endif; ?>
    <?php if ($is_superadmin): ?>
        <a href="usuarios.php"><img src="images/usuarios.webp" alt="usuarios"></a>
    <?php endif; ?>
    <a href="imprimir.php"><img src="images/imprimir.webp" alt="imprimir"></a>
    <a href="logout.php"><img src="images/logout.webp" alt="logout"></a>
</div>
	
</br>
	

<?php if (isset($error)): ?>
    <p class="rojo_intenso"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<h2>Añadir Usuario</h2>
	
</br>
	
<form method="POST">
    <label>Usuario:</label><input type="text" name="usuario" required><br><br>
    <label>Contraseña:</label><input type="password" name="contraseña" required><br><br>
    <label>Tipo:</label>
    <select name="tipo">
        <option value="Usuario">Usuario</option>
        <option value="Colaborador">Colaborador</option>
        <option value="Administrador">Administrador</option>
        <option value="SuperAdministrador">SuperAdministrador</option>
    </select><br><br>
    <input type="submit" value="Añadir Usuario">
</form>
	
</br>
		
</br>
	
<h2>Lista de Usuarios</h2>
<table>
    <thead>
        <tr>
            <th>Usuario</th>
            <th>Tipo</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($usuarios as $usuario): ?>
            <tr>
                <td><?= htmlspecialchars($usuario['usuario']) ?></td>
                <td><?= htmlspecialchars($usuario['tipo']) ?></td>
                <td>
                    <a href="editar_usuario.php?usuario=<?= urlencode($usuario['usuario']) ?>">Editar</a> |
                    <a href="eliminar_usuario.php?usuario=<?= urlencode($usuario['usuario']) ?>">Eliminar</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<h4 class="small" style="margin-top:12px; text-align:left;"><?= htmlspecialchars($version_text) ?></h4>
<p class="small" style="text-align:left;"><?= htmlspecialchars($autor_text) ?></p>

</body>
</html>
