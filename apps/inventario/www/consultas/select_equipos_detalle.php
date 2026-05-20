<?php
require_once '/var/www/privado/session.safe.php';
require_once '/var/www/privado/db.connect.oracle.php';

if (!$conn) {
    die("Error de conexión Oracle.");
}

// =====================================================
// EXPORT CSV )
// =====================================================
if (isset($_GET['exportar']) && $_GET['exportar'] === 'csv') {
    $sql = "
    SELECT
        e.HOSTNAME, e.DOMINIO, e.FUNCION_PRINCIPAL, e.IP_PRIMARIA, e.MAC_PRIMARIA,
        r.NOMBRE_RED, s.NOMBRE AS SO, s.VERSION AS SO_VERSION
    FROM EQUIPOS e
    LEFT JOIN REDES r ON e.ID_RED = r.ID_RED
    LEFT JOIN SISTEMAS_OPERATIVOS s ON e.ID_SO = s.ID_SO
    ORDER BY e.HOSTNAME
    ";

    $stmt = oci_parse($conn, $sql);
    oci_execute($stmt);

    $data = [];
    while ($row = oci_fetch_assoc($stmt)) {
        $data[] = $row;
    }
    oci_free_statement($stmt);

    header("Content-Type: text/csv");
    header("Content-Disposition: attachment; filename=equipos_detalle.csv");
    $out = fopen("php://output", "w");
    if (!empty($data)) {
        fputcsv($out, array_keys($data[0]));
    }
    foreach ($data as $row) {
        fputcsv($out, $row);
    }
    fclose($out);
    oci_close($conn);
    exit;
}

