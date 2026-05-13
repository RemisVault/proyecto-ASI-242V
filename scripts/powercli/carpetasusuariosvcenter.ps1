# --- CONFIGURACIÓN DE VARIABLES ---
$datacenter = "ASI-242V"
$dominio = "ASI-242V" 
$rol = "usuario"
$rol_plantillas = "desplegar_desde_template"

# --- EJECUCIÓN ---
# Conexión al Datacenter
$dc = Get-Datacenter -Name $datacenter 

# Obtenemos la carpeta raíz de VMs (donde colgamos todo)
$vmFolder = Get-Folder -Location $dc -Name "vm"

# 1. CREACIÓN DE CARPETA TEMPLATES_PUBLICAS
Write-Host "Creando carpeta de plantillas públicas..." -ForegroundColor Yellow
$folderTpl = New-Folder -Name "templates_publicas" -Location $vmFolder

# Asignación de permiso al grupo global para desplegar plantillas
try {
    Write-Host "  > Asignando rol '$rol_plantillas' al grupo general en la carpeta de plantillas" -ForegroundColor Cyan
    New-VIPermission -Entity $folderTpl -Principal "$dominio\usuarios de vcenter" -Role $rol_plantillas -Propagate $true -ErrorAction Stop
}
catch {
    Write-Warning "  ! No se pudo asignar permiso en templates_publicas. Revisa si el grupo o el rol existen."
}

# 2. ESTRUCTURA DE CARPETAS DE USUARIOS (HV01 y HV02)
$gruposRaiz = @("usuariosHV01-242V", "usuariosHV02-242V")

foreach ($grupoRaiz in $gruposRaiz) {
    Write-Host "`nCreando estructura para: $grupoRaiz" -ForegroundColor Yellow
    
    # Crear carpeta de nivel superior (ej. usuariosHV01-242V)
    $parentFolder = New-Folder -Name $grupoRaiz -Location $vmFolder
    
    # Crear las 6 subcarpetas y asignar permisos individuales
    for ($i = 1; $i -le 6; $i++) {
        $folderName = "$($grupoRaiz).usu$i"
        $subFolder = New-Folder -Name $folderName -Location $parentFolder
        
        # Formamos el nombre del grupo de AD para el permiso (ej: ASI-242V\usuariosHV01-242V.usu1)
        $groupPrincipal = "$dominio\$folderName"
        
        try {
            Write-Host "  > Creando $folderName y asignando rol '$rol' a $groupPrincipal" -ForegroundColor Cyan
            
            # Aplicar el permiso individual en cada subcarpeta
            New-VIPermission -Entity $subFolder -Principal $groupPrincipal -Role $rol -Propagate $true -ErrorAction Stop
        }
        catch {
            Write-Warning "  ! No se pudo asignar permiso a $groupPrincipal."
        }
    }
}

Write-Host "`nEstructura completada" -ForegroundColor White -BackgroundColor DarkGreen