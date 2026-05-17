<?php

require_once '/var/www/privado/session.safe.php';
require_once '/var/www/privado/db.connect.oracle.php';

if (!$conn) {
    $e = oci_error();
    die("Error de conexión Oracle: " . $e['message']);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: delete_equipos.html");
    exit;
}

$hostname = limpiar($_POST["hostname"] ?? '');

echo "<center>";

// =========================================================
// VALIDACIÓN
// =========================================================
if (!preg_match('/^[a-zA-Z0-9.\-_]+$/', $hostname)) {

    echo "<h2>Error de Validación</h2>";
    echo "<p style='color:red;'>Hostname no válido.</p>";
    echo "<p><a href='delete_equipos.html'>Volver</a></p>";
    echo "</center>";

    oci_close($conn);
    exit;
}

// =========================================================
// DELETE
// =========================================================
$query = "DELETE FROM EQUIPOS WHERE HOSTNAME = :hostname";
$stmt = oci_parse($conn, $query);

oci_bind_by_name($stmt, ":hostname", $hostname);

$result = oci_execute($stmt);

if (!$result) {

    $e = oci_error($stmt);

    echo "<h2>Error al eliminar</h2>";
    echo "<pre style='color:red;'>";
    print_r($e);
    echo "</pre>";

    oci_free_statement($stmt);
    oci_close($conn);
    exit;
}

// =========================================================
// COMMIT EXPLÍCITO (MÁS CONTROLADO)
// =========================================================
oci_commit($conn);

// =========================================================
// RESULTADO REAL
// =========================================================
$rows = oci_num_rows($stmt);

oci_free_statement($stmt);
oci_close($conn);

if ($rows > 0) {
    echo "<h2>Equipo eliminado correctamente</h2>";
    echo "<p style='color:green;'>Equipo: <b>$hostname</b></p>";
} else {
    echo "<h2>No encontrado</h2>";
    echo "<p style='color:orange;'>No existe el equipo: <b>$hostname</b></p>";
}

echo "<br><p><a href='delete_equipos.html'>Volver</a></p>";
echo "</center>";

?>
