FROM ubuntu:24.04

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update && apt-get install -y \
    apache2 \
    php \
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

RUN useradd -m -s /bin/bash administrador && \
    echo "administrador:administrador" | chpasswd && \
    usermod -aG sudo administrador

RUN mkdir -p /var/run/sshd /var/run/vsftpd/empty /var/log/supervisor

COPY inicio.sh /opt/inicio.sh
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

RUN chmod +x /opt/inicio.sh

EXPOSE 22 21 80 40016-40020

ENTRYPOINT ["/opt/inicio.sh"]
