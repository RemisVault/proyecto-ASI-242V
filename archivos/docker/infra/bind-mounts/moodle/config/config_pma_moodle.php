<?php
/**
 * Configuración de phpMyAdmin para el Frontend de Moodle
 */
$i = 0;

/* Incremento de servidor */
$i++;

/* Nombre que aparecerá en el panel */
$cfg['Servers'][$i]['verbose'] = 'Moodle Database (MariaDB)';

/* Host: Usamos el nombre del servicio del backend de Moodle */
$cfg['Servers'][$i]['host'] = 'moodle_backend'; 

/* Puerto estándar */
$cfg['Servers'][$i]['port'] = '3306';

/* Forzamos el uso de TCP */
$cfg['Servers'][$i]['connect_type'] = 'tcp';

/* Método de autenticación */
$cfg['Servers'][$i]['auth_type'] = 'cookie';

/* Seguridad */
$cfg['Servers'][$i]['AllowNoPassword'] = false;

/* Configuración extra */
$cfg['DefaultLang'] = 'es';
$cfg['ServerDefault'] = 1;
?>
