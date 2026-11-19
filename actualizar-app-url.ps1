param(
    [Parameter(Mandatory = $true)]
    [string]$Url,
    [string]$EnvPath = ''
)

$ErrorActionPreference = 'Stop'

try {
    $parsedUrl = $null
    if (![System.Uri]::TryCreate($Url, [System.UriKind]::Absolute, [ref]$parsedUrl) -or
        $parsedUrl.Scheme -notin @('http', 'https')) {
        throw 'La URL debe ser una direccion absoluta HTTP o HTTPS.'
    }

    $envPath = $EnvPath
    if ([string]::IsNullOrWhiteSpace($envPath)) {
        $envPath = Join-Path $PSScriptRoot '.env'
    }
    if (!(Test-Path -LiteralPath $envPath)) {
        throw "No se encontro el archivo $envPath."
    }

    $encoding = New-Object System.Text.UTF8Encoding($false)
    $lines = [System.IO.File]::ReadAllLines($envPath, $encoding)
    $updatedLines = New-Object 'System.Collections.Generic.List[string]'
    $urlWritten = $false

    foreach ($line in $lines) {
        if ($line -match '^\s*APP_URL\s*=') {
            if (!$urlWritten) {
                $updatedLines.Add("APP_URL=$Url")
                $urlWritten = $true
            }
        } else {
            $updatedLines.Add($line)
        }
    }

    if (!$urlWritten) {
        $updatedLines.Add("APP_URL=$Url")
    }

    $temporaryPath = "$envPath.$([guid]::NewGuid().ToString('N')).tmp"
    try {
        [System.IO.File]::WriteAllLines($temporaryPath, $updatedLines, $encoding)
        Move-Item -LiteralPath $temporaryPath -Destination $envPath -Force
    } finally {
        Remove-Item -LiteralPath $temporaryPath -Force -ErrorAction SilentlyContinue
    }

    Write-Host "APP_URL actualizado a $Url"
    exit 0
} catch {
    Write-Error $_.Exception.Message
    exit 1
}
