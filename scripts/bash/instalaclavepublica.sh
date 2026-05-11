#!/bin/bash
# Uso: ./instalaclavepublica.sh IP_SERVIDOR_CLAVES USUARIO_REMOTO

# En primer lugar, se hace que si no se utilizan dos argumentos salte un mensaje de error explicando como ejecutar el script
if [ $# -ne 2 ]
then
    echo "Error: forma de uso:"
    echo "$0 IP_servidor_claves usuario_remoto"
else
    # Dentro del else creamos el directorio (el parámetro -p hace que no pase nada si el directorio ya existe) y asignamos permisos
    mkdir -p ~/.ssh
    chmod 700 ~/.ssh

    # Utilizamos el comando scp para transferir el archivo a /tmp, donde se almacenan archivos temporales
    # $1 es la IP del servidor y $2 es el usuario en ese servidor
    scp $2@$1:~/.ssh/id_ssh_acceso.pub /tmp/key_transfer.pub

    # Llevamos la clave a /.ssh/authorized_keys en el directorio home del usuario que ejecuta el script y otorgamos permisos
    cat /tmp/key_transfer.pub >> ~/.ssh/authorized_keys
    chmod 600 ~/.ssh/authorized_keys

    # Borramos el archivo temporal de /tmp
    rm /tmp/key_transfer.pub
    echo "Operación finalizada: Clave pública autorizada."
fi