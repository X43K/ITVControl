<?php
session_start();

/* ================= SEGURIDAD ================= */

// Solo Administrador o SuperAdministrador
if (!isset($_SESSION['usuario']) || !in_array($_SESSION['tipo'], ['Administrador','SuperAdministrador'])) {
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

                /* ================= CONTROL DE FLOTA ================= */

                // Si es Administrador, solo puede desbloquear su flota
                if ($_SESSION['tipo'] === 'Administrador') {

                    $flota_admin = $_SESSION['flota'] ?? '';
                    $flota_usuario = $usuario['flota'] ?? '';

                    if ($flota_admin !== $flota_usuario) {
                        die("No tienes permiso para desbloquear este usuario.");
                    }
                }

                /* ================= DESBLOQUEAR ================= */

                $usuario['bloqueado'] = false;
                $usuario['intentos'] = 0;
                break;
            }
        }

        file_put_contents($usuarios_file, json_encode($usuarios, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}

header('Location: usuarios.php');
exit();
?>