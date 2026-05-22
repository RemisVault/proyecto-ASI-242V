<?php
require_once '/var/www/privado/session.safe.php';

if (!isset($_SESSION["usuario_autenticado"]) || $_SESSION["usuario_autenticado"] !== true) {
    header("Location: index.php");
    exit;
}

$user = limpiar($_SESSION["user"]);

// ==========================================
// EXPORTACIÓN GLOBAL / IMPORTACIÓN GLOBAL
// ==========================================
require_once '/var/www/privado/db.connect.oracle.php';

// --- PROCESO DE IMPORTACIÓN (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file_import'])) {
    $file_name = $_FILES['file_import']['name'];
    $file_tmp  = $_FILES['file_import']['tmp_name'];

    echo "<center>";

    // Validación obligatoria con preg_match para la extensión del archivo
    if (!preg_match('/^.*\.(json|yaml|yml)$/i', $file_name, $matches)) {
        echo "<h2>Error de Validación</h2>";
        echo "<p style='color: red;'>El formato del archivo no es válido. Solo se permiten extensiones .json o .yaml</p>";
        echo "<p><a href='menu.php'><button type='button'>Volver</button></a></p>";
        echo "</center>";
        exit;
    }

    $ext = strtolower($matches[1]);
    $contenido = file_get_contents($file_tmp);
    $data_import = [];

    // Parseamos según el formato del archivo
    if ($ext === 'json') {
        $data_import = json_decode($contenido, true);
    } elseif ($ext === 'yaml' || $ext === 'yml') {
        $lineas = explode("\n", $contenido);
        $tabla_actual = '';
        $fila_actual = [];

        foreach ($lineas as $linea) {
            $linea = rtrim($linea);
            if (empty($linea) || $linea === '---') continue;

            if (preg_match('/^([A-Z_]+):$/', $linea, $m_tabla)) {
                if (!empty($fila_actual) && !empty($tabla_actual)) {
                    $data_import[$tabla_actual][] = $fila_actual;
                    $fila_actual = [];
                }
                $tabla_actual = $m_tabla[1];
                continue;
            }
            if (trim($linea) === '-') {
                if (!empty($fila_actual) && !empty($tabla_actual)) {
                    $data_import[$tabla_actual][] = $fila_actual;
                    $fila_actual = [];
                }
                continue;
            }
            if (preg_match('/^\s+([A-Z0-9_]+):\s*"(.*)"$/', $linea, $m_valores)) {
                $campo = $m_valores[1];
                // SOLUCIÓN XSS: Saneamos el valor extraído antes de guardarlo en el array
                $valor = limpiar($m_valores[2]);
                $fila_actual[$campo] = ($valor === '-') ? null : $valor;
            }
        }
        if (!empty($fila_actual) && !empty($tabla_actual)) {
            $data_import[$tabla_actual][] = $fila_actual;
        }
    }

    if (!empty($data_import)) {
        $tablas_orden = [
            'EQUIPO_SERVICIO', 'EQUIPO_HARDWARE', 'USUARIOS_CREDENCIALES',
            'EQUIPOS', 'SERVICIOS', 'SISTEMAS_OPERATIVOS', 'REDES', 'PARAMETROS_BASICOS'
        ];

        foreach ($tablas_orden as $tabla) {
            $sql_del = "DELETE FROM " . $tabla;
            $stmt_del = oci_parse($conn, $sql_del);
            oci_execute($stmt_del, OCI_NO_AUTO_COMMIT);
            oci_free_statement($stmt_del);
        }

        $tablas_orden_inv = array_reverse($tablas_orden);
        $error_ocurrido = false;

        foreach ($tablas_orden_inv as $tabla) {
            if (!isset($data_import[$tabla]) || !is_array($data_import[$tabla])) continue;

            foreach ($data_import[$tabla] as $fila) {
                $columnas = array_keys($fila);
                $columnas_validas = true;
                foreach ($columnas as $col) {
                    if (!preg_match('/^[a-zA-Z0-9_]+$/', $col)) {
                        $columnas_validas = false;
                        break;
                    }
                }
                
                if (!$columnas_validas) {
                    $error_ocurrido = true;
                    continue; // Descarta esta fila corrupta o maliciosa
                }

                $placeholders = array_map(function($col) { return ":" . $col; }, $columnas);

                $sql_ins = "INSERT INTO " . $tabla . " (" . implode(', ', $columnas) . ") VALUES (" . implode(', ', $placeholders) . ")";
                $stmt_ins = oci_parse($conn, $sql_ins);

                foreach ($fila as $col => $val) {
                    oci_bind_by_name($stmt_ins, ":" . $col, $fila[$col]);
                }

                $r = oci_execute($stmt_ins, OCI_NO_AUTO_COMMIT);
                if (!$r) {
                    $error_ocurrido = true;
                }
                oci_free_statement($stmt_ins);
            }
        }

        if (!$error_ocurrido) {
            oci_commit($conn);
            echo "<h2 style='color: green;'>¡Importación Completada con Éxito!</h2>";
            echo "<p>Los datos han sido restaurados de forma global.</p>";
        } else {
            oci_rollback($conn);
            echo "<h2 style='color: red;'>Error en la Importación</h2>";
            echo "<p>Se realizó un rollback. Verifique la estructura del archivo.</p>";
        }
    } else {
        echo "<h2 style='color: orange;'>El archivo está vacío o no tiene un formato válido</h2>";
    }

    oci_close($conn);
    echo "<p><a href='menu.php'><button type='button'>Volver al Menú</button></a></p>";
    echo "</center>";
    exit;
}

// --- PROCESO DE EXPORTACIÓN (GET) ---
if (isset($_GET['export']) && in_array($_GET['export'], ['json', 'yaml'])) {

    function fetch_table($conn, $sql) {
        $stmt = oci_parse($conn, $sql);
        oci_execute($stmt);

        $data = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $data[] = $row;
        }

        oci_free_statement($stmt);
        return $data;
    }

    $dump = [];

    $dump['PARAMETROS_BASICOS']    = fetch_table($conn, "SELECT * FROM PARAMETROS_BASICOS");
    $dump['REDES']                 = fetch_table($conn, "SELECT * FROM REDES");
    $dump['SISTEMAS_OPERATIVOS']   = fetch_table($conn, "SELECT * FROM SISTEMAS_OPERATIVOS");
    $dump['SERVICIOS']             = fetch_table($conn, "SELECT * FROM SERVICIOS");
    $dump['EQUIPOS']               = fetch_table($conn, "SELECT * FROM EQUIPOS");
    $dump['EQUIPO_HARDWARE']       = fetch_table($conn, "SELECT * FROM EQUIPO_HARDWARE");
    $dump['USUARIOS_CREDENCIALES'] = fetch_table($conn, "SELECT * FROM USUARIOS_CREDENCIALES");
    $dump['EQUIPO_SERVICIO']       = fetch_table($conn, "SELECT * FROM EQUIPO_SERVICIO");

    if (ob_get_level()) ob_end_clean();

    $format = $_GET['export'];

    if ($format === 'json') {
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="inventario.json"');
        echo json_encode($dump, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        oci_close($conn);
        exit;
    }

    header('Content-Type: text/yaml; charset=utf-8');
    header('Content-Disposition: attachment; filename="inventario.yaml"');

    echo "---\n";
    foreach ($dump as $tabla => $filas) {
        echo $tabla . ":\n";
        foreach ($filas as $fila) {
            echo "  -\n";
            foreach ($fila as $k => $v) {
                $v = $v ?? "-";
                echo "      $k: \"" . $v . "\"\n";
            }
        }
        echo "\n";
    }

    oci_close($conn);
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Menú Principal - Inventario</title>
</head>
<body>

<div style="float: left; text-align: left; margin-left: 15px; margin-top: 15px;">

    <div style="margin-bottom: 8px;">
        <a href="?export=json" style="text-decoration: none;">
            <button type="button" style="margin-right: 5px; cursor: pointer;">Exportar JSON</button>
        </a>
        <a href="?export=yaml" style="text-decoration: none;">
            <button type="button" style="cursor: pointer;">Exportar YAML</button>
        </a>
    </div>

    <form method="POST" action="" enctype="multipart/form-data" style="margin: 0; padding: 0;">
        <input type="file" id="import_json" name="file_import" accept=".json" style="display: none;" onchange="this.form.submit()">
        <input type="file" id="import_yaml" name="file_import" accept=".yaml,.yml" style="display: none;" onchange="this.form.submit()">

        <button type="button" onclick="document.getElementById('import_json').click()" style="margin-right: 5px; cursor: pointer;">Importar JSON</button>
        <button type="button" onclick="document.getElementById('import_yaml').click()" style="cursor: pointer;">Importar YAML</button>
    </form>

</div>

<div style="clear: both;"></div>

<div style="float: right; text-align: right; margin-right: 15px;">
    <strong><?php echo htmlspecialchars($user); ?></strong>
</div>

<div style="clear: both;"></div>

<center>

    <h1>Bienvenido al Sistema de Inventario</h1>

    <hr width="50%" style="margin: 25px 0;">

    <h3>Tablas Maestras</h3>
    <p><a href="equipos/index.php">Gestion de Equipos</a></p>
    <p><a href="redes/index.php">Gestion de Redes</a></p>
    <p><a href="sistemas/index.php">Sistemas Operativos</a></p>

    <hr width="30%" style="margin: 20px 0;">

    <h3>Analisis y Extraccion</h3>
    <p><a href="consultas/index.php">Consultas Avanzadas e Informes</a></p>

    <hr width="50%" style="margin: 25px 0;">

    <form action="logout.php" method="POST">
        <button type="submit">Salir</button>
    </form>

</center>

</body>
</html>