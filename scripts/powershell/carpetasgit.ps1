Write-Host @"
############################################################
#                                                          #
#          GENERADOR DE ESTRUCTURA DE PROYECTO             #
#               By: Álvaro Remis Busto                     #
#                                                          #
#     Propósito: Despliegue Directorios Entrega GitHub     #
#               y persistencia vía .gitkeep                #
#                                                          #
############################################################
"@ -ForegroundColor Cyan

# Crear la carpeta principal
$raiz = "proyecto"
New-Item -Path $raiz -ItemType Directory -Force

# Definir las subcarpetas relativas a la raíz
$subcarpetas = @(
    "docs/informe",
    "docs/esquemas/red",
    "docs/esquemas/BD",
    "scripts/bash",
    "scripts/powershell",
    "scripts/powercli",
    "scripts/sql/DDL",
    "scripts/sql/DML",
    "apps",
    "inventario",
    "archivos",
    "otros"
)

# Bucle de creación de infraestructura
foreach ($sub in $subcarpetas) {
    $rutaCompleta = Join-Path -Path $raiz -ChildPath $sub
    # Crear la subcarpeta
    New-Item -Path $rutaCompleta -ItemType Directory -Force
    # Crear el archivo .gitkeep para que GitHub las acepte
    New-Item -Path "$rutaCompleta/.gitkeep" -ItemType File -Force
}