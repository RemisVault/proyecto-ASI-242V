## Resumen de Implementaciones y Configuraciones

### 1. Arquitectura de Red y Enrutamiento (On-Premises & Cloud)
* **Router Local (ROUTER01-242V):** Servidor perimetral basado en Zentyal 6.2 (Ubuntu 18.04 LTS) administrando múltiples interfaces de red (DMZ, Servidores Internos, Estaciones, Gestión).
* **Políticas de Red:** Configuración avanzada de enrutamiento, NAT, reglas de cortafuegos estrictas para filtrado de paquetes desde redes externas hacia el área interna, y redirección selectiva de puertos.
* **Servicio DHCP:** Servidor DHCP centralizado (`/etc/dhcp/dhcpd.conf`) con reservas de direcciones IP estáticas mediante asociación MAC para toda la infraestructura de servidores y rangos dinámicos para los clientes.
* **Servicios VPN:** Implementación de servidor OpenVPN de acceso remoto para conexiones seguras de clientes externos (`EXTERNA01`), validando tablas de enrutamiento y asignación de IPs virtuales.

### 2. Servicios de Directorio y Control de Accesos (Active Directory)
* **Controladores de Dominio:** Despliegue en alta disponibilidad con `DC01-242V` (Windows Server 2019 Standard) y `DC02-242V` (Windows Server 2022 Standard).
* **Diseño de Unidades Organizativas (UOs):** Estructuración jerárquica de UOs para organizar `Equipos`, `Usuarios` (`empresa`, `gestion`, `tecnicos`, `admins`, `clientes`, etc.) y roles de plataformas.
* **Políticas de Grupo (GPOs):** Creación e implantación de directivas de seguridad corporativas, incluyendo:
    * Permitir contraseñas simples (entorno de pruebas).
    * Sincronización y configuración del servicio NTP del dominio.
    * Habilitación de administración remota y Escritorio Remoto (RDP).
    * Establecimiento de mensajes de inicio de sesión obligatorios.
    * Configuración automática de administradores locales en estaciones de trabajo.
    * Restricciones de entorno para estaciones y usuarios.
* **Entorno de Usuario:** Configuración de perfiles móviles para el grupo de `gestión` y mapeo dinámico de unidades de red corporativas (`Z:`).

### 3. Almacenamiento Centralizado y Copias de Seguridad
* **Servidor NAS (FILER01-242V):** Despliegue basado en TrueNAS administrado en la red de gestión.
* **Protocolos de Red:** Compartición de recursos mediante protocolos SMB y NFS.
* **Estructura de Datos:** Creación de volúmenes de producción destinados a instaladores, repositorios de software, almacenamiento de perfiles móviles, carpetas particulares de red (`usuarios$`) y Datastores compartidos por NFS para la infraestructura de virtualización.
* **Resiliencia:** Configuración y gestión automatizada de snapshots para la protección contra pérdida de datos.

### 4. Virtualización e Infraestructura Local (vSphere)
* **Hipervisores ESXi:** Despliegue de dos nodos físicos `HV01-242V` y `HV02-242V` bajo VMware ESXi 6.7 con acceso SSH y vSphere Agent.
* **vCenter Server:** Integración de la consola centralizada (`VCENTER01-242V`) con Active Directory como origen de identidad LDAP.
* **Delegación de Permisos:** Creación de roles personalizados y permisos globales (`crear_en_datastore`, `desplegar_desde_template`, `explorador_de_datastore`, `usuario_networking`) mapeados sobre estructuras de carpetas lógicas y de almacenamiento para el grupo de `tecnicos`.

