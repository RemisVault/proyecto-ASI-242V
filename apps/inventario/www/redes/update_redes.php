<?php

require_once '/var/www/privado/session.safe.php';
require_once '/var/www/privado/db.connect.oracle.php';

if (!$conn) {
    die("Error de conexión Oracle.");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: update_redes.html");
    exit;
}

$nombre_red = strtoupper(limpiar($_POST["nombre_red"] ?? ''));
$direccion_red = limpiar($_POST["direccion_red"] ?? '');
$gateway = limpiar($_POST["gateway"] ?? '');

echo "<center>";

// ==========================================================
// VALIDACIÓN BÁSICA
// ==========================================================
if (!preg_match('/^[A-Z0-9\s_-]+$/', $nombre_red)) {
    echo "<h2>Error</h2>";
    echo "<p style='color:red;'>Nombre de red inválido</p>";
    exit;
}

// ==========================================================
// 1. OBTENER ID_RED
// ==========================================================
$sql = "SELECT ID_RED FROM REDES WHERE NOMBRE_RED = :nombre_red";
$stmt = oci_parse($conn, $sql);

oci_bind_by_name($stmt, ":nombre_red", $nombre_red);
oci_execute($stmt);

$row = oci_fetch_assoc($stmt);
oci_free_statement($stmt);

if (!$row) {
    echo "<h2>No encontrada</h2>";
    echo "<p style='color:orange;'>La red no existe: <b>$nombre_red</b></p>";
    exit;
}

$id_red = $row['ID_RED'];

// ==========================================================
// 2. UPDATE
// ==========================================================
$sql = "UPDATE REDES 
        SET DIRECCION_RED = :direccion_red,
            GATEWAY = :gateway
        WHERE ID_RED = :id_red";

$stmt = oci_parse($conn, $sql);

oci_bind_by_name($stmt, ":direccion_red", $direccion_red);
oci_bind_by_name($stmt, ":gateway", $gateway);
oci_bind_by_name($stmt, ":id_red", $id_red);

$result = oci_execute($stmt);

if (!$result) {
    $e = oci_error($stmt);
    echo "<h2>Error al actualizar</h2>";
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
echo "<h2>Red actualizada correctamente</h2>";
echo "<p style='color:green;'>Red: <b>$nombre_red</b></p>";
echo "<p>Nueva dirección: $direccion_red</p>";
echo "<p>Nuevo gateway: $gateway</p>";

echo "<br><a href='update_redes.html'><button>Volver</button></a>";

echo "</center>";

?>
