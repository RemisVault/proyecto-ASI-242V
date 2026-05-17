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
// PROCESAMIENTO DEL PROCEDIMIENTO ALMACENADO
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // limpiar() ya maneja el trim interno de la cadena
    $nombre_red = limpiar($_POST["nombre_red"] ?? '');

    echo "<center>";

    // Validación obligatoria con preg_match (Letras, números, guiones y espacios)
    if (!preg_match('/^[a-zA-Z0-9\s_-]*$/', $nombre_red) || $nombre_red === '') {

        echo "<h2>Error de Validación</h2>";
        echo "<p style='color:red;'>Nombre de red no válido.</p>";
        echo "<p><a href='equipos_por_red.html'><button style='cursor:pointer;'>Volver</button></a></p>";
        echo "</center>";

        oci_close($conn);
        exit;
    }

    echo "<h2>Resultados de búsqueda</h2>";

    // Inicializamos el cursor de salida de Oracle
    $p_cursor = oci_new_cursor($conn);

    // Llamada al bloque PL/SQL para el procedimiento prc_listar_equipos_red
    $query = "BEGIN prc_listar_equipos_red(:p_nombre_red, :p_cursor); END;";
    $stmt = oci_parse($conn, $query);

    if (!$stmt) {
        die(oci_error($conn)['message']);
    }

    // Vinculamos los parámetros IN y OUT
    oci_bind_by_name($stmt, ":p_nombre_red", $nombre_red);
    oci_bind_by_name($stmt, ":p_cursor", $p_cursor, -1, OCI_B_CURSOR);

    // Ejecutamos controlando de forma limpia el RAISE_APPLICATION_ERROR del procedimiento
    if (@oci_execute($stmt)) {
        
        // Ejecutamos el cursor para poder recorrer sus datos
        oci_execute($p_cursor);

        $hay_resultados = false;

        echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse; text-align: left;'>";
        echo "<tr style='background-color: #f2f2f2;'>
                <th>ID Equipo</th>
                <th>Hostname</th>
                <th>Dominio</th>
                <th>IP Primaria</th>
                <th>MAC Primaria</th>
              </tr>";

        while ($row = oci_fetch_assoc($p_cursor)) {

            $hay_resultados = true;

            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['ID_EQUIPO'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($row['HOSTNAME'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($row['DOMINIO'] ?? '-') . "</td>";
            echo "<td>" . htmlspecialchars($row['IP_PRIMARIA'] ?? '-') . "</td>";
            echo "<td>" . htmlspecialchars($row['MAC_PRIMARIA'] ?? '-') . "</td>";
            echo "</tr>";
        }

        echo "</table>";

        if (!$hay_resultados) {
            echo "<p>No se encontraron equipos asignados a esta red.</p>";
        }

        oci_free_statement($p_cursor);

    } else {
        // Captura el error -2004 definido en el EXCEPTION de tu Oracle si la red no existe
        $e = oci_error($stmt);
        echo "<p style='color:red;'><strong>" . htmlspecialchars($e['message']) . "</strong></p>";
    }

    oci_free_statement($stmt);
    oci_close($conn);

    echo "<br><p><a href='equipos_por_red.html'><button style='cursor:pointer;'>Volver</button></a></p>";
    echo "</center>";

    exit;
}

// Redirección por defecto al HTML correcto si entran de forma directa
header("Location: equipos_por_red.html");
exit;
?>
