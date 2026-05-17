#!/bin/bash
HOST_UID=$(stat -c '%u' /var/www)
HOST_GID=$(stat -c '%g' /var/www)
usermod -u "$HOST_UID" administrador
groupmod -g "$HOST_GID" administrador 2>/dev/null || true
chown -R administrador:www-data /var/www
chmod -R 775 /var/www
a2enmod remoteip rewrite
a2enconf phpmyadmin remoteip
source /etc/apache2/envvars
exec /usr/bin/supervisord -n -c /etc/supervisor/conf.d/supervisord.conf
