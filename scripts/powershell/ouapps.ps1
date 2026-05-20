# 1. Verificar si la OU "apps" ya existe, si no, crearla
$ouPath = "OU=usuarios,DC=asi-242v,DC=cifpaviles,DC=com"
$targetOU = Get-ADOrganizationalUnit -Filter "Name -eq 'apps'" -SearchBase $ouPath -ErrorAction SilentlyContinue

if ($null -eq $targetOU) {
    $targetOU = New-ADOrganizationalUnit -Name "apps" -Path $ouPath -PassThru
}

# Configurar la contraseña común de forma segura
$securePass = ConvertTo-SecureString "Temporal242v!" -AsPlainText -Force

# 2. Lista de usuarios a procesar (añadido 'inventario')
$usuarios = @("moodle", "vcenter", "wordpress", "inventario")

# 3. Recorrer la lista y crear solo los que falten
foreach ($user in $usuarios) {
    # Comprobar si el usuario ya existe
    $existe = Get-ADUser -Filter "SamAccountName -eq '$user'" -ErrorAction SilentlyContinue

    if ($null -eq $existe) {
        # Si no existe, se crea dentro de la OU apps
        New-ADUser -Name $user `
                   -SamAccountName $user `
                   -Path $targetOU.DistinguishedName `
                   -AccountPassword $securePass `
                   -ChangePasswordAtLogon $false `
                   -Enabled $true
    }
}

# 4. Mostrar el resultado final filtrado con lo solicitado
Get-ADUser -Filter "SearchScope -eq 'Subtree'" -SearchBase "OU=apps,$ouPath" | Select-Object SamAccountName, Name, DistinguishedName