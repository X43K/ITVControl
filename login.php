<?php
session_start();

$usuarios_file = 'usuarios.json';

// Si ya hay sesión válida, redirigir
if (isset($_SESSION['usuario'])) {
    header('Location: index.php');
    exit;
}

// Comprobar archivo de usuarios
if (!file_exists($usuarios_file)) {
    die("El archivo de usuarios no existe.");
}

$usuarios = json_decode(file_get_contents($usuarios_file), true);

// Si el navegador NO ha enviado credenciales → pedirlas
if (!isset($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'])) {
    header('WWW-Authenticate: Basic realm="ITVControl"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'Autenticación requerida';
    exit;
}

$usuario_input     = $_SERVER['PHP_AUTH_USER'];
$contraseña_input  = $_SERVER['PHP_AUTH_PW'];

$usuario_encontrado = false;

// Validar usuario
foreach ($usuarios as $usuario) {
    if ($usuario['usuario'] === $usuario_input) {
        $usuario_encontrado = true;

        if (password_verify($contraseña_input, $usuario['contraseña'])) {
            // Login correcto → crear sesión
            $_SESSION['usuario'] = $usuario['usuario'];
            $_SESSION['tipo']    = $usuario['tipo'];

            header('Location: index.php');
            exit;
        }
        break;
    }
}

// Si llega aquí → credenciales incorrectas
header('WWW-Authenticate: Basic realm="ITVControl"');
header('HTTP/1.0 401 Unauthorized');
echo 'Usuario o contraseña incorrectos';
exit;
