@echo off
title Sistema de asistencia - Inicio
cd /d "%~dp0"

rem ============================================================
rem  Inicia el sistema de asistencia (Laravel) con un solo clic.
rem  Poner este archivo en la carpeta del proyecto, junto a "artisan".
rem ============================================================

where php >nul 2>&1
if errorlevel 1 goto sin_php
if not exist artisan goto sin_artisan
if not exist .env goto sin_env

set "IP="
for /f "usebackq delims=" %%i in (`powershell -NoProfile -Command "(Get-NetIPConfiguration | Where-Object { $_.IPv4DefaultGateway -ne $null -and $_.NetAdapter.Status -eq 'Up' } | Select-Object -First 1).IPv4Address.IPAddress"`) do set "IP=%%i"
if not defined IP set /p IP=No pude detectar la IP de esta PC. Escribala y presione Enter: 

:menu
cls
echo ============================================================
echo    SISTEMA DE ASISTENCIA - EPET 20
echo ============================================================
echo    IP de esta PC: %IP%
echo.
echo    1^) Iniciar con HTTP    ^(recomendado para pruebas^)
echo    2^) Iniciar con HTTPS   ^(proxy con certificado^)
echo    3^) Detener todo
echo    4^) Salir
echo.
set "OP="
set /p OP=Elija una opcion y presione Enter: 
if "%OP%"=="1" goto http
if "%OP%"=="2" goto https
if "%OP%"=="3" goto detener
if "%OP%"=="4" exit /b 0
goto menu

:http
if not defined IP goto error_ip
call :actualizar_url http://%IP%:8000
if errorlevel 1 goto error_app_url
call :preparar
if errorlevel 1 goto error_preparar
call :abrir_puerto 8000
start "Laravel Server" cmd /k php artisan serve --host=0.0.0.0 --port=8000
start "Laravel Scheduler" cmd /k php artisan schedule:work
call :abrir_navegador http://127.0.0.1:8000
echo.
echo ============================================================
echo    LISTO ^(HTTP^). En la Raspberry:
echo.
echo    Navegador : http://%IP%:8000
echo    Agente    : python3 dy50_agente.py --url http://%IP%:8000 --token TU_DY50_TOKEN
echo.
echo    Agregue --simulado al agente para probar sin el lector.
echo ============================================================
echo.
pause
goto menu

:https
set "PY="
for /f "delims=" %%p in ('where python 2^>nul') do if not defined PY set "PY=python"
if not defined PY for /f "delims=" %%p in ('where py 2^>nul') do if not defined PY set "PY=py"
if not defined PY goto sin_python
if not exist "%~dp0https_proxy.py" goto sin_proxy
if not exist "%~dp0preparar-https.ps1" goto sin_preparador

echo.
echo Se configurara HTTPS y se copiara ca.crt a /home/alumnos/certs/ en la Raspberry.
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0preparar-https.ps1"
if errorlevel 1 goto error_https
set "IP="
if exist "C:\dy50-tls\ip.txt" set /p IP=<"C:\dy50-tls\ip.txt"
if not defined IP goto error_https

call :actualizar_url https://%IP%:8443
if errorlevel 1 goto error_app_url
call :preparar
if errorlevel 1 goto error_preparar
call :abrir_puerto 8443
start "Laravel Server" cmd /k php artisan serve --host=127.0.0.1 --port=8000
start "Laravel Scheduler" cmd /k php artisan schedule:work
start "Proxy HTTPS" cmd /k %PY% "%~dp0https_proxy.py" --cert "C:\dy50-tls\server.crt" --key "C:\dy50-tls\server.key" --host 0.0.0.0 --port 8443 --backend 127.0.0.1:8000 --modo sitio
call :abrir_navegador https://%IP%:8443
echo.
echo ============================================================
echo    LISTO ^(HTTPS^). En la Raspberry:
echo.
echo    Navegador : https://%IP%:8443
echo    Agente    : python3 dy50_agente.py --url https://%IP%:8443 --cafile /home/alumnos/certs/ca.crt --token TU_DY50_TOKEN
echo    CA copiada: /home/alumnos/certs/ca.crt
echo ============================================================
echo.
pause
goto menu

:detener
call :cerrar_ventanas
echo Todo detenido.
echo.
pause
goto menu

rem ---------------- subrutinas ----------------
:preparar
call :cerrar_ventanas
netstat -an | findstr /c:":3306 " >nul
if errorlevel 1 echo [AVISO] MySQL no parece estar encendido ^(puerto 3306^). Inicielo desde el panel de XAMPP.
php artisan optimize:clear
if errorlevel 1 exit /b 1
exit /b 0

:actualizar_url
if not exist "%~dp0actualizar-app-url.ps1" exit /b 1
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0actualizar-app-url.ps1" -Url "%~1"
exit /b %errorlevel%

:cerrar_ventanas
taskkill /FI "WINDOWTITLE eq Laravel Server*" /T /F >nul 2>&1
taskkill /FI "WINDOWTITLE eq Laravel Scheduler*" /T /F >nul 2>&1
taskkill /FI "WINDOWTITLE eq Proxy HTTPS*" /T /F >nul 2>&1
exit /b 0

:abrir_puerto
netsh advfirewall firewall show rule name="Sistema asistencia %1" >nul 2>&1
if not errorlevel 1 exit /b 0
netsh advfirewall firewall add rule name="Sistema asistencia %1" dir=in action=allow protocol=TCP localport=%1 >nul 2>&1
if errorlevel 1 echo [AVISO] No pude abrir el puerto %1 en el firewall. Ejecute este archivo UNA vez como administrador.
exit /b 0

:abrir_navegador
timeout /t 4 /nobreak >nul
start "" %1
exit /b 0

rem ---------------- errores ----------------
:sin_php
echo [ERROR] No encuentro PHP. Si usan XAMPP, agreguen C:\xampp\php al PATH de Windows y abran una consola nueva.
pause
exit /b 1

:sin_artisan
echo [ERROR] Este archivo debe estar en la carpeta del proyecto, junto al archivo "artisan".
pause
exit /b 1

:sin_env
echo [ERROR] Falta el archivo .env en esta carpeta.
pause
exit /b 1

:sin_python
echo [ERROR] No encuentro Python en esta PC. Instalenlo o usen la opcion 1 ^(HTTP^).
pause
goto menu

:sin_proxy
echo [ERROR] No encuentro https_proxy.py junto a este archivo.
pause
goto menu

:sin_preparador
echo [ERROR] No encuentro preparar-https.ps1 junto a este archivo.
pause
goto menu

:error_https
echo [ERROR] No se pudo preparar HTTPS o copiar ca.crt. Revise el mensaje anterior.
pause
goto menu

:error_app_url
echo [ERROR] No se pudo actualizar APP_URL en .env. Laravel no se iniciara con una URL desactualizada.
pause
goto menu

:error_ip
echo [ERROR] No pude determinar la IP de esta PC.
pause
goto menu

:error_preparar
echo [ERROR] Laravel no pudo limpiar la configuracion cacheada. Revise el error anterior; no se iniciaron los servidores.
pause
goto menu
