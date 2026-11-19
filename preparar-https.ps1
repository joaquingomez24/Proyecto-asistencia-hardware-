$ErrorActionPreference = 'Stop'

$tlsDir = 'C:\dy50-tls'
$caCertificate = Join-Path $tlsDir 'ca.crt'
$caKey = Join-Path $tlsDir 'ca.key'
$serverCertificate = Join-Path $tlsDir 'server.crt'
$serverKey = Join-Path $tlsDir 'server.key'
$ipRecord = Join-Path $tlsDir 'ip.txt'
$sshUser = 'alumnos'
$remoteCertificate = '/home/alumnos/certs/ca.crt'

function Stop-Setup($message) {
    Write-Host "[ERROR] $message" -ForegroundColor Red
    exit 1
}

function Invoke-OpenSsl {
    param([string[]]$Arguments)

    $startInfo = New-Object System.Diagnostics.ProcessStartInfo
    $startInfo.FileName = $script:openssl
    $startInfo.UseShellExecute = $false
    $startInfo.RedirectStandardOutput = $true
    $startInfo.RedirectStandardError = $true
    $startInfo.CreateNoWindow = $true

    $quotedArguments = foreach ($argument in $Arguments) {
        $builder = New-Object System.Text.StringBuilder
        [void]$builder.Append('"')
        $backslashes = 0
        foreach ($character in $argument.ToCharArray()) {
            if ($character -eq '\') {
                $backslashes++
            } elseif ($character -eq '"') {
                if ($backslashes -gt 0) {
                    [void]$builder.Append(('\' * (2 * $backslashes)))
                }
                [void]$builder.Append('\"')
                $backslashes = 0
            } else {
                if ($backslashes -gt 0) {
                    [void]$builder.Append(('\' * $backslashes))
                    $backslashes = 0
                }
                [void]$builder.Append($character)
            }
        }
        if ($backslashes -gt 0) {
            [void]$builder.Append(('\' * (2 * $backslashes)))
        }
        [void]$builder.Append('"')
        $builder.ToString()
    }
    $startInfo.Arguments = $quotedArguments -join ' '

    $process = New-Object System.Diagnostics.Process
    $process.StartInfo = $startInfo
    if (!$process.Start()) {
        throw "No se pudo iniciar OpenSSL: $($Arguments -join ' ')"
    }
    $standardOutput = $process.StandardOutput.ReadToEndAsync()
    $standardError = $process.StandardError.ReadToEndAsync()
    $process.WaitForExit()
    $output = $standardOutput.Result
    $errorOutput = $standardError.Result

    if ($process.ExitCode -ne 0) {
        $details = (@($output) + @($errorOutput)) -join ' '
        throw "OpenSSL fallo ($($process.ExitCode)): $($Arguments -join ' '). $details"
    }
    if ($output) {
        Write-Host $output.Trim()
    }
}

function Test-ServerCertificate {
    param([string]$Address)

    if (!(Test-Path $script:serverCertificate) -or !(Test-Path $script:serverKey)) {
        return $false
    }

    & $script:openssl verify -CAfile $script:caCertificate -verify_ip $Address $script:serverCertificate *> $null
    if ($LASTEXITCODE -ne 0) {
        return $false
    }

    & $script:openssl x509 -in $script:serverCertificate -checkend 2592000 -noout *> $null
    if ($LASTEXITCODE -ne 0) {
        return $false
    }

    $certificatePublicKey = (& $script:openssl x509 -in $script:serverCertificate -pubkey -noout 2>$null) -join "`n"
    if ($LASTEXITCODE -ne 0) {
        return $false
    }
    $privateKeyPublicKey = (& $script:openssl pkey -in $script:serverKey -pubout 2>$null) -join "`n"
    if ($LASTEXITCODE -ne 0) {
        return $false
    }

    return $certificatePublicKey.Trim() -eq $privateKeyPublicKey.Trim()
}

try {
    if (!(Test-Path $caCertificate) -or !(Test-Path $caKey)) {
        Stop-Setup "Falta $caCertificate o $caKey. No se creara otra CA automaticamente para no invalidar la confianza de la Raspberry."
    }

    $opensslCommand = Get-Command openssl.exe -ErrorAction SilentlyContinue
    if ($opensslCommand) {
        $script:openssl = $opensslCommand.Source
    } elseif (Test-Path 'C:\Program Files\Git\usr\bin\openssl.exe') {
        $script:openssl = 'C:\Program Files\Git\usr\bin\openssl.exe'
    } elseif (Test-Path 'C:\xampp\apache\bin\openssl.exe') {
        $script:openssl = 'C:\xampp\apache\bin\openssl.exe'
    } else {
        Stop-Setup 'No encuentro OpenSSL. Instale Git for Windows o configure OpenSSL.'
    }

    $raspberryIp = Read-Host 'IP de la Raspberry'
    $parsedRaspberryIp = $null
    if (![System.Net.IPAddress]::TryParse($raspberryIp, [ref]$parsedRaspberryIp) -or
        $parsedRaspberryIp.AddressFamily -ne [System.Net.Sockets.AddressFamily]::InterNetwork) {
        Stop-Setup 'La direccion ingresada para la Raspberry no es una IPv4 valida.'
    }

    $pcIp = $null
    try {
        $route = Find-NetRoute -RemoteIPAddress $raspberryIp -ErrorAction Stop |
            Where-Object { $_.IPAddress -and $_.IPAddress -notlike '127.*' -and $_.IPAddress -notlike '169.254.*' } |
            Select-Object -First 1
        if ($route) {
            $pcIp = $route.IPAddress
        }
    } catch {
        Write-Host '[AVISO] Windows no pudo determinar la interfaz de ruta hacia la Raspberry.'
    }

    if (!$pcIp) {
        $addresses = @(Get-NetIPAddress -AddressFamily IPv4 -ErrorAction SilentlyContinue |
            Where-Object {
                $_.IPAddress -notlike '127.*' -and
                $_.IPAddress -notlike '169.254.*' -and
                $_.AddressState -eq 'Preferred'
            } |
            Select-Object -ExpandProperty IPAddress -Unique)

        if ($addresses.Count -eq 1) {
            $pcIp = $addresses[0]
        } elseif ($addresses.Count -gt 1) {
            Write-Host 'No pude elegir automaticamente la IP conectada a la Raspberry. Direcciones disponibles:'
            for ($index = 0; $index -lt $addresses.Count; $index++) {
                Write-Host "  $($index + 1)) $($addresses[$index])"
            }
            $choice = Read-Host 'Numero de la IP de esta PC en la red de la Raspberry'
            $selection = 0
            if (![int]::TryParse($choice, [ref]$selection) -or $selection -lt 1 -or $selection -gt $addresses.Count) {
                Stop-Setup 'La seleccion de IP no es valida.'
            }
            $pcIp = $addresses[$selection - 1]
        }
    }

    if (!$pcIp) {
        Stop-Setup 'No encontre una IPv4 activa de esta PC. Conectela a la red de la Raspberry e intentelo nuevamente.'
    }

    $selectedPcIp = Read-Host "IP detectada para esta PC: $pcIp (Enter para aceptar o escriba la IP correcta)"
    if ($selectedPcIp) {
        $parsedPcIp = $null
        if (![System.Net.IPAddress]::TryParse($selectedPcIp, [ref]$parsedPcIp) -or
            $parsedPcIp.AddressFamily -ne [System.Net.Sockets.AddressFamily]::InterNetwork) {
            Stop-Setup 'La direccion ingresada para esta PC no es una IPv4 valida.'
        }
        $pcIp = $parsedPcIp.ToString()
    }
    Write-Host "IP de esta PC para la Raspberry: $pcIp" -ForegroundColor Cyan

    $registeredIps = @()
    if (Test-Path $ipRecord) {
        foreach ($registeredIp in Get-Content -Path $ipRecord) {
            $parsedIp = $null
            if ([System.Net.IPAddress]::TryParse($registeredIp.Trim(), [ref]$parsedIp) -and
                $parsedIp.AddressFamily -eq [System.Net.Sockets.AddressFamily]::InterNetwork -and
                $registeredIps -notcontains $parsedIp.ToString()) {
                $registeredIps += $parsedIp.ToString()
            }
        }
    }

    if (Test-ServerCertificate -Address $pcIp) {
        if ($registeredIps -notcontains $pcIp) {
            $registeredIps += $pcIp
        }
        Set-Content -Path $ipRecord -Value (@($pcIp) + @($registeredIps | Where-Object { $_ -ne $pcIp })) -Encoding ascii
        Write-Host 'El certificado vigente ya corresponde a esta IP; no hace falta volver a emitirlo.' -ForegroundColor Green
    } else {
        Write-Host "Creando un certificado de servidor firmado por la CA para $pcIp ..."
        $stagingPrefix = 'server.' + [guid]::NewGuid().ToString('N')
        $stagingKey = Join-Path $tlsDir "$stagingPrefix.key"
        $stagingCsr = Join-Path $tlsDir "$stagingPrefix.csr"
        $stagingCertificate = Join-Path $tlsDir "$stagingPrefix.crt"
        $extensions = Join-Path $tlsDir "$stagingPrefix.ext"
        $allCertificateIps = @($pcIp) + @($registeredIps | Where-Object { $_ -ne $pcIp })
        $subjectAlternativeNames = ($allCertificateIps | ForEach-Object { "IP:$_" }) -join ','
        @(
            'basicConstraints=critical,CA:FALSE'
            'keyUsage=critical,digitalSignature,keyEncipherment'
            'extendedKeyUsage=serverAuth'
            "subjectAltName=$subjectAlternativeNames"
            'subjectKeyIdentifier=hash'
            'authorityKeyIdentifier=keyid,issuer'
        ) | Set-Content -Path $extensions -Encoding ascii

        try {
            Invoke-OpenSsl @('genrsa', '-out', $stagingKey, '2048')
            Invoke-OpenSsl @('req', '-new', '-key', $stagingKey, '-out', $stagingCsr, '-subj', "/CN=$pcIp")
            Invoke-OpenSsl @(
                'x509', '-req', '-in', $stagingCsr,
                '-CA', $caCertificate, '-CAkey', $caKey, '-CAcreateserial',
                '-out', $stagingCertificate, '-days', '365', '-sha256', '-extfile', $extensions
            )

            & $script:openssl verify -CAfile $caCertificate -verify_ip $pcIp $stagingCertificate
            if ($LASTEXITCODE -ne 0) {
                throw 'El certificado generado no valida con la CA o no contiene la IP requerida.'
            }
            & $script:openssl x509 -in $stagingCertificate -checkend 2592000 -noout
            if ($LASTEXITCODE -ne 0) {
                throw 'El certificado generado no tiene una vigencia valida.'
            }
            $certificatePublicKey = (& $script:openssl x509 -in $stagingCertificate -pubkey -noout) -join "`n"
            $privateKeyPublicKey = (& $script:openssl pkey -in $stagingKey -pubout) -join "`n"
            if ($LASTEXITCODE -ne 0 -or $certificatePublicKey.Trim() -ne $privateKeyPublicKey.Trim()) {
                throw 'La clave privada no corresponde al certificado generado.'
            }

            Move-Item -Path $stagingKey -Destination $serverKey -Force
            Move-Item -Path $stagingCertificate -Destination $serverCertificate -Force
            if (!(Test-ServerCertificate -Address $pcIp)) {
                Stop-Setup 'El certificado generado no paso la verificacion de IP, vigencia, firma o clave.'
            }
            Set-Content -Path $ipRecord -Value $allCertificateIps -Encoding ascii
            Write-Host 'Certificado de servidor creado y verificado.' -ForegroundColor Green
        } finally {
            Remove-Item -Path $stagingKey, $stagingCsr, $stagingCertificate, $extensions -Force -ErrorAction SilentlyContinue
        }
    }

    $sshCommand = Get-Command ssh.exe -ErrorAction SilentlyContinue
    $scpCommand = Get-Command scp.exe -ErrorAction SilentlyContinue
    if (!$sshCommand -or !$scpCommand) {
        Stop-Setup 'Faltan ssh.exe o scp.exe de OpenSSH. Habilite OpenSSH Client en las Caracteristicas opcionales de Windows.'
    }

    $destination = "$sshUser@${raspberryIp}:$remoteCertificate"
    Write-Host "Copiando solo ca.crt a $destination. SSH puede solicitar confirmar la huella y la contrasena."
    & $sshCommand.Source -o ConnectTimeout=10 "$sshUser@$raspberryIp" 'mkdir -p /home/alumnos/certs'
    if ($LASTEXITCODE -ne 0) {
        Stop-Setup 'No pude crear /home/alumnos/certs por SSH. Revise IP, usuario, servicio SSH y contrasena.'
    }
    & $scpCommand.Source -o ConnectTimeout=10 $caCertificate $destination
    if ($LASTEXITCODE -ne 0) {
        Stop-Setup 'No pude copiar ca.crt. Revise la conexion SSH y los permisos del usuario alumnos.'
    }
    & $sshCommand.Source -o ConnectTimeout=10 "$sshUser@$raspberryIp" 'test -s /home/alumnos/certs/ca.crt'
    if ($LASTEXITCODE -ne 0) {
        Stop-Setup 'La Raspberry no confirmo la existencia de /home/alumnos/certs/ca.crt.'
    }

    Write-Host "CA instalada en $remoteCertificate." -ForegroundColor Green
    exit 0
} catch {
    Stop-Setup $_.Exception.Message
}
