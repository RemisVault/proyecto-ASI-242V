<?php

require_once '/var/www/privado/session.safe.php';
require_once '/var/www/privado/db.connect.oracle.php';

// =========================================================================
// CONEXIÓN
// =========================================================================
if (!$conn) {
    die("Error de comunicación con el sistema central.");
}

// =========================================================================
// PROCESAMIENTO DEL PROCEDIMIENTO ALMACENADO
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nombre_red = limpiar($_POST["nombre_red"] ?? '');

    echo "<center>";

    // Validación obligatoria con preg_match (Letras, números, guiones y espacios)
    if (!preg_match('/^[a-zA-Z0-9\s_-]*$/', $nombre_red) || $nombre_red === '') {

        echo "<h2>Error de Validación</h2>";
        echo "<p style='color:red;'>Nombre de red no válido.</p>";
        echo "<p><a href='equipos_por_red.html'><button type='button'>Volver</button></a></p>";
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
        die("Error en el procesamiento de la consulta del inventario.");
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
        $e = oci_error($stmt);
        if (preg_match('/ORA-20\d{3}/', $e['message'])) {
            echo "<p style='color:red;'><strong>" . htmlspecialchars($e['message']) . "</strong></p>";
        } else {
            echo "<p style='color:red;'><strong>No se pudo completar la consulta debido a un problema técnico en el servidor central.</strong></p>";
        }
    }

    oci_free_statement($stmt);
    oci_close($conn);

    echo "<br><p><a href='equipos_por_red.html'><button type='button'>Volver</button></a></p>";
    echo "</center>";

    exit;
}

// Redirección por defecto al HTML correcto si entran de forma directa
header("Location: equipos_por_red.html");
exit;
?>