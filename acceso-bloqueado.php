<?php
http_response_code(403); // Mantener código 403

// Detectar IP real
$ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';

// Opcional: país usando ip-api.com
$pais = "Desconocido";
$codigoPais = "";
$geo = @json_decode(file_get_contents("http://ip-api.com/json/$ip"));
if ($geo && $geo->status === "success") {
    $pais = $geo->country;
    $codigoPais = strtolower($geo->countryCode);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Acceso Bloqueado</title>
<style>
body{
    font-family: Arial, sans-serif;
    background: linear-gradient(135deg,#2c3e50,#4ca1af);
    height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
    color:white;
    text-align:center;
}
.container{
    background: rgba(0,0,0,0.7);
    padding:40px;
    border-radius:12px;
    max-width:550px;
    width:90%;
}
h1{font-size:60px;color:#ff4c4c;}
.ip-box{
    margin-top:20px;
    padding:12px;
    background:rgba(255,0,0,0.2);
    border-radius:8px;
    font-size:16px;
}
.btn{
    display:inline-block;
    margin-top:25px;
    padding:10px 20px;
    background:#ff4c4c;
    color:white;
    text-decoration:none;
    border-radius:20px;
}
</style>
</head>
<body>

<div class="container">
<img src="images/logo.webp" width="40" class="logo" alt="Logo">
<h1>🚫 Acceso Bloqueado</h1>
<p class="ip-box">
Su dirección IP ha sido bloqueada por seguridad.<br>
IP detectada: <strong><?php echo htmlspecialchars($ip); ?></strong>
<?php if($codigoPais): ?>
<img src="https://flagcdn.com/24x18/<?php echo $codigoPais; ?>.png" alt="Bandera">
<br>País: <?php echo htmlspecialchars($pais); ?>
<?php endif; ?>
</p>

<p>No podrá acceder a esta sección hasta que se desbloquee por un administrador.</p>
</div>

</body>
</html>