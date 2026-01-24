<?php
// Iniciar sesión
session_start();

// Destruir toda la sesión
session_unset();
session_destroy();

// Redirigir al login
header('Location: login.php');
exit();
?>
