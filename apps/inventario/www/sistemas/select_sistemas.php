<?php

require_once '/var/www/privado/session.safe.php';
require_once '/var/www/privado/db.connect.oracle.php';

if (!$conn) {
    die("Error de conexión Oracle.");
}

// =========================================================================
// EXPORTACIÓN CSV
// =========================================================================
if (isset($_GET['exportar'])) {

    $formato = limpiar($_GET['exportar']);

    if ($formato !== 'csv') {
        die("Formato no permitido.");
    }

    $query = "SELECT ID_SO, NOMBRE, VERSION
              FROM SISTEMAS_OPERATIVOS
              ORDER BY ID_SO ASC";

    $stmt = oci_parse($conn, $query);
    oci_execute($stmt);

    $datos = [];

    while ($row = oci_fetch_assoc($stmt)) {
        $datos[] = $row;
    }

    oci_free_statement($stmt);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="sistemas_operativos.csv"');

    $output = fopen('php://output', 'w');

    if (!empty($datos)) {
        fputcsv($output, array_keys($datos[0]));
    }

    foreach ($datos as $fila) {
        fputcsv($output, $fila);
    }

    fclose($output);
    oci_close($conn);
    exit;
}

// =========================================================================
// BÚSQUEDA
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nombre = limpiar($_POST["nombre"] ?? '');

    echo "<center>";

    // =====================================================
    // VALIDACIÓN
    // =====================================================
    if (!preg_match('/^[a-zA-Z0-9\s.\-_]*$/', $nombre)) {

        echo "<h2>Error de Validación</h2>";
        echo "<p style='color:red;'>Nombre no válido.</p>";
        echo "<p><a href='select_sistemas.html'>Volver</a></p>";
        echo "</center>";

        oci_close($conn);
        exit;
    }

    echo "<h2>Resultados de búsqueda</h2>";

    // =====================================================
    // 🔥 FIX REAL ORACLE: UPPER en SQL
    // =====================================================
    $query = "SELECT ID_SO, NOMBRE, VERSION
              FROM SISTEMAS_OPERATIVOS
              WHERE UPPER(NOMBRE) LIKE :nombre
              ORDER BY NOMBRE ASC";

    $stmt = oci_parse($conn, $query);

    $busqueda = strtoupper($nombre) . "%";

    oci_bind_by_name($stmt, ":nombre", $busqueda);

    oci_execute($stmt);

    $hay_resultados = false;

    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Versión</th>
          </tr>";

    while ($row = oci_fetch_assoc($stmt)) {

        $hay_resultados = true;

        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['ID_SO'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($row['NOMBRE'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($row['VERSION'] ?? '-') . "</td>";
        echo "</tr>";
    }

    echo "</table>";

    if (!$hay_resultados) {
        echo "<p>No se encontraron sistemas operativos.</p>";
    }

    oci_free_statement($stmt);
    oci_close($conn);

    echo "<br><p><a href='select_sistemas.html'>Volver</a></p>";
    echo "</center>";

    exit;
}

// =========================================================================
// ACCESO DIRECTO
// =========================================================================
header("Location: select_sistemas.html");
exit;

?>
