<?php

require_once '/var/www/privado/session.safe.php';
require_once '/var/www/privado/db.connect.oracle.php';

if (!$conn) {
    die("Error de conexión Oracle.");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: delete_redes.html");
    exit;
}

$nombre_red = strtoupper(limpiar($_POST["nombre_red"] ?? ''));

echo "<center>";

// ==========================================================
// VALIDACIÓN
// ==========================================================
if (!preg_match('/^[A-Z0-9\s_-]+$/', $nombre_red)) {
    echo "<h2>Error de Validación</h2>";
    echo "<p style='color:red;'>Nombre de red no válido.</p>";
    echo "<p><a href='delete_redes.html'><button>Volver</button></a></p>";
    echo "</center>";
    oci_close($conn);
    exit;
}

// ==========================================================
// 1. OBTENER ID_RED (MEJOR PRÁCTICA)
// ==========================================================
$sql = "SELECT ID_RED FROM REDES WHERE NOMBRE_RED = :nombre_red";
$stmt = oci_parse($conn, $sql);

oci_bind_by_name($stmt, ":nombre_red", $nombre_red);
oci_execute($stmt);

$row = oci_fetch_assoc($stmt);
oci_free_statement($stmt);

if (!$row) {
    echo "<h2>No encontrada</h2>";
    echo "<p style='color:orange;'>No existe la red: <b>$nombre_red</b></p>";
    echo "<p><a href='delete_redes.html'><button>Volver</button></a></p>";
    echo "</center>";
    oci_close($conn);
    exit;
}

$id_red = $row['ID_RED'];

// ==========================================================
// 2. CHECK FK (EQUIPOS DEPENDEN DE REDES)
// ==========================================================
$sql = "SELECT COUNT(*) AS TOTAL FROM EQUIPOS WHERE ID_RED = :id_red";
$stmt = oci_parse($conn, $sql);

oci_bind_by_name($stmt, ":id_red", $id_red);
oci_execute($stmt);

$row = oci_fetch_assoc($stmt);
oci_free_statement($stmt);

$dependencias = array_values($row)[0] ?? 0;

if ($dependencias > 0) {
    echo "<h2>No se puede eliminar</h2>";
    echo "<p style='color:red;'>Hay $dependencias equipos usando esta red.</p>";
    echo "<p>Reasigna o elimina esos equipos primero.</p>";
    echo "</center>";
    oci_close($conn);
    exit;
}

// ==========================================================
// 3. DELETE
// ==========================================================
$sql = "DELETE FROM REDES WHERE ID_RED = :id_red";
$stmt = oci_parse($conn, $sql);

oci_bind_by_name($stmt, ":id_red", $id_red);

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

oci_commit($conn);

oci_free_statement($stmt);
oci_close($conn);

// ==========================================================
// RESULTADO
// ==========================================================
echo "<h2>Red eliminada correctamente</h2>";
echo "<p style='color:green;'>Red: <b>$nombre_red</b></p>";
echo "<p>Filas afectadas: 1 (esperado)</p>";

echo "<br><p><a href='delete_redes.html'><button>Volver</button></a></p>";
echo "</center>";

?>