// =====================================================
// LECTURA DE VARIABLES Y VALIDACIÓN
// =====================================================
$id_red = '';
$hostname = '';
$accion = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_red = limpiar($_POST['id_red'] ?? '');
    $hostname = limpiar($_POST['hostname'] ?? '');
    $accion = $_POST['accion'] ?? '';

    // Validación estricta con preg_match para ambos campos
    if ($id_red !== '' && !preg_match('/^[0-9]+$/', $id_red)) {
        echo "<center><h2 style='color:red;'>Error de validación</h2><p>El ID de red no es válido.</p><a href='select_equipos_detalle.php'><button>Volver</button></a></center>";
        exit;
    }
    if ($hostname !== '' && !preg_match('/^[a-zA-Z0-9.\-_]*$/', $hostname)) {
        echo "<center><h2 style='color:red;'>Error de validación</h2><p>El Hostname contiene caracteres no permitidos.</p><a href='select_equipos_detalle.php'><button>Volver</button></a></center>";
        exit;
    }
    
    // Si el usuario pulsó "Cargar Equipos", reseteamos el hostname para evitar filtros cruzados erróneos
    if ($accion === 'cargar_equipos') {
        $hostname = '';
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle Equipos</title>
</head>
<body>

<div style="float: left; margin: 15px;">
    <a href="select_equipos_detalle.php?exportar=csv">
        <button type="button">Exportar CSV</button>
    </a>
</div>
<div style="clear: both;"></div>

<center>
    <h2>Consulta completa de Equipos</h2>

    <form method="post" action="select_equipos_detalle.php">
        
        <label for="id_red">Red:</label>
        <select name="id_red" id="id_red">
            <option value="">-- Todas las redes --</option>
            <?php
            $stmtR = oci_parse($conn, "SELECT ID_RED, NOMBRE_RED FROM REDES ORDER BY NOMBRE_RED");
            oci_execute($stmtR);
            while ($rowR = oci_fetch_assoc($stmtR)) {
                $selected = ($id_red == $rowR['ID_RED']) ? 'selected' : '';
                echo "<option value='" . htmlspecialchars($rowR['ID_RED']) . "' $selected>" . htmlspecialchars($rowR['NOMBRE_RED']) . "</option>";
            }
            oci_free_statement($stmtR);
            ?>
        </select>
        
        <button type="submit" name="accion" value="cargar_equipos">Filtrar Red</button>

        &nbsp;&nbsp;&nbsp;

        <label for="hostname">Equipo:</label>
        <select name="hostname" id="hostname">
            <option value="">-- Todos los equipos --</option>
            <?php
            $sqlE = "SELECT HOSTNAME FROM EQUIPOS";
            if ($id_red !== '') {
                $sqlE .= " WHERE ID_RED = :id_red";
            }
            $sqlE .= " ORDER BY HOSTNAME";
            
            $stmtE = oci_parse($conn, $sqlE);
            if ($id_red !== '') {
                oci_bind_by_name($stmtE, ":id_red", $id_red);
            }
            oci_execute($stmtE);
            
            while ($rowE = oci_fetch_assoc($stmtE)) {
                $selected = ($hostname === $rowE['HOSTNAME']) ? 'selected' : '';
                echo "<option value='" . htmlspecialchars($rowE['HOSTNAME']) . "' $selected>" . htmlspecialchars($rowE['HOSTNAME']) . "</option>";
            }
            oci_free_statement($stmtE);
            ?>
        </select>

        <button type="submit" name="accion" value="buscar">Buscar</button>
    </form>

    <br>

<?php
// =====================================================
// MOSTRAR TABLA SOLO SI SE PULSÓ "BUSCAR"
// =====================================================
if ($accion === 'buscar') {
    $sql = "
    SELECT
        e.HOSTNAME, e.DOMINIO, e.FUNCION_PRINCIPAL, e.IP_PRIMARIA, e.MAC_PRIMARIA,
        r.NOMBRE_RED, r.DIRECCION_RED, r.GATEWAY,
        s.NOMBRE AS SO, s.VERSION AS SO_VERSION,
        LISTAGG(DISTINCT h.TIPO_COMPONENTE || ':' || NVL(h.CAPACIDAD,'-') || 'x' || NVL(h.CANTIDAD,1), ' | ') WITHIN GROUP (ORDER BY h.TIPO_COMPONENTE) AS HARDWARE,
        LISTAGG(DISTINCT srv.NOMBRE_SERVICIO, ' | ') WITHIN GROUP (ORDER BY srv.NOMBRE_SERVICIO) AS SERVICIOS
    FROM EQUIPOS e
    LEFT JOIN REDES r ON e.ID_RED = r.ID_RED
    LEFT JOIN SISTEMAS_OPERATIVOS s ON e.ID_SO = s.ID_SO
    LEFT JOIN EQUIPO_HARDWARE h ON e.ID_EQUIPO = h.ID_EQUIPO
    LEFT JOIN EQUIPO_SERVICIO es ON e.ID_EQUIPO = es.ID_EQUIPO
    LEFT JOIN SERVICIOS srv ON es.ID_SERVICIO = srv.ID_SERVICIO
    WHERE (:id_red IS NULL OR r.ID_RED = :id_red)
      AND (:hostname IS NULL OR e.HOSTNAME = :hostname)
    GROUP BY
        e.HOSTNAME, e.DOMINIO, e.FUNCION_PRINCIPAL, e.IP_PRIMARIA, e.MAC_PRIMARIA,
        r.NOMBRE_RED, r.DIRECCION_RED, r.GATEWAY, s.NOMBRE, s.VERSION
    ORDER BY e.HOSTNAME
    ";

    $stmt = oci_parse($conn, $sql);

    $param_red = ($id_red === '') ? null : $id_red;
    $param_host = ($hostname === '') ? null : $hostname;

    oci_bind_by_name($stmt, ":id_red", $param_red);
    oci_bind_by_name($stmt, ":hostname", $param_host);

    oci_execute($stmt);

    echo "<h2>Detalle completo de equipos</h2>";
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr>
            <th>Hostname</th><th>Dominio</th><th>Función</th><th>IP</th><th>MAC</th>
            <th>Red</th><th>Gateway</th><th>S.O</th><th>Versión</th><th>Hardware</th><th>Servicios</th>
          </tr>";

    $hay = false;
    while ($row = oci_fetch_assoc($stmt)) {
        $hay = true;
        echo "<tr>
            <td>".htmlspecialchars($row['HOSTNAME'] ?? '')."</td>
            <td>".htmlspecialchars($row['DOMINIO'] ?? '')."</td>
            <td>".htmlspecialchars($row['FUNCION_PRINCIPAL'] ?? '')."</td>
            <td>".htmlspecialchars($row['IP_PRIMARIA'] ?? '')."</td>
            <td>".htmlspecialchars($row['MAC_PRIMARIA'] ?? '')."</td>
            <td>".htmlspecialchars($row['NOMBRE_RED'] ?? '')."</td>
            <td>".htmlspecialchars($row['GATEWAY'] ?? '')."</td>
            <td>".htmlspecialchars($row['SO'] ?? '')."</td>
            <td>".htmlspecialchars($row['SO_VERSION'] ?? '')."</td>
            <td>".htmlspecialchars($row['HARDWARE'] ?? '-')."</td>
            <td>".htmlspecialchars($row['SERVICIOS'] ?? '-')."</td>
        </tr>";
    }
    echo "</table>";

    if (!$hay) {
        echo "<p>No se encontraron equipos bajo esos criterios.</p>";
    }

    oci_free_statement($stmt);
}

oci_close($conn);
?>

    <br><br>
    <p><a href="index.php">Volver al Menú Principal</a></p>

</center>

</body>
</html>
