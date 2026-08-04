<?php
session_name('ITVCONTROL_SESSID');
session_start();
session_unset();
session_destroy();

// Redirigir a login.php
header('Location: login.php');
exit();