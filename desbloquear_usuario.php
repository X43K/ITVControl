<?php
session_start();

// Solo SuperAdministradores pueden desbloquear
if (!isset($_SESSION['usuario']) || $_SESSION['tipo'] !== 'SuperAdministrador') {
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['usuario'])) {
    $usuario_a_desbloquear = $_POST['usuario'];
    $usuarios_file = 'usuarios.json';

    if (file_exists($usuarios_file)) {
        $usuarios = json_decode(file_get_contents($usuarios_file), true);
        if (!is_array($usuarios)) $usuarios = [];

        foreach ($usuarios as &$usuario) {
            if ($usuario['usuario'] === $usuario_a_desbloquear) {
                // Desbloquear y resetear intentos
                $usuario['bloqueado'] = false;
                $usuario['intentos'] = 0;
                break;
            }
        }

        file_put_contents($usuarios_file, json_encode($usuarios, JSON_PRETTY_PRINT));
    }
}

// Redirigir de vuelta a la lista de usuarios
header('Location: usuarios.php');
exit();
?>
