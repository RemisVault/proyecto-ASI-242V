<?php
require_once '/var/www/privado/session.safe.php';

if (!isset($_SESSION["auth"]) || $_SESSION["auth"] !== true) {
    header("Location: index.php");
    exit;
}

$user = limpiar($_SESSION["user"]);

// =============================
// EXPORTACIÓN GLOBAL
// =============================
require_once '/var/www/privado/db.connect.oracle.php';

if (isset($_GET['export']) && in_array($_GET['export'], ['json', 'yaml'])) {

    function fetch_table($conn, $sql) {
        $stmt = oci_parse($conn, $sql);
        oci_execute($stmt);

        $data = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $data[] = $row;
        }

        oci_free_statement($stmt);
        return $data;
    }

    $dump = [];

    $dump['PARAMETROS_BASICOS']    = fetch_table($conn, "SELECT * FROM PARAMETROS_BASICOS");
    $dump['REDES']                 = fetch_table($conn, "SELECT * FROM REDES");
    $dump['SISTEMAS_OPERATIVOS']   = fetch_table($conn, "SELECT * FROM SISTEMAS_OPERATIVOS");
    $dump['SERVICIOS']             = fetch_table($conn, "SELECT * FROM SERVICIOS");
    $dump['EQUIPOS']               = fetch_table($conn, "SELECT * FROM EQUIPOS");
    $dump['EQUIPO_HARDWARE']       = fetch_table($conn, "SELECT * FROM EQUIPO_HARDWARE");
    $dump['USUARIOS_CREDENCIALES'] = fetch_table($conn, "SELECT * FROM USUARIOS_CREDENCIALES");
    $dump['EQUIPO_SERVICIO']       = fetch_table($conn, "SELECT * FROM EQUIPO_SERVICIO");

    if (ob_get_level()) ob_end_clean();

    $format = $_GET['export'];

    if ($format === 'json') {

        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="inventario.json"');

        echo json_encode($dump, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        oci_close($conn);
        exit;
    }

    header('Content-Type: text/yaml; charset=utf-8');
    header('Content-Disposition: attachment; filename="inventario.yaml"');

    echo "---\n";

    foreach ($dump as $tabla => $filas) {

        echo $tabla . ":\n";

        foreach ($filas as $fila) {
            echo "  -\n";
            foreach ($fila as $k => $v) {
                $v = $v ?? "-";
                echo "      $k: \"" . $v . "\"\n";
            }
        }

        echo "\n";
    }

    oci_close($conn);
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Menú Principal - Inventario</title>
</head>
<body>

<div style="float: left; text-align: left; margin-left: 15px; margin-top: 15px;">

    <a href="?export=json" style="text-decoration: none;">
        <button type="button" style="margin-right: 5px; cursor: pointer;">Exportar JSON</button>
    </a>

    <a href="?export=yaml" style="text-decoration: none;">
        <button type="button" style="cursor: pointer;">Exportar YAML</button>
    </a>

</div>

<div style="clear: both;"></div>

<div style="float: right; text-align: right; margin-right: 15px;">
    <strong><?php echo htmlspecialchars($user); ?></strong>
</div>

<div style="clear: both;"></div>

<center>

    <h1>Bienvenido al Sistema de Inventario</h1>

    <hr width="50%" style="margin: 25px 0;">

    <h3>Tablas Maestras</h3>
    <p><a href="equipos/index.php">Gestion de Equipos</a></p>
    <p><a href="redes/index.php">Gestion de Redes</a></p>
    <p><a href="sistemas/index.php">Sistemas Operativos</a></p>

    <hr width="30%" style="margin: 20px 0;">

    <h3>Analisis y Extraccion</h3>
    <p><a href="consultas/index.php">Consultas Avanzadas e Informes</a></p>

    <hr width="50%" style="margin: 25px 0;">

    <form action="logout.php" method="POST">
        <button type="submit">Salir</button>
    </form>

</center>

</body>
</html>
