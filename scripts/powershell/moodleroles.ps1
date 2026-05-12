$domainPath = "DC=asi-242v,DC=cifpaviles,DC=com"
$baseOU = "OU=moodleroles,$domainPath"

# creación de OUs
if (!(Get-ADOrganizationalUnit -Filter "Name -eq 'moodleroles'" -SearchBase $domainPath)) {
    New-ADOrganizationalUnit -Name "moodleroles" -Path $domainPath
}
New-ADOrganizationalUnit -Name "adminsmoodle" -Path $baseOU -ErrorAction SilentlyContinue
New-ADOrganizationalUnit -Name "tecnicosmoodle" -Path $baseOU -ErrorAction SilentlyContinue

# Creamos cursos con mismos cn pero samaccountnames distintos

foreach ($n in 1..3) {
    # Grupo para ADMINS
    New-ADGroup -Name "DOCSN$n" -sAMAccountName "DOCSN$n`_A" -GroupCategory Security -GroupScope Global -Path "OU=adminsmoodle,$baseOU"
    
    # Grupo para TECNICOS
    New-ADGroup -Name "DOCSN$n" -sAMAccountName "DOCSN$n`_T" -GroupCategory Security -GroupScope Global -Path "OU=tecnicosmoodle,$baseOU"
}

#asignamos usuarios a grupos en funcion del samaccountname (DOCSN$n`_A, DOCSN$n`_T)

$admins = "alvaro", "manu", "victor"
foreach ($n in 1..3) {
    Add-ADGroupMember -Identity "DOCSN$n`_A" -Members $admins
}

Add-ADGroupMember -Identity "DOCSN1_T" -Members "luisma", "toni", "ruben", "guillermo", "luis", "olga"
Add-ADGroupMember -Identity "DOCSN2_T" -Members "ruben", "guillermo", "luis", "olga"
Add-ADGroupMember -Identity "DOCSN3_T" -Members "luis", "olga"