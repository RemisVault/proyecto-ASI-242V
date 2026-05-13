$dominio="DC=asi-242V, DC=cifpaviles, DC=com"

# Crear la OU virtualizacion antes de crear los grupos
New-ADOrganizationalUnit -Name "virtualizacion" -Path "OU=empresa, OU=usuarios, $dominio"

$usuario_inicial=1
$usuario_final=6

$host_inicial=1
$host_final=2

# ---------------

$pathOU = "OU=virtualizacion, OU=empresa, OU=usuarios, $dominio"

# Crear grupos principales y añadir miembros existentes
New-ADGroup "admins de vcenter" -GroupScope Global -GroupCategory Security -Path $pathOU
Add-ADGroupMember -Identity "admins de vcenter" -Members admins

New-ADGroup "usuarios de vcenter" -GroupScope Global -GroupCategory Security -Path $pathOU
Add-ADGroupMember -Identity "usuarios de vcenter" -Members empresa

# Bucle para crear la estructura de hosts y usuarios por host
for ($j=$host_inicial;$j -le $host_final; $j++)
{
    # host $j
    Write-Host "Creando grupo de host $j"
    New-ADGroup "usuariosHV0$j-242V" -GroupScope Global -GroupCategory Security -Path $pathOU

    for ($i=$usuario_inicial;$i -le $usuario_final;$i++)
    {
        Write-Host "Creando grupo de usuario $i en host $j"
        $nombreGrupoUsuario = "usuariosHV0$j-242V.usu$i"
        
        New-ADGroup $nombreGrupoUsuario -GroupScope Global -GroupCategory Security -Path $pathOU
        
        # Añadir el grupo de usuario al grupo del host correspondiente
        Add-ADGroupMember -Identity "usuariosHV0$j-242V" -Members $nombreGrupoUsuario
    }
}