FROM ubuntu:24.04

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update && apt-get install -y \
    apache2 \
    php \
    libapache2-mod-php \
    php-mysql \
    php-xml \
    php-gd \
    php-curl \
    php-zip \
    php-intl \
    php-mbstring \
    php-soap \
    php-ldap \
    php-bcmath \
    phpmyadmin \
    vsftpd \
    supervisor \
    openssh-server \
    iproute2 \
    net-tools \
    git \
    sudo \
    nano \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

RUN ln -s /etc/phpmyadmin/apache.conf /etc/apache2/conf-available/phpmyadmin.conf && \
    a2enconf phpmyadmin.conf

RUN a2enmod remoteip
RUN touch /etc/apache2/conf-available/remoteip.conf && a2enconf remoteip

RUN sed -i 's/;max_input_vars = 1000/max_input_vars = 5000/g' /etc/php/*/apache2/php.ini

RUN useradd -m -s /bin/bash administrador && \
    echo "administrador:administrador" | chpasswd && \
    usermod -aG sudo administrador

COPY inicio.sh /opt/inicio.sh
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf
RUN chmod +x /opt/inicio.sh && mkdir -p /var/run/sshd /var/run/vsftpd/empty

EXPOSE 22 21 80 40006-40010

CMD ["/usr/bin/supervisord"]
