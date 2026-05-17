<?php
require_once '/var/www/privado/session.safe.php';
$user = $_SESSION["user"];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Redes - Inventario</title>
</head>
<body>

    <div style="float: right; text-align: right; margin-right: 15px;">
        <strong><?php echo htmlspecialchars($user); ?></strong>
    </div>

    <div style="clear: both;"></div>

    <center>
        <h1>Gestión de Redes</h1>

        <hr width="100%" style="margin: 20px 0; border: 0; border-top: 1px solid #ccc;">

        <h3>Operaciones Disponibles</h3>

        <p><a href="select_redes.html">Consultar Redes</a></p>
        <p><a href="insert_redes.html">Dar de Alta Red</a></p>
        <p><a href="update_redes.html">Modificar Red</a></p>
        <p><a href="delete_redes.html">Dar de Baja Red</a></p>

        <hr width="100%" style="margin: 25px 0; border: 0; border-top: 1px solid #ccc;">

        <p><a href="../menu.php">Volver al Menú Principal</a></p>

        <br>

        <form action="../logout.php" method="POST">
            <button type="submit">Salir</button>
        </form>
    </center>

</body>
</html>
