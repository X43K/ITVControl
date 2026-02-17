<?php
// Detectar IP real
function getRealIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    } else {
        return $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
    }
}

$ip = getRealIP();
$bloqueadasFile = __DIR__ . "/ips_permanentemente_bloqueadas.log";

// Comprobar si la IP está bloqueada
$ipsBloqueadas = file_exists($bloqueadasFile) ? file($bloqueadasFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];

if (in_array($ip, $ipsBloqueadas)) {
    // Redirigir a la página de acceso bloqueado
    header("Location: /itv/acceso-bloqueado.php");
    exit;
}
?>
