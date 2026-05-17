<?php
/**
 * Configuración de phpMyAdmin para el Frontend de WordPress
 */
$i = 0;

/* Incremento de servidor */
$i++;

/* Nombre que aparecerá en el panel */
$cfg['Servers'][$i]['verbose'] = 'WordPress Database (MySQL)';

/* Host: Usamos el nombre del servicio definido en el docker-compose */
$cfg['Servers'][$i]['host'] = 'wordpress_backend'; 

/* Puerto estándar */
$cfg['Servers'][$i]['port'] = '3306';

/* Forzamos el uso de TCP para evitar errores de socket local */
$cfg['Servers'][$i]['connect_type'] = 'tcp';

/* Método de autenticación por cookies (solicita usuario/pass) */
$cfg['Servers'][$i]['auth_type'] = 'cookie';

/* No permitir entrar sin contraseña */
$cfg['Servers'][$i]['AllowNoPassword'] = false;

/* Configuración de idioma y tema (Opcional) */
$cfg['DefaultLang'] = 'es';
$cfg['ServerDefault'] = 1;
?>
