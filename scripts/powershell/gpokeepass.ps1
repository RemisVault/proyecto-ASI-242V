$kpPath = "C:\Program Files (x86)\KeePass2x\KeePass.exe"
$vaults = "\\FILER01-242V.asi-242v.cifpaviles.com\Vaults$"

$user = [Security.Principal.WindowsIdentity]::GetCurrent()
$groups = $user.Groups.Translate([Security.Principal.NTAccount]).Value

Start-Sleep -Seconds 5

if (Test-Path $kpPath) {
    # Comprobamos pertenencia a grupos usando -match (más flexible)
    if ($groups -match "adminspasswd") {
        Start-Process $kpPath -ArgumentList "`"$vaults\Vault_Maestra.kdbx`""
    }
    elseif ($groups -match "admins") {
        Start-Process $kpPath -ArgumentList "`"$vaults\Vault_Admins.kdbx`""
    }
}