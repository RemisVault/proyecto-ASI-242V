<?php
require_once '/var/www/privado/session.safe.php';
require_once '/var/www/privado/db.connect.ldap_admin.php';

$user = limpiar($_POST["user"]);
$user_ldap = str_replace(array('\\', '*', '(', ')', "\0"), '', $user);

$password = limpiar($_POST["password"]);

$auth_success = false;

foreach ($ldap_hosts as $host) {
    $ldap_conn = @ldap_connect($host, $ldap_port);
    if (!$ldap_conn) continue;

    @ldap_set_option($ldap_conn, LDAP_OPT_PROTOCOL_VERSION, 3);
    @ldap_set_option($ldap_conn, LDAP_OPT_REFERRALS, 0);

    $ldap_bind = @ldap_bind($ldap_conn, $ldap_admin_user, $ldap_admin_pass);

    if ($ldap_bind) {
        $filter = "(sAMAccountName=$user_ldap)";
        $sr = @ldap_search($ldap_conn, $base_dn, $filter);

        if ($sr) {
            $info = @ldap_get_entries($ldap_conn, $sr);

            if ($info && $info["count"] > 0) {
                $user_dn = $info[0]["dn"];

                if (@ldap_bind($ldap_conn, $user_dn, $password)) {
                    $auth_success = true;
                    $_SESSION["auth"] = true;
                    $_SESSION["user"] = $user;
                }
            }
        }
    }

    @ldap_close($ldap_conn);

    if ($auth_success) break;
}

if ($auth_success) {
    header("Location: menu.php");
    exit;
} else {
    echo "Autenticación fallida para el usuario: " . htmlspecialchars($user);
    echo "<br><br><a href='index.php'><button>Volver</button></a>";
}
?>
