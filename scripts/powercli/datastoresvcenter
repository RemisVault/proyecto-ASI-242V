# --- variables ---
$dominio_nombre_corto = "ASI-242V"
$host_inicial = 1
$host_final = 2

# Roles (Asegúrate de que estén creados en vCenter)
$rol_publico = "explorador_de_datastore"
$rol_host = "crear_en_datastore"

# --- EJECUCIÓN ---

# datos
$carpeta_publico = "Publico"
$grupo_vcenter = "$dominio_nombre_corto\usuarios de vcenter"

Write-Host "Configurando carpeta $carpeta_publico..." -ForegroundColor Magenta

# Crear carpeta en la vista de datastores
$folderPub = Get-Folder -Name "datastore" | New-Folder -Name $carpeta_publico

# Asignar permisos al grupo global
New-VIPermission -Entity $folderPub -Principal $grupo_vcenter -Role $rol_publico -Propagate $true

# Mover el datastore "Datos" dentro (como en image_3870f6.png)
Move-Datastore -Datastore "Datos" -Destination $folderPub


# carpetas y mover datastores
for ($j = $host_inicial; $j -le $host_final; $j++)
{
    $num_host = "0$j" # Genera 01, 02
    $nombre_carpeta = "HV$num_host-242V"
    $grupo_dominio = "$dominio_nombre_corto\usuariosHV$num_host-242V"
    
    Write-Host "Configurando carpeta: $nombre_carpeta" -ForegroundColor Yellow
    
    # Crear carpeta de host
    $folderHost = Get-Folder -Name "datastore" | New-Folder -Name $nombre_carpeta
    
    # Asignar rol al grupo del host (como en image_3870f6.png)
    New-VIPermission -Entity $folderHost -Principal $grupo_dominio -Role $rol_host -Propagate $true

    # Mover los datastores locales (a y b) a su carpeta
    # Ejemplo: Mueve Datastore01a-242V y Datastore01b-242V a HV01-242V
    Get-Datastore -Name "Datastore$($num_host)*-242V" | Move-Datastore -Destination $folderHost
}

Write-Host "`nVista de Almacenamiento organizada correctamente." -ForegroundColor Green