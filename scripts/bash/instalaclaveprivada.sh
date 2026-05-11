#!/bin/bash
# Uso: ./instalaclaveprivada.sh IP_SERVIDOR_CLAVES USUARIO_REMOTO

if [ $# -ne 2 ]
then
    echo "Error: forma de uso:"
    echo "$0 IP_servidor_claves usuario_remoto"
else
    # Aseguramos que existe el directorio .ssh local
    mkdir -p ~/.ssh
    chmod 700 ~/.ssh

    # Descargamos la clave privada (la identidad)
    scp $2@$1:~/.ssh/id_ssh_acceso ~/.ssh/id_ssh_acceso

    # Ajustamos permisos: la clave privada DEBE ser 600 o SSH la rechazará
    chmod 600 ~/.ssh/id_ssh_acceso
    
    echo "Operación finalizada: Clave privada instalada en ~/.ssh/"
fi