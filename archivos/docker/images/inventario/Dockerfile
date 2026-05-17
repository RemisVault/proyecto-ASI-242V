FROM ubuntu:24.04

ENV DEBIAN_FRONTEND=noninteractive

# 1. Instalación de paquetes base, PHP y dependencias de compilación
RUN apt-get update && apt-get install -y \
    apache2 \
    php \
    php-dev \
    php-pear \
    php-ldap \
    libaio1t64 \
    libyaml-dev \
    wget \
    unzip \
    build-essential \
    libapache2-mod-php \
    vsftpd \
    supervisor \
    openssh-server \
    rsyslog \
    iproute2 \
    net-tools \
    sudo \
    nano \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# FIX: Enlace simbólico para que Oracle encuentre libaio.so.1
RUN ln -s /usr/lib/x86_64-linux-gnu/libaio.so.1t64 /usr/lib/x86_64-linux-gnu/libaio.so.1

# 2. Configuración del Oracle Instant Client
RUN mkdir -p /opt/oracle
WORKDIR /opt/oracle

RUN wget https://download.oracle.com/otn_software/linux/instantclient/211000/instantclient-basic-linux.x64-21.1.0.0.0.zip && \
    wget https://download.oracle.com/otn_software/linux/instantclient/211000/instantclient-sdk-linux.x64-21.1.0.0.0.zip && \
    unzip instantclient-basic-linux.x64-21.1.0.0.0.zip && \
    unzip instantclient-sdk-linux.x64-21.1.0.0.0.zip && \
    rm *.zip

ENV LD_LIBRARY_PATH=/opt/oracle/instantclient_21_1
RUN echo /opt/oracle/instantclient_21_1 > /etc/ld.so.conf.d/oracle-instantclient.conf && ldconfig

# 3. Instalación de OCI8 y extensión YAML nativa
RUN echo "instantclient,/opt/oracle/instantclient_21_1" | pecl install oci8 && \
    pecl install yaml && \
    echo "extension=oci8.so" >> /etc/php/8.3/apache2/php.ini && \
    echo "extension=oci8.so" >> /etc/php/8.3/cli/php.ini && \
    echo "extension=yaml.so" >> /etc/php/8.3/apache2/php.ini && \
    echo "extension=yaml.so" >> /etc/php/8.3/cli/php.ini

# 4. Estructura de directorios y usuarios
RUN useradd -m -s /bin/bash administrador && \
    echo "administrador:administrador" | chpasswd && \
    usermod -aG sudo administrador

# Creamos la carpeta privada para conexiones y sesiones seguras
RUN mkdir -p /var/www/privado && \
    chown -R www-data:www-data /var/www/privado && \
    chmod 750 /var/www/privado

COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

RUN mkdir -p /var/run/sshd /var/run/vsftpd/empty && \
    chmod 755 /var/run/vsftpd/empty

# Establecemos el punto de entrada al DocumentRoot
WORKDIR /var/www/html

EXPOSE 22 21 80 40011-40015

CMD ["/usr/bin/supervisord"]
