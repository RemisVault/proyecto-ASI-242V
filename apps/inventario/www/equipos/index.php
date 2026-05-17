<?php
require_once '/var/www/privado/session.safe.php';
$user = $_SESSION["user"];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Equipos - Inventario</title>
</head>
<body>

    <div style="float: right; text-align: right; margin-right: 15px;">
        <strong><?php echo htmlspecialchars($user); ?></strong>
    </div>

    <div style="clear: both;"></div>

    <center>
        <h1>Gestión de Equipos</h1>

        <hr width="100%" style="margin: 20px 0; border: 0; border-top: 1px solid #ccc;">

        <h3>Operaciones Disponibles</h3>

        <p><a href="select_equipos.html">Consultar Equipos</a></p>
        <p><a href="delete_equipos.html">Dar de Baja Equipo</a></p>

        <hr width="100%" style="margin: 25px 0; border: 0; border-top: 1px solid #ccc;">

        <p><a href="../menu.php">Volver al Menú Principal</a></p>

        <br>

        <form action="../logout.php" method="POST">
            <button type="submit">Salir</button>
        </form>
    </center>

</body>
</html>
