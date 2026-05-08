Write-Host @"
############################################################
#                                                          #
#         GENERADOR DE INFRAESTRUCTURA DE AD               #
#            By: Álvaro Remis Busto                        #
#                                                          #
#    Propósito: Despliegue de OUs, Usuarios, Grupos y      #
#               Jerarquías de Seguridad en el Dominio      #
#                                                          #
############################################################
"@ -ForegroundColor Cyan

Import-Module ActiveDirectory

# --- CONFIGURACIÓN ---
$csvPath = "ruta en la que se encuentre el csv"
$dominio = "asi-242v.cifpaviles.com" 
$passwordSecret = ConvertTo-SecureString "contraseña" -AsPlainText -Force  #Introduzca su contraseña deseada para los usuarios creados
$usuarios = Import-csv -Path $csvPath -Delimiter ","

Write-Host "Iniciando despliegue de infraestructura AD para $dominio..." -ForegroundColor Cyan

# --- FASE 1: CREACIÓN DE OUs, USUARIOS Y GRUPOS HOJA ---
# Con el foreach conseguimos que todas las acciones del try se ejecuten para cada fila del csv.
# El catch, en caso de error en el try, nos devuelve el error que devuelve Windows para el usuario específico.
foreach ($u in $usuarios) {
    try {
        $rutaUO = $u.UO.Trim()
        
        # 1.1 Crear OUs recursivamente
        # Buscamos dentro del csv la columna UO y eliminamos espacios con Trim. Con $u definimos cada fila y con UO la columna, filtrando la busqueda por casillas
        # Guardamos la casilla en la variable ruta OU (toda la ruta de OUs) y despues guardamos cada OU de la ruta en partes usando las comas del csv como separador.
        # Creamos OUs convirtiendo los datos en un array inverso. 
        # Buscamos match con un -Like. Si la OU no existe en nuestro AD se crea una OU con el nombre de la parte de la ruta despues del simbolo "=". Esto se repetirá
        # dentro de cada UO separada por comas con el bucle foreach.
        $partes = $rutaUO.Split(',')
        [array]::Reverse($partes)
        $dnActual = ""
        foreach ($parte in $partes) {
            $dnActual = if ($dnActual -eq "") { $parte.Trim() } else { $parte.Trim() + "," + $dnActual }
            if ($parte -like "OU=*" -and -not (Get-ADOrganizationalUnit -Identity $dnActual -ErrorAction SilentlyContinue)) {
                $nombreOU = $parte.Split('=')[1]
                $padreDN = $dnActual.Substring($parte.Length + 1)
                New-ADOrganizationalUnit -Name $nombreOU -Path $padreDN
            }
        }

        # 1.2 Limpieza de usuario previo
        # Utilizamos la misma lógica de busqueda de casillas en el csv pero con la columna usuarios y volvemos a eliminar espacios con Trim y lo almacenamos en samAccount
        # Si samAccount coincide con el nombre de un usuario del AC se elimina el usuario.

        $samAccount = $u.usuario.Trim()
        if (Get-ADUser -Filter "SamAccountName -eq '$samAccount'" -ErrorAction SilentlyContinue) {
            Remove-ADUser -Identity $samAccount -Confirm:$false
        }

        # 1.3 Creación del usuario
        # Creamos un usuario por cada fila del csv. Usamos la variable dominio para completar el UserPrincipalName tras la arroba. Usamos la variable pawwordsecret.
        # Habilitamos el usuario
        New-ADUser -Name "$($u.Nombre.Trim()) $($u.Apellido.Trim())" `
                    -SamAccountName $samAccount `
                    -UserPrincipalName "$samAccount@$dominio" `
                    -Path $rutaUO `
                    -AccountPassword $passwordSecret `
                    -Enabled $true
        
        # 1.4 Crear el grupo de la OU (gestion, N1, N2, N3, admins)
        # Filtramos y usando la logica de filtrado de OUs creamos grupos con los mismos nombres que las OU dentro de las propias OU. Almacenamos el nombre del grupo
        # en nombreGrupoHoja. Tras el primer split, con [0] indicamos que solo se seleccione el primer elemento de la lista. Con [1] tras el segundo nos aseguramos de que
        # solo se use el primer valor despues del igual de ese elemento para nombrar el grupo. Si existe un grupo con el mismo nombre el script no para de ejecutarse.
        # Se crea un grupo nuevo con el nombre almacenado en nombreGrupoHoja y se le añade un miembro con el valor almacenado en samAccount
        $nombreGrupoHoja = ($rutaUO.Split(',')[0]).Split('=')[1]
        if (-not (Get-ADGroup -Filter "Name -eq '$nombreGrupoHoja'" -ErrorAction SilentlyContinue)) {
            New-ADGroup -Name $nombreGrupoHoja -GroupCategory Security -GroupScope Global -Path $rutaUO
        }
        Add-ADGroupMember -Identity $nombreGrupoHoja -Members $samAccount
        
        Write-Host "[OK] Usuario $samAccount procesado en $nombreGrupoHoja" -ForegroundColor DarkMagenta
    } 
    catch { Write-Host "[ERR] $($u.usuario): $($_.Exception.Message)" -ForegroundColor Red }
}

# --- FASE 2: JERARQUÍA DE GRUPOS (MIEMBRO DE) ---
Write-Host "`nConfigurando jerarquía de grupos estructurales..." -ForegroundColor Cyan

# Creamos un array de hash tables con estructura clave = valor con tres claves para cada objeto. Los arrays van entre () y las hash tables que guardan los conjuntos
# clave = valor van emtre {}. Padre es el nombre del grupo dentro del que estarán los demás. Hijos serán los grupos subordinados.
# Path serña la ruta exacta (DN) donde se crearán los grupos.

$jerarquia = @(
    # Padre -> Hijos
    @{ Padre = "tecnicos"; Hijos = @("N1", "N2", "N3"); Path = "OU=tecnicos,OU=empresa,OU=usuarios,DC=asi-242v,DC=cifpaviles,DC=com" },
    @{ Padre = "empresa";  Hijos = @("gestion", "tecnicos", "admins"); Path = "OU=empresa,OU=usuarios,DC=asi-242v,DC=cifpaviles,DC=com" },
    @{ Padre = "usuariosASI-242v"; Hijos = @("empresa", "clientes"); Path = "OU=usuarios,DC=asi-242v,DC=cifpaviles,DC=com" },
    @{ Padre = "adminsEstaciones"; Hijos = @("N2"); Path = "OU=admins,OU=empresa,OU=usuarios,DC=asi-242v,DC=cifpaviles,DC=com" }
)

# Dentro del foreach, para cada objeto en la hash table, se crean grupos nuevos si no existen ya en nuestro AD usando la variable $item con la clave Padre
# En el foreach, para cada objeto Hijo de la hash table, si no existe se crea el grupo hijo dentro de la OU usuarios de nuestro AD. Finalmente se añade 
# un miembro nuevo al grupo padre utilizando la variable hijo
foreach ($item in $jerarquia) {
    # Crear padre si no existe
    if (-not (Get-ADGroup -Filter "Name -eq '$($item.Padre)'" -ErrorAction SilentlyContinue)) {
        New-ADGroup -Name $item.Padre -GroupCategory Security -GroupScope Global -Path $item.Path
    }
    # Anidar hijos
    foreach ($hijo in $item.Hijos) {
        if (-not (Get-ADGroup -Filter "Name -eq '$hijo'" -ErrorAction SilentlyContinue)) {
             $cPath = "OU=$hijo,OU=usuarios,DC=asi-242v,DC=cifpaviles,DC=com"
             New-ADGroup -Name $hijo -GroupCategory Security -GroupScope Global -Path $cPath
        }
        Add-ADGroupMember -Identity $item.Padre -Members $hijo -ErrorAction SilentlyContinue
    }
}

# --- FASE 3: VINCULACIÓN CON GRUPOS DE SISTEMA (ADMINS) ---
# Para añadir grupos creados por nosotros a grupos propios del AD utilizaremos IDs internos de la estructura de Windows Server. Un SID es un Security Identifier.
# Creamos otro array de hash tables con valores para nuestros nuevos grupos y variables para las SID que nos interesan.
Write-Host "`nAsignando roles de administración del sistema para 'admins'..." -ForegroundColor Magenta
$domainSID = (Get-ADDomain).DomainSID.Value

$vincSist = @(
    @{ Local = "usuariosASI-242v"; SistSID = "$domainSID-513" }, # Usuarios del dominio
    @{ Local = "admins";           SistSID = "$domainSID-512" }, # Admins. del dominio
    @{ Local = "admins";           SistSID = "$domainSID-518" }, # Admins. del esquema
    @{ Local = "admins";           SistSID = "$domainSID-519" }  # Admins. de empresas
)

# Dentro del foreach, para cada elemento de la hash table, seleccionamos la identidad del grupo asociado al SID de Windows (SistID) e introducimos en el mismo nuestros
# grupos de AD (Local)

foreach ($v in $vincSist) {
    try {
        $grupoSist = Get-ADGroup -Identity $v.SistSID
        Add-ADGroupMember -Identity $grupoSist -Members $v.Local -ErrorAction Stop
        Write-Host " [+] $($v.Local) -> $($grupoSist.Name)" -ForegroundColor Green
    } catch { Write-Host " [!] $($v.Local): $($_.Exception.Message)" -ForegroundColor Yellow }
}

Write-Host "`n¡ESTRUCTURA COMPLETADA AL 100%!" -BackgroundColor White -ForegroundColor Black

#.csv Usuarios
# Nombre,Apellido,usuario,UO,GrupoDirecto
# Andrés,Marina,andres,"OU=gestion,OU=empresa,OU=usuarios,DC=asi-242v,DC=cifpaviles,DC=com",gestion
# Jose Manuel,Arabia,manuel,"OU=gestion,OU=empresa,OU=usuarios,DC=asi-242v,DC=cifpaviles,DC=com",gestion
# Luisma,Alvarez,luisma,"OU=N1,OU=tecnicos,OU=empresa,OU=usuarios,DC=asi-242v,DC=cifpaviles,DC=com",N1
# Antonio,Matachana,toni,"OU=N1,OU=tecnicos,OU=empresa,OU=usuarios,DC=asi-242v,DC=cifpaviles,DC=com",N1
# Guillermo,González,guillermo,"OU=N2,OU=tecnicos,OU=empresa,OU=usuarios,DC=asi-242v,DC=cifpaviles,DC=com",N2
# Rubén,Pardiño,ruben,"OU=N2,OU=tecnicos,OU=empresa,OU=usuarios,DC=asi-242v,DC=cifpaviles,DC=com",N2
# Luis,Lestón,luis,"OU=N3,OU=tecnicos,OU=empresa,OU=usuarios,DC=asi-242v,DC=cifpaviles,DC=com",N3
# Olga,Hevia,olga,"OU=N3,OU=tecnicos,OU=empresa,OU=usuarios,DC=asi-242v,DC=cifpaviles,DC=com",N3
# Manuel,Varo,manu,"OU=admins,OU=empresa,OU=usuarios,DC=asi-242v,DC=cifpaviles,DC=com",admins
# Victor Manuel,Rubio,victor,"OU=admins,OU=empresa,OU=usuarios,DC=asi-242v,DC=cifpaviles,DC=com",admins
# Álvaro,Remis,alvaro,"OU=admins,OU=empresa,OU=usuarios,DC=asi-242v,DC=cifpaviles,DC=com",admins