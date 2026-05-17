<?php

require_once '/var/www/privado/session.safe.php';
require_once '/var/www/privado/db.connect.oracle.php';

// =========================================================================
// CONEXIÓN
// =========================================================================
if (!$conn) {
    $e = oci_error();
    die("Error de conexión Oracle: " . $e['message']);
}

// =========================================================================
// INSERT (POST)
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nombre_red      = limpiar($_POST["nombre_red"] ?? '');
    $direccion_red   = limpiar($_POST["direccion_red"] ?? '');
    $gateway         = limpiar($_POST["gateway"] ?? '');
    $interfaz_router = limpiar($_POST["interfaz_router"] ?? '');
    $descripcion     = limpiar($_POST["descripcion"] ?? '');

    echo "<center>";

    // =========================================================================
    // VALIDACIÓN (adaptada a tus datos reales)
    // =========================================================================
    if (
        !preg_match('/^[A-Z0-9\s_-]+$/', $nombre_red) ||
        !preg_match('/^(\d{1,3}\.){3}\d{1,3}\/([0-9]|[12][0-9]|3[0-2])$/', $direccion_red) ||
        !preg_match('/^(\d{1,3}\.){3}\d{1,3}$/', $gateway) ||
        !preg_match('/^[a-zA-Z]+[0-9]*$/', $interfaz_router)
    ) {
        echo "<h2>Error de Validación</h2>";
        echo "<p style='color:red;'>Datos de red no válidos.</p>";
        echo "<p><a href='insert_redes.html'><button>Volver</button></a></p>";
        echo "</center>";

        oci_close($conn);
        exit;
    }

    // =========================================================================
    // INSERT SQL
    // =========================================================================
    $query = "INSERT INTO REDES (
                NOMBRE_RED,
                DIRECCION_RED,
                GATEWAY,
                INTERFAZ_ROUTER,
                DESCRIPCION
              ) VALUES (
                :nombre_red,
                :direccion_red,
                :gateway,
                :interfaz_router,
                :descripcion
              )";

    $stmt = oci_parse($conn, $query);

    oci_bind_by_name($stmt, ":nombre_red", $nombre_red);
    oci_bind_by_name($stmt, ":direccion_red", $direccion_red);
    oci_bind_by_name($stmt, ":gateway", $gateway);
    oci_bind_by_name($stmt, ":interfaz_router", $interfaz_router);
    oci_bind_by_name($stmt, ":descripcion", $descripcion);

    $result = oci_execute($stmt, OCI_COMMIT_ON_SUCCESS);

    if ($result) {
        echo "<h2>Red insertada correctamente</h2>";
        echo "<p style='color:green;'>Se ha añadido la red: <b>$nombre_red</b></p>";
    } else {
        $e = oci_error($stmt);
        echo "<h2>Error al insertar</h2>";
        echo "<p style='color:red;'>" . htmlspecialchars($e['message']) . "</p>";
    }

    oci_free_statement($stmt);
    oci_close($conn);

    echo "<br><p><a href='insert_redes.html'><button>Volver</button></a></p>";
    echo "</center>";

    exit;
}

// =========================================================================
// ACCESO DIRECTO
// =========================================================================
header("Location: insert_redes.html");
exit;

?>
