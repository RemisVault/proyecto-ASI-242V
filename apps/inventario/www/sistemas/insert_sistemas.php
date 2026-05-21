<?php

require_once '/var/www/privado/session.safe.php';
require_once '/var/www/privado/db.connect.oracle.php';

if (!$conn) {
    die("Error de comunicación con el sistema central.");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: insert_sistemas.html");
    exit;
}

$nombre = strtoupper(limpiar($_POST["nombre"] ?? ''));
$version = strtoupper(limpiar($_POST["version"] ?? ''));

echo "<center>";

// =========================================================
// VALIDACIÓN
// =========================================================
if (!preg_match('/^[A-Z0-9\s.\-_]+$/', $nombre)) {
    echo "<h2>Error de Validación</h2>";
    echo "<p style='color:red;'>Nombre de sistema no válido</p>";
    echo "<p><a href='insert_sistemas.html'><button type='button'>Volver</button></a></p>";
    echo "</center>";
    oci_close($conn);
    exit;
}

if ($version !== '' && !preg_match('/^[A-Z0-9\s.\-_]*$/', $version)) {
    echo "<h2>Error de Validación</h2>";
    echo "<p style='color:red;'>Versión no válida</p>";
    echo "<p><a href='insert_sistemas.html'><button type='button'>Volver</button></a></p>";
    echo "</center>";
    oci_close($conn);
    exit;
}

// =========================================================
// INSERT
// =========================================================
$sql = "INSERT INTO SISTEMAS_OPERATIVOS (NOMBRE, VERSION)
        VALUES (:nombre, :version)";

$stmt = oci_parse($conn, $sql);

oci_bind_by_name($stmt, ":nombre", $nombre);
oci_bind_by_name($stmt, ":version", $version);

$result = oci_execute($stmt, OCI_COMMIT_ON_SUCCESS);

if ($result) {
    echo "<h2>Insertado correctamente</h2>";
    echo "<p><b>$nombre</b> $version</p>";
} else {
    echo "<h2>Error</h2>";
    echo "<p style='color:red;'>No se pudo completar la operación debido a un conflicto de integridad en el sistema.</p>";
}

oci_free_statement($stmt);
oci_close($conn);

echo "<br><p><a href='insert_sistemas.html'><button type='button'>Volver</button></a></p>";
echo "</center>";

?>