<?php

require_once '/var/www/privado/session.safe.php';
require_once '/var/www/privado/db.connect.oracle.php';

if (!$conn) {
    die("Error de conexión Oracle.");
}

// =====================================================
// EXPORT CSV
// =====================================================
if (isset($_GET['exportar']) && $_GET['exportar'] === 'csv') {

    $sql = "
    SELECT 
        e.HOSTNAME,
        e.DOMINIO,
        e.FUNCION_PRINCIPAL,
        e.IP_PRIMARIA,
        e.MAC_PRIMARIA,
        r.NOMBRE_RED,
        s.NOMBRE AS SO,
        s.VERSION AS SO_VERSION
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
// INPUT
// =====================================================
$hostname = limpiar($_POST['hostname'] ?? '');

echo "<center>";

// =====================================================
// VALIDACIÓN
// =====================================================
if (!preg_match('/^[a-zA-Z0-9.\-_]*$/', $hostname)) {
    echo "<h2>Error de validación</h2>";
    echo "<p>Hostname no válido</p>";
    echo "<a href='select_equipos_detalle.html'><button>Volver</button></a>";
    exit;
}

// =====================================================
// QUERY COMPLETA (CMDB)
// =====================================================
$sql = "
SELECT 
    e.HOSTNAME,
    e.DOMINIO,
    e.FUNCION_PRINCIPAL,
    e.IP_PRIMARIA,
    e.MAC_PRIMARIA,

    r.NOMBRE_RED,
    r.DIRECCION_RED,
    r.GATEWAY,

    s.NOMBRE AS SO,
    s.VERSION AS SO_VERSION,

    LISTAGG(DISTINCT 
        h.TIPO_COMPONENTE || ':' || NVL(h.CAPACIDAD,'-') || 'x' || NVL(h.CANTIDAD,1),
        ' | '
    ) WITHIN GROUP (ORDER BY h.TIPO_COMPONENTE) AS HARDWARE,

    LISTAGG(DISTINCT 
        srv.NOMBRE_SERVICIO,
        ' | '
    ) WITHIN GROUP (ORDER BY srv.NOMBRE_SERVICIO) AS SERVICIOS

FROM EQUIPOS e
LEFT JOIN REDES r ON e.ID_RED = r.ID_RED
LEFT JOIN SISTEMAS_OPERATIVOS s ON e.ID_SO = s.ID_SO
LEFT JOIN EQUIPO_HARDWARE h ON e.ID_EQUIPO = h.ID_EQUIPO
LEFT JOIN EQUIPO_SERVICIO es ON e.ID_EQUIPO = es.ID_EQUIPO
LEFT JOIN SERVICIOS srv ON es.ID_SERVICIO = srv.ID_SERVICIO

WHERE (:hostname IS NULL OR e.HOSTNAME LIKE :hostname)

GROUP BY 
    e.HOSTNAME,
    e.DOMINIO,
    e.FUNCION_PRINCIPAL,
    e.IP_PRIMARIA,
    e.MAC_PRIMARIA,
    r.NOMBRE_RED,
    r.DIRECCION_RED,
    r.GATEWAY,
    s.NOMBRE,
    s.VERSION

ORDER BY e.HOSTNAME
";

$stmt = oci_parse($conn, $sql);

$param = ($hostname === '') ? null : "%$hostname%";

oci_bind_by_name($stmt, ":hostname", $param);

oci_execute($stmt);

// =====================================================
// OUTPUT
// =====================================================
echo "<h2>Detalle completo de equipos</h2>";

echo "<table border='1' cellpadding='5'>";

echo "<tr>
<th>Hostname</th>
<th>Dominio</th>
<th>Función</th>
<th>IP</th>
<th>MAC</th>
<th>Red</th>
<th>Gateway</th>
<th>S.O</th>
<th>Versión</th>
<th>Hardware</th>
<th>Servicios</th>
</tr>";

$hay = false;

while ($row = oci_fetch_assoc($stmt)) {

    $hay = true;

    echo "<tr>
        <td>".htmlspecialchars($row['HOSTNAME'])."</td>
        <td>".htmlspecialchars($row['DOMINIO'])."</td>
        <td>".htmlspecialchars($row['FUNCION_PRINCIPAL'])."</td>
        <td>".htmlspecialchars($row['IP_PRIMARIA'])."</td>
        <td>".htmlspecialchars($row['MAC_PRIMARIA'])."</td>
        <td>".htmlspecialchars($row['NOMBRE_RED'])."</td>
        <td>".htmlspecialchars($row['GATEWAY'])."</td>
        <td>".htmlspecialchars($row['SO'])."</td>
        <td>".htmlspecialchars($row['SO_VERSION'])."</td>
        <td>".htmlspecialchars($row['HARDWARE'] ?? '-')."</td>
        <td>".htmlspecialchars($row['SERVICIOS'] ?? '-')."</td>
    </tr>";
}

echo "</table>";

if (!$hay) {
    echo "<p>No hay resultados</p>";
}

oci_free_statement($stmt);
oci_close($conn);

echo "<br><a href='select_equipos_detalle.html'><button>Volver</button></a>";
echo "</center>";

?>
