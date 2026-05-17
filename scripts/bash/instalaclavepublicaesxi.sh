#!/bin/bash
# Uso: ./instalaclavepublicaesxi.sh IP_ESXi

IP_ESXi=$1
R_ORIGEN="/home/administrador/.ssh/242v.pub"
USUARIO_ESXi="root"

if [ $# -ne 1 ]; then
    echo "Error: forma de uso: $0 IP_ESXi"
    exit 1
fi

echo "--- Habilitando Algoritmos Modernos (ED25519) en ESXi ($IP_ESXi) ---"
CLAVE_PUB=$(cat "$R_ORIGEN" | awk '{print $1, $2}')

ssh $USUARIO_ESXi@$IP_ESXi << EOF
    # 1. Asegurar la llave en la ruta oficial
    mkdir -p /etc/ssh/keys-root
    echo "$CLAVE_PUB" > /etc/ssh/keys-root/authorized_keys
    chmod 700 /etc/ssh/keys-root
    chmod 600 /etc/ssh/keys-root/authorized_keys

    # 2. Configurar el demonio SSH para aceptar ED25519
    # Primero limpiamos si ya existen las líneas
    sed -i '/PubkeyAcceptedKeyTypes/d' /etc/ssh/sshd_config

    # Añadimos la compatibilidad explícita para ED25519 y RSA
    echo "PubkeyAcceptedKeyTypes +ssh-ed25519,ssh-rsa" >> /etc/ssh/sshd_config

    # Aseguramos que el login de root y llaves estén activos
    sed -i 's/.*PermitRootLogin.*/PermitRootLogin yes/g' /etc/ssh/sshd_config
    sed -i 's/.*PubkeyAuthentication.*/PubkeyAuthentication yes/g' /etc/ssh/sshd_config

    # 3. Reiniciar el servicio
    /etc/init.d/SSH restart
EOF

echo "--- Configuración aplicada ---"
echo "Prueba ahora la conexión normal:"
echo "ssh -i ~/.ssh/242v root@$IP_ESXi"