### 5. Servidor Web Local y Dockerización (DMZ - LAMP01-242V)
* **Infraestructura Base:** Servidor físico Ubuntu Server 24.04 LTS configurado con un volumen en **RAID 5 por software** (`mdadm`) montado en `/mnt/raid5/` y dedicado exclusivamente al almacenamiento del motor de Docker.
* **Servicios Web y Aplicaciones Dockerizadas:**
    * **Nginx Proxy Manager:** Actúa como proxy inverso centralizado, manejando los sitios virtuales de Apache, asignando certificados SSL y forzando tráfico seguro HTTPS.
    * **WordPress & Moodle:** Aplicaciones desplegadas en arquitecturas de contenedores independientes separando capas de Frontend (Apache/PHP) y Backend (MySQL/MariaDB).
    * **phpMyAdmin:** Interfaz web configurada para el mantenimiento y administración ágil de las bases de datos contenedoras.
    * **Aplicación de Inventario:** Plataforma web a medida compuesta por Frontend en PHP y backend conectado a un motor de base de datos **Oracle Database**, administrado mediante Enterprise Manager y SQL Developer.
* **Integración Moodle-AD:** Configuración avanzada de autenticación y mapeo de datos mediante el servidor LDAP de los controladores de dominio, sincronizando la creación automática de cursos, inscripciones y roles (`moodleroles`) basados en grupos de Active Directory.

### 6. Estaciones de Trabajo (Clientes)
* **Clientes Windows (WS01 / WS02):** Estaciones de trabajo basadas en Windows 10 Enterprise LTSC y Windows 11 Pro N integradas al dominio y preparadas para administración por RDP.
* **Cliente Linux (WS03-242V):** Estación basada en Ubuntu Desktop con entorno remoto xrdp, herramientas de edición (`joe`), antivirus corporativo ClamAV y **cifrado de datos de usuario (directorio /home)** montado en una partición secundaria (`sdb1`).

### 7. Extensión e Infraestructura Cloud (Microsoft Azure)
* **Redes Cloud:** Configuración de Grupos de Recursos, Redes Virtuales (VNets), direccionamiento IP público corporativo y Grupos de Seguridad de Red (NSG) en la nube.
* **Hibridación (Túnel VPN Site-to-Site):** Despliegue de un segundo enrutador perimetral Zentyal (`ROUTER02`) en Azure, estableciendo un túnel VPN permanente contra el entorno local (`ROUTER01`) y anunciando dinámicamente las subredes de ambos entornos.
* **Migración de Servicios (LAMP02-ID):** Réplica y migración completa del ecosistema dockerizado (WordPress, Moodle e Inventario con Base de Datos Oracle) hacia máquinas virtuales en la nube.
* **DNS Externo corporativo:** Configuración del servicio DNS externo mediante **Bind9** (`named.conf.options` y `named.conf.local`) con zonas directas e inversas públicas que resuelven los nombres de las aplicaciones migradas hacia las IPs públicas de Azure.

### 8. Automatización, Sincronización y Seguridad Global
* **Infraestructura SSH sin Contraseñas:** Configuración de un entorno de claves públicas/privadas automatizado mediante Scripts en Bash (`instalaclaveprivada.sh`, `instalaclavepublica.sh`, `instalaclaveprivadaesxi.sh`) para permitir al administrador acceder de manera transparente y segura desde el equipo perimetral hacia cualquier servidor o hipervisor de la red.
* **Gestión Centralizada de Credenciales:** Uso de herramientas de cifrado KeePass cuyas bases de datos son almacenadas de forma compartida en TrueNAS. Despliegue automatizado de la aplicación y precarga de bóvedas específicas mediante GPOs según el grupo del usuario de AD.
* **Sincronización Horaria (NTP):** Configuración obligatoria y auditoría horaria en toda la topología; entornos Linux validados mediante `systemd-timesyncd`, hipervisores ESXi a través de `ntpd` y los controladores de dominio apuntando al servidor horario de la red educativa.
* **Seguridad y Validación en el Código Web:**
    * **Validación Estricta:** Implementación del control de entradas en formularios HTML del lado del servidor usando expresiones regulares mediante la función `preg_match` antes de procesar solicitudes.
    * **Saneamiento de Datos:** Uso obligatorio y preventivo de la función centralizada `limpiar()` provista por el módulo de sesión para mitigar vulnerabilidades de inyección.
    * **Aislamiento del Entorno:** Ubicación de archivos sensibles de configuración y credenciales de bases de datos de manera aislada fuera de la raíz pública del servidor web (rutas `/var/www/privado/session.safe.php` y `/var/www/privado/db.connect.oracle.php`).