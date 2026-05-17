<?php
$ora_user = "alvaro_oracle";
$ora_pass = "1234";
$ora_db   = "inventario_backend:1521/freepdb1";

$conn = oci_connect($ora_user, $ora_pass, $ora_db, 'AL32UTF8');

if (!$conn) {
    $e = oci_error();
    die("Error de conexión Oracle: " . $e['message']);
}
?>
