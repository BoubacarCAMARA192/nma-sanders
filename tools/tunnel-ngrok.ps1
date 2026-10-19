# =====================================================================
# Ouvre le tunnel ngrok vers le MySQL de XAMPP puis met a jour les
# variables d'environnement Vercel et redeploie en production.
# Prerequis : ngrok installe et configure (ngrok config add-authtoken ...)
#             XAMPP (MySQL) demarre, vercel CLI connectee.
# =====================================================================

$ErrorActionPreference = 'Stop'

$dossierOutils = $PSScriptRoot
$fichierConfig = Join-Path $dossierOutils 'tunnel.local.json'

if (-not (Test-Path $fichierConfig)) {
    throw "Fichier de configuration manquant : $fichierConfig"
}
$config = Get-Content $fichierConfig -Raw | ConvertFrom-Json

# --- 1. Demarrage du tunnel ngrok --------------------------------------
$ngrok = Get-Process -Name 'ngrok' -ErrorAction SilentlyContinue
if (-not $ngrok) {
    Write-Host 'Demarrage de ngrok (tcp 3306)...' -ForegroundColor Cyan
    $executable = (Get-Command ngrok -ErrorAction SilentlyContinue).Source
    if (-not $executable) {
        $executable = Get-ChildItem "$env:LOCALAPPDATA\Microsoft\WinGet\Packages" -Recurse -Filter 'ngrok.exe' -ErrorAction SilentlyContinue |
            Select-Object -First 1 -ExpandProperty FullName
    }
    if (-not $executable) {
        throw 'ngrok est introuvable. Installez-le (winget install ngrok.ngrok).'
    }
    Start-Process -FilePath $executable -ArgumentList 'tcp', '3306' -WindowStyle Minimized
    Start-Sleep -Seconds 4
}

# --- 2. Lecture de l'adresse publique ----------------------------------
$api = Invoke-RestMethod -Uri 'http://127.0.0.1:4040/api/tunnels'
$tunnel = $api.tunnels | Where-Object { $_.proto -eq 'tcp' } | Select-Object -First 1
if (-not $tunnel) {
    throw 'Aucun tunnel TCP actif : verifiez le jeton ngrok (ngrok config add-authtoken).'
}

$adresse = $tunnel.public_url -replace '^tcp://', ''
$hote, $port = $adresse.Split(':')
Write-Host "Tunnel actif : $adresse" -ForegroundColor Green

# --- 3. Mise a jour des variables Vercel -------------------------------
function Set-VariableVercel {
    param([string]$Nom, [string]$Valeur)
    & vercel env rm $Nom production --yes 2>$null | Out-Null
    $Valeur | & vercel env add $Nom production | Out-Null
    Write-Host "  $Nom = $Valeur"
}

Write-Host 'Mise a jour des variables Vercel...' -ForegroundColor Cyan
Set-VariableVercel 'NMA_DB_HOST' $hote
Set-VariableVercel 'NMA_DB_PORT' $port
Set-VariableVercel 'NMA_DB_NAME' $config.base
Set-VariableVercel 'NMA_DB_USER' $config.utilisateur
Set-VariableVercel 'NMA_DB_PASS' $config.mot_de_passe

# --- 4. Redeploiement en production ------------------------------------
Write-Host 'Rediploiement de la production...' -ForegroundColor Cyan
& vercel deploy --prod --yes

Write-Host 'Termine : https://nma-sanders.vercel.app' -ForegroundColor Green
