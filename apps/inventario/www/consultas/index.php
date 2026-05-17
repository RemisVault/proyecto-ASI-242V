<?php
require_once '/var/www/privado/session.safe.php';
$user = $_SESSION["user"];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Consultas - Inventario</title>
</head>
<body>

<div style="float: right; text-align: right; margin-right: 15px;">
    <strong><?php echo htmlspecialchars($user); ?></strong>
</div>

<div style="clear: both;"></div>

<center>

    <h1>Menú de Consultas</h1>

    <hr width="100%" style="margin: 20px 0;">

    <h3>Consultas disponibles</h3>

    <p>
        <a href="select_equipos_detalle.html">
            Consultas multitabla
        </a>
    </p>

    <p>
        <a href="equipos_por_red.html">
            Cambiar red de un equipo
        </a>
    </p>

    <hr width="100%" style="margin: 25px 0;">

    <p><a href="../menu.php">Volver al Menú Principal</a></p>

    <br>

    <!-- ✔ BOTÓN SALIR (igual que en el resto del sistema) -->
    <form action="../logout.php" method="POST">
        <button type="submit">Salir</button>
    </form>

</center>

</body>
</html>
