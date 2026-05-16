$domainPath = "DC=asi-242v,DC=cifpaviles,DC=com"
$baseOU = "OU=moodleroles,$domainPath"

# creación de OUs
if (!(Get-ADOrganizationalUnit -Filter "Name -eq 'moodleroles'" -SearchBase $domainPath)) {
    New-ADOrganizationalUnit -Name "moodleroles" -Path $domainPath
}
New-ADOrganizationalUnit -Name "adminsmoodle" -Path $baseOU -ErrorAction SilentlyContinue
New-ADOrganizationalUnit -Name "tecnicosmoodle" -Path $baseOU -ErrorAction SilentlyContinue
New-ADOrganizationalUnit -Name "creadoresmoodle" -Path $baseOU -ErrorAction SilentlyContinue

# Creamos cursos con mismos cn pero samaccountnames distintos y metadatos corregidos
foreach ($n in 1..3) {
    $longName = "Documentos N$n"
    $resumen = "Documentación para técnicos de ASI-242V, Nivel $n"

    # Grupo para ADMINS
    New-ADGroup -Name "DOCSN$n" -sAMAccountName "DOCSN$n`_A" -GroupCategory Security -GroupScope Global -Path "OU=adminsmoodle,$baseOU" -Description $longName -OtherAttributes @{info=$resumen} -ErrorAction SilentlyContinue
    
    # Grupo para TECNICOS
    New-ADGroup -Name "DOCSN$n" -sAMAccountName "DOCSN$n`_T" -GroupCategory Security -GroupScope Global -Path "OU=tecnicosmoodle,$baseOU" -Description $longName -OtherAttributes @{info=$resumen} -ErrorAction SilentlyContinue
}

# Creación del grupo de creadores
New-ADGroup -Name "creadoresmoodle" -sAMAccountName "creadoresmoodle" -GroupCategory Security -GroupScope Global -Path "OU=creadoresmoodle,$baseOU" -Description "Creadores de Cursos Moodle" -ErrorAction SilentlyContinue

# Asignamos usuarios a grupos de cursos
$admins = "alvaro", "manu", "victor"
foreach ($n in 1..3) {
    Add-ADGroupMember -Identity "DOCSN$n`_A" -Members $admins -ErrorAction SilentlyContinue
}

Add-ADGroupMember -Identity "DOCSN1_T" -Members "luisma", "toni", "ruben", "guillermo", "luis", "olga" -ErrorAction SilentlyContinue
Add-ADGroupMember -Identity "DOCSN2_T" -Members "ruben", "guillermo", "luis", "olga" -ErrorAction SilentlyContinue
Add-ADGroupMember -Identity "DOCSN3_T" -Members "luis", "olga" -ErrorAction SilentlyContinue

# Asignamos usuarios al grupo de creadores (Admins + Técnicos N3)
Add-ADGroupMember -Identity "creadoresmoodle" -Members $admins -ErrorAction SilentlyContinue
Add-ADGroupMember -Identity "creadoresmoodle" -Members "luis", "olga" -ErrorAction SilentlyContinue