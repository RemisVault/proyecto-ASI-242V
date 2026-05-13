# --- CONFIGURACIÓN DE ASIGNACIONES ---
# (host, usuarioenhost, usuario)
$asignaciones = @(
    ("1", "1", "luisma"), 
    ("1", "2", "luis"), 
    ("1", "3", "olga"), 
    ("1", "4", "toni"), 
    ("1", "5", "ruben"), 
    ("1", "6", "guillermo"),
    ("2", "1", "alvaro"),
    ("2", "2", "manu"),
    ("2", "3", "victor")
)

# --- EJECUCIÓN ---
Import-Module ActiveDirectory

ForEach ($asignacion in $asignaciones)
{
    $hostesxi = $asignacion[0]
    $usuarioenhost = $asignacion[1]
    $usuario = $asignacion[2]
    
    # Ajustado a tu dominio -242V
    $grupo = "usuariosHV0$hostesxi-242V.usu$usuarioenhost"

    Write-Host "Procesando $grupo para el usuario $usuario..." -ForegroundColor Yellow

    try {
        # Obtiene y elimina los miembros actuales para dejar el grupo limpio
        $miembros = Get-ADGroup $grupo | Get-ADGroupMember
        foreach ($miembro in $miembros) 
        {
            Remove-ADGroupMember -Identity $grupo -Members $miembro -Confirm:$false
            Write-Host "  > Eliminado miembro previo: $($miembro.Name)" -ForegroundColor Gray
        }
        
        # Añade al usuario definitivo
        Add-ADGroupMember -Identity $grupo -Members $usuario
        Write-Host "  > OK: $usuario añadido a $grupo" -ForegroundColor Green
    }
    catch {
        Write-Warning "  ! Error: No se pudo procesar el grupo $grupo o el usuario $usuario. Revisa que existan en el AD."
    }
}