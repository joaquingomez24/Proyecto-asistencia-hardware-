# Sistema de asistencia docente — EPET 20

Aplicación Laravel para administrar docentes, horarios, huellas y asistencias. Un agente Python en la Raspberry Pi opera el lector DY50 y recibe las órdenes que genera el panel.

## Arquitectura actual

```text
Navegador ──HTTPS──► proxy de la PC ──HTTP local──► Laravel ──► MySQL
Raspberry Pi ──HTTPS + token──► proxy ──► API de comandos de Laravel
DY50 ──UART──► agente Python de la Raspberry
```

La integración vigente usa una cola de comandos (`GET /api/dy50/comandos/siguiente` y dos rutas `POST` para progreso y resultado). La antigua API de eventos no está registrada. El script que la usaba se conserva en [`archivosDeProceso/`](archivosDeProceso/README.md), no se usa en producción.

## Requisitos

- **PC Windows:** PHP 8.3 o posterior, Composer, XAMPP con MySQL y Python 3 para el proxy HTTPS.
- **Raspberry Pi:** Python 3, `pyserial`, conexión al DY50 y acceso de red a la PC.
- Una base MySQL vacía llamada `asistencia_laravel`.

Los archivos del agente que se copian a la Raspberry son `dy50_agente.py` y `sensor_dy50.py`. No copies claves privadas.

## Instalación inicial

Desde PowerShell, en la carpeta del proyecto:

```powershell
composer install
Copy-Item .env.example .env
```

Edita `.env`: configura la conexión a MySQL, un `DY50_TOKEN` aleatorio y los datos de la cuenta inicial (`ADMIN_USUARIO`, `ADMIN_DNI`, `ADMIN_EMAIL` y `ADMIN_PASSWORD`). Genera la clave de Laravel y crea las tablas y la cuenta:

```powershell
php artisan key:generate
php artisan migrate --seed
```

No uses los valores de ejemplo como credenciales reales. Haz una copia de la base antes de volver a ejecutar migraciones en una instalación que ya tenga datos.

## HTTPS y certificado

El certificado del servidor debe incluir en su SAN la IP o el nombre que utilizarán el navegador y la Raspberry. La dirección configurada actualmente es `192.168.100.150`; si cambia, actualiza `APP_URL` y regenera el certificado para que coincidan.

La primera vez, crea una CA local en `C:\dy50-tls` con OpenSSL 3:

```powershell
New-Item -ItemType Directory -Force C:\dy50-tls
Set-Location C:\dy50-tls
openssl req -x509 -newkey rsa:4096 -nodes -keyout ca.key -out ca.crt -sha256 -days 3650 -subj "/CN=DY50 Local Development CA" -addext "basicConstraints=critical,CA:TRUE" -addext "keyUsage=critical,keyCertSign,cRLSign"
```

Con la CA ya creada, ejecuta desde la carpeta del proyecto:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\preparar-https.ps1
```

El asistente solicita la IP de la Raspberry, detecta la dirección de la PC hacia esa Raspberry, genera o renueva el certificado del servidor y copia **solo `ca.crt`** a `/home/alumnos/certs/ca.crt`. Si Windows no encuentra OpenSSL, instala Git for Windows o usa el OpenSSL de XAMPP. Conserva `ca.key` y `server.key` únicamente en la PC.

### Elegir la dirección de red

La Raspberry debe poder alcanzar la PC. En la red actual se usa `192.168.100.150`. Si se conecta la Raspberry por cable directo, la guía anterior usaba `192.168.50.1` para la PC; en ese caso actualiza la URL y emite un certificado para esa dirección. En una red escolar o VPN, usa la IP o el nombre que realmente alcance la Raspberry. No mezcles la IP de una opción con el certificado de otra.

## Iniciar los servicios

Enciende MySQL desde XAMPP. Abre tres terminales PowerShell en la carpeta del proyecto y deja cada proceso activo:

**1. Laravel**

```powershell
php artisan optimize:clear
php artisan serve --host=127.0.0.1 --port=8000
```

**2. Tareas programadas**

```powershell
php artisan schedule:work
```

**3. Proxy HTTPS**

```powershell
py -3 .\https_proxy.py --cert C:\dy50-tls\server.crt --key C:\dy50-tls\server.key --host 0.0.0.0 --port 8443 --backend 127.0.0.1:8000 --modo sitio
```

El modo `sitio` permite abrir el panel y la API a través del proxy. El modo `api` restringe el proxy a las rutas del agente y no sirve el panel web. Autoriza el puerto TCP 8443 en el firewall solo para las redes/equipos que correspondan. Con la IP configurada actualmente, abre `https://192.168.100.150:8443`.

## Iniciar el agente DY50

En la Raspberry, desde la carpeta que contiene `dy50_agente.py` y `sensor_dy50.py`:

```bash
python3 dy50_agente.py --url https://192.168.100.150:8443 --cafile /home/alumnos/certs/ca.crt --token 'TU_DY50_TOKEN'
```

Reemplaza `TU_DY50_TOKEN` por el mismo valor configurado en `.env`. No publiques el token ni lo guardes en el repositorio. Para probar sin el sensor se puede agregar `--simulado`; no uses ese modo para enrolar huellas reales.

## Vaciar la memoria del lector

Conecta el DY50 y ejecuta desde la carpeta donde están `vaciar_memoria_dy50.py` y `sensor_dy50.py`:

```bash
python3 vaciar_memoria_dy50.py --puerto /dev/serial0
```

El programa muestra cuántas huellas hay y exige escribir `VACIAR`. La operación es irreversible.

## Comprobaciones

```powershell
php artisan route:list --path=dy50
php artisan test
py -3 -m unittest discover -s pruebas -v
```

La API actual debe mostrar las tres rutas bajo `api/dy50/comandos`. Si el agente recibe `401`, verifica que el token coincida; si aparece un error TLS, comprueba que la dirección usada esté incluida en el certificado y que la Raspberry confíe en `ca.crt`.

## Archivos locales y seguridad

- `.env` contiene tokens, contraseñas y valores propios de cada instalación: no debe versionarse. [`.env.example`](.env.example) es la plantilla sin secretos. Al clonar, copia esa plantilla a `.env` y completa tus valores.
- `asistencia_laravel` es un archivo SQLite local antiguo; la aplicación configurada aquí usa MySQL. El archivo puede contener datos locales, por eso se conserva como copia local y se excluye de Git; respáldalo y verifica su contenido antes de borrarlo.
- `__pycache__/` y `.phpunit.result.cache` son resultados regenerables de Python y PHPUnit. No son código ni datos de la aplicación; se pueden borrar cuando no haya procesos de prueba activos.
- `server.key` es una **clave privada TLS**, no un certificado público. El proxy necesita el par correspondiente `server.crt`/`server.key`; conserva la clave en `C:\dy50-tls`, fuera del repositorio, y nunca la copies a la Raspberry.
- `vendor/`, `node_modules/`, compilaciones, cachés y archivos de sesión son dependencias o resultados locales; se regeneran a partir de `composer.lock`, `package.json` o al ejecutar la aplicación.
- `archivosDeProceso/` contiene material de etapas descartadas, solo como referencia histórica.
