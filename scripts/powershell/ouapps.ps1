# 1. Crear la OU apps
$targetOU = New-ADOrganizationalUnit -Name "apps" -Path "OU=usuarios,DC=asi-242v,DC=cifpaviles,DC=com" -PassThru
$securePass = ConvertTo-SecureString "Temporal242v!" -AsPlainText -Force

# 2. Crear los 3 usuarios directamente dentro de ella
"moodle", "vcenter", "wordpress" | ForEach-Object {
    New-ADUser -Name $_ -SamAccountName $_ -Path $targetOU.DistinguishedName -AccountPassword $securePass -ChangePasswordAtLogon $false -Enabled $true
}