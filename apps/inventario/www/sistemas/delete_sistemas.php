<?php

require_once '/var/www/privado/session.safe.php';
require_once '/var/www/privado/db.connect.oracle.php';

if (!$conn) {
    die("Error de comunicación con el sistema central.");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: delete_sistemas.html");
    exit;
}

$nombre = strtoupper(limpiar($_POST["nombre"] ?? ''));
$version = strtoupper(limpiar($_POST["version"] ?? ''));

echo "<center>";

// ==========================
// VALIDACIÓN
// ==========================
if (!preg_match('/^[A-Z0-9\s.\-_]+$/', $nombre)) {
    echo "<h2>Error de Validación</h2>";
    echo "<p style='color:red;'>Nombre no válido</p>";
    echo "<p><a href='delete_sistemas.html'><button type='button'>Volver</button></a></p>";
    echo "</center>";
    exit;
}

if (!preg_match('/^[A-Z0-9\s.\-_]+$/', $version)) {
    echo "<h2>Error de Validación</h2>";
    echo "<p style='color:red;'>Versión no válida</p>";
    echo "<p><a href='delete_sistemas.html'><button type='button'>Volver</button></a></p>";
    echo "</center>";
    exit;
}

// ==========================
// BUSCAR ID
// ==========================
$sql = "SELECT ID_SO FROM SISTEMAS_OPERATIVOS
        WHERE NOMBRE = :nombre AND VERSION = :version";

$stmt = oci_parse($conn, $sql);

oci_bind_by_name($stmt, ":nombre", $nombre);
oci_bind_by_name($stmt, ":version", $version);

oci_execute($stmt);

$row = oci_fetch_assoc($stmt);
oci_free_statement($stmt);

if (!$row) {
    echo "<h2>No encontrado</h2>";
    echo "<p style='color:orange;'>$nombre $version</p>";
    echo "<p><a href='delete_sistemas.html'><button type='button'>Volver</button></a></p>";
    echo "</center>";
    oci_close($conn);
    exit;
}

$id_so = $row['ID_SO'];

// ==========================
// DELETE
// ==========================
$sql = "DELETE FROM SISTEMAS_OPERATIVOS WHERE ID_SO = :id_so";

$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ":id_so", $id_so);

$result = oci_execute($stmt, OCI_COMMIT_ON_SUCCESS);

if (!$result) {
    echo "<h2>Error al eliminar</h2>";
    echo "<p style='color:red;'>No se pudo completar la operación debido a un conflicto de integridad en el sistema.</p>";
    echo "<p><a href='delete_sistemas.html'><button type='button'>Volver</button></a></p>";
    echo "</center>";
    oci_free_statement($stmt);
    oci_close($conn);
    exit;
}

oci_free_statement($stmt);
oci_close($conn);

// ==========================
// RESULTADO
// ==========================
echo "<h2>Eliminado correctamente</h2>";
echo "<p>$nombre $version</p>";

echo "<br><p><a href='delete_sistemas.html'><button type='button'>Volver</button></a></p>";

echo "</center>";

?>