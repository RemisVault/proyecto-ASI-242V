<?php

require_once '/var/www/privado/session.safe.php';
require_once '/var/www/privado/db.connect.oracle.php';

// =========================================================================
// COMPROBACIÓN DE CONEXIÓN
// =========================================================================
if (!$conn) {
    die("Error de conexión Oracle.");
}

// =========================================================================
// EXPORTACIÓN CSV
// =========================================================================
if (isset($_GET['exportar'])) {

    $formato = limpiar($_GET['exportar']);

    // Solo permitir CSV
    if ($formato !== 'csv') {
        die("Formato no permitido.");
    }

    $query = "SELECT 
                ID_RED,
                NOMBRE_RED,
                DIRECCION_RED,
                GATEWAY,
                INTERFAZ_ROUTER,
                DESCRIPCION
              FROM REDES
              ORDER BY ID_RED ASC";

    $stmt = oci_parse($conn, $query);
    oci_execute($stmt);

    $datos = [];

    while ($row = oci_fetch_assoc($stmt)) {
        $datos[] = $row;
    }

    oci_free_statement($stmt);

    if (ob_get_level()) {
        ob_end_clean();
    }

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="redes_inventario.csv"');

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

    $nombre_red = limpiar($_POST["nombre_red"] ?? '');

    echo "<center>";

    // =========================================================================
    // VALIDACIÓN
    // =========================================================================
    if (!preg_match('/^[a-zA-Z0-9\s_-]*$/', $nombre_red)) {

        echo "<h2>Error de Validación</h2>";
        echo "<p style='color:red;'>El campo contiene caracteres no permitidos.</p>";
        echo "<p><a href='select_redes.html'><button type='button'>Volver</button></a></p>";
        echo "</center>";

        oci_close($conn);
        exit;
    }

    echo "<h2>Resultados de búsqueda</h2>";

    $query = "SELECT 
                ID_RED,
                NOMBRE_RED,
                DIRECCION_RED,
                GATEWAY,
                INTERFAZ_ROUTER,
                DESCRIPCION
              FROM REDES
              WHERE NOMBRE_RED LIKE :nombre
              ORDER BY ID_RED ASC";

    $stmt = oci_parse($conn, $query);

    $busqueda = "%" . $nombre_red . "%";

    oci_bind_by_name($stmt, ":nombre", $busqueda);

    oci_execute($stmt);

    $hay_resultados = false;

    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr>
            <th>ID</th>
            <th>Nombre de Red</th>
            <th>Dirección IP / CIDR</th>
            <th>Gateway</th>
            <th>Interfaz Router</th>
            <th>Descripción</th>
          </tr>";

    while ($row = oci_fetch_assoc($stmt)) {

        $hay_resultados = true;

        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['ID_RED'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($row['NOMBRE_RED'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($row['DIRECCION_RED'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($row['GATEWAY'] ?? '-') . "</td>";
        echo "<td>" . htmlspecialchars($row['INTERFAZ_ROUTER'] ?? '-') . "</td>";
        echo "<td>" . htmlspecialchars($row['DESCRIPCION'] ?? '-') . "</td>";
        echo "</tr>";
    }

    echo "</table>";

    if (!$hay_resultados) {
        echo "<p>No se encontraron redes con ese nombre.</p>";
    }

    oci_free_statement($stmt);
    oci_close($conn);

    echo "<br>";
    echo "<p><a href='select_redes.html'><button type='button'>Volver</button></a></p>";
    echo "</center>";

    exit;
}

// =========================================================================
// ACCESO DIRECTO
// =========================================================================
header("Location: select_redes.html");
exit;

?>
