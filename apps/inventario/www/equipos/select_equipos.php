<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

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
// EXPORTACIÓN
// =========================================================================
if (isset($_GET['exportar'])) {

    $formato = limpiar($_GET['exportar']);

    $query = "SELECT
                ID_EQUIPO,
                HOSTNAME,
                DOMINIO,
                FUNCION_PRINCIPAL,
                IP_PRIMARIA,
                MAC_PRIMARIA,
                ID_RED,
                ID_SO
              FROM EQUIPOS
              ORDER BY ID_EQUIPO ASC";

    $stmt = oci_parse($conn, $query);

    if (!$stmt) {
        die(oci_error($conn)['message']);
    }

    oci_execute($stmt);

    $datos = [];

    while ($row = oci_fetch_assoc($stmt)) {
        $datos[] = $row;
    }

    oci_free_statement($stmt);

    if (ob_get_level()) ob_end_clean();

    if ($formato === 'csv') {

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="equipos_inventario.csv"');

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

    if ($formato === 'json') {

        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="equipos_inventario.json"');

        echo json_encode($datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        oci_close($conn);
        exit;
    }

    if ($formato === 'yaml') {

        header('Content-Type: text/yaml; charset=utf-8');
        header('Content-Disposition: attachment; filename="equipos_inventario.yaml"');

        echo "---\n";

        foreach ($datos as $fila) {
            echo "- hostname: \"" . ($fila['HOSTNAME'] ?? '-') . "\"\n";
            echo "  dominio: \"" . ($fila['DOMINIO'] ?? '-') . "\"\n";
            echo "  funcion_principal: \"" . ($fila['FUNCION_PRINCIPAL'] ?? '-') . "\"\n";
            echo "  ip_primaria: \"" . ($fila['IP_PRIMARIA'] ?? '-') . "\"\n";
            echo "  mac_primaria: \"" . ($fila['MAC_PRIMARIA'] ?? '-') . "\"\n";
            echo "  id_red: \"" . ($fila['ID_RED'] ?? '-') . "\"\n";
            echo "  id_so: \"" . ($fila['ID_SO'] ?? '-') . "\"\n";
        }

        oci_close($conn);
        exit;
    }

    echo "Formato no válido.";
    oci_close($conn);
    exit;
}

// =========================================================================
// BÚSQUEDA
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $hostname = limpiar($_POST["hostname"] ?? '');

    echo "<center>";

    // validación básica (hostname realista)
    if (!preg_match('/^[a-zA-Z0-9.\-_]*$/', $hostname)) {

        echo "<h2>Error de Validación</h2>";
        echo "<p style='color:red;'>Hostname no válido.</p>";
        echo "<p><a href='select_equipos.html'><button>Volver</button></a></p>";
        echo "</center>";

        oci_close($conn);
        exit;
    }

    echo "<h2>Resultados de búsqueda</h2>";

    $query = "SELECT
                HOSTNAME,
                DOMINIO,
                FUNCION_PRINCIPAL,
                IP_PRIMARIA,
                MAC_PRIMARIA,
                ID_RED,
                ID_SO
              FROM EQUIPOS
              WHERE HOSTNAME LIKE :hostname
              ORDER BY HOSTNAME ASC";

    $stmt = oci_parse($conn, $query);

    if (!$stmt) {
        die(oci_error($conn)['message']);
    }

    $busqueda = "%" . $hostname . "%";

    oci_bind_by_name($stmt, ":hostname", $busqueda);

    oci_execute($stmt);

    $hay_resultados = false;

    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr>
            <th>Hostname</th>
            <th>Dominio</th>
            <th>Función</th>
            <th>IP</th>
            <th>MAC</th>
            <th>ID Red</th>
            <th>ID SO</th>
          </tr>";

    while ($row = oci_fetch_assoc($stmt)) {

        $hay_resultados = true;

        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['HOSTNAME'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($row['DOMINIO'] ?? '-') . "</td>";
        echo "<td>" . htmlspecialchars($row['FUNCION_PRINCIPAL'] ?? '-') . "</td>";
        echo "<td>" . htmlspecialchars($row['IP_PRIMARIA'] ?? '-') . "</td>";
        echo "<td>" . htmlspecialchars($row['MAC_PRIMARIA'] ?? '-') . "</td>";
        echo "<td>" . htmlspecialchars($row['ID_RED'] ?? '-') . "</td>";
        echo "<td>" . htmlspecialchars($row['ID_SO'] ?? '-') . "</td>";
        echo "</tr>";
    }

    echo "</table>";

    if (!$hay_resultados) {
        echo "<p>No se encontraron equipos.</p>";
    }

    oci_free_statement($stmt);
    oci_close($conn);

    echo "<br><p><a href='select_equipos.html'><button>Volver</button></a></p>";
    echo "</center>";

    exit;
}

header("Location: select_equipos.html");
exit;

?>
