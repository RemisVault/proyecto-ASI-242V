<?php
require_once __DIR__ . '/../privado/session.safe.php';
require_once __DIR__ . '/../privado/db.connect.oracle.php';

if (!isset($conn) || !$conn) {
    $conn = oci_connect($ora_user, $ora_pass, $ora_db, 'AL32UTF8');
}

if (!$conn) {
    $e = oci_error();
    die("Error de conexión Oracle: " . $e['message']);
}

$stmt = oci_parse($conn, "SELECT PARAMETRO, VALOR FROM PARAMETROS_BASICOS");

if (!$stmt) {
    $e = oci_error($conn);
    die("Error en oci_parse: " . $e['message']);
}

$r = oci_execute($stmt);

if (!$r) {
    $e = oci_error($stmt);
    die("Error en oci_execute: " . $e['message']);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login Inventario</title>
</head>
<body>
<center>

    <div style="margin-top: 20px; margin-bottom: -30px;">
        <img src="images/inventario.png" alt="Inventario" width="150">
    </div>

    <h2>Acceso al Sistema de Inventario</h2>

    <form action="auth.php" method="POST">
        <p style="margin: 10px 0;">
            <label>Usuario:</label><br>
            <input type="text" name="user" required>
        </p>
        <p style="margin: 10px 0;">
            <label>Contraseña:</label><br>
            <input type="password" name="password" required>
        </p>
        <p style="margin-top: 15px;">
            <button type="submit">Entrar</button>
        </p>
    </form>

    <hr width="50%" style="margin: 25px 0;">

    <table border="1" cellpadding="5" cellspacing="0">
        <tr>
            <th>PARAMETRO</th>
            <th>VALOR</th>
        </tr>
        <?php
        while ($row = oci_fetch_array($stmt, OCI_ASSOC + OCI_RETURN_NULLS)) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['PARAMETRO']) . "</td>";
            echo "<td>" . htmlspecialchars($row['VALOR']) . "</td>";
            echo "</tr>";
        }

        if ($stmt) { oci_free_statement($stmt); }
        if ($conn) { oci_close($conn); }
        ?>
    </table>

</center>
</body>
</html>
