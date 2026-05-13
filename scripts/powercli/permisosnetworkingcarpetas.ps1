$rolNetworking = "usuario_de_networking"
$dominio = "ASI-242V"
$carpetasRaiz = "usuariosHV01-242V", "usuariosHV02-242V"

foreach ($raiz in $carpetasRaiz) {
    $folderRaiz = Get-Folder -Name $raiz -ErrorAction SilentlyContinue
    
    if ($null -eq $folderRaiz) {
        Write-Host "No se encuentra la carpeta raíz: $raiz" -ForegroundColor Red
        continue
    }

    Write-Host "`nEntrando en carpeta: $raiz" -ForegroundColor Magenta
    
    # Obtenemos las subcarpetas (usu1, usu2, etc.)
    $subcarpetas = Get-Folder -Location $folderRaiz
    
    foreach ($carpeta in $subcarpetas) {
        $nombreGrupo = "$dominio\$($carpeta.Name)"
        
        Write-Host "Configurando permisos para: $nombreGrupo" -ForegroundColor Yellow
        
        # Aplicamos el permiso directamente sobre la carpeta de la VM
        try {
            New-VIPermission -Entity $carpeta `
                             -Principal $nombreGrupo `
                             -Role $rolNetworking `
                             -Propagate $true `
                             -ErrorAction Stop
            
            Write-Host "  [OK] Permiso aplicado correctamente." -ForegroundColor Green
        }
        catch {
            if ($_.Exception.Message -match "already exists") {
                Write-Host "  [INFO] El grupo ya tenía este permiso." -ForegroundColor Gray
            } else {
                Write-Host "  [ERROR] No se pudo asignar: $($_.Exception.Message)" -ForegroundColor Red
            }
        }
    }
}

Write-Host "`nProceso finalizado." -ForegroundColor Cyan