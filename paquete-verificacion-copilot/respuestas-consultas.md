# Respuestas a las consultas del repositorio

La carpeta `paquete-verificacion-copilot/` contiene copias idénticas a los originales: [informe de verificación](./informe-verificacion-codigo.md), [Listado de comandos](./Listado_comandos_Laravel_y_Raspberry.docx), [README principal](./README.md) y [README de archivosDeProceso](./archivosDeProceso/README.md).

Para obtener la salida actual de `route:list`, restauré las dependencias con `composer install` usando el lockfile. `vendor/` sigue ignorado; no cambiaron los manifiestos rastreados. No leí `.env` ni ejecuté operaciones sobre el sensor.

## 1. Estados, tolerancia y ventana

Contenido de [config/asistencia.php](../config/asistencia.php):

```php
<?php

return [
    // Cuánto antes del inicio se habilita el registro de asistencia.
    'minutos_antes' => 20,

    // Pasado este tiempo desde el inicio, la asistencia pasa de "Sobre la hora" a "Tarde".
    'minutos_tolerancia' => 20,
];
```

Contenido de [EstadoAsistencia.php](../app/Enums/EstadoAsistencia.php):

```php
<?php

namespace App\Enums;

enum EstadoAsistencia: string
{
    case Presente = 'presente';
    case SobreLaHora = 'sobre_la_hora';
    case Tarde = 'tarde';
    case Ausente = 'ausente';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Presente => 'Presente',
            self::SobreLaHora => 'Sobre la hora',
            self::Tarde => 'Tarde',
            self::Ausente => 'Ausente',
        };
    }
}
```

Se habilita fichar 20 minutos antes. La tolerancia de 20 minutos se cuenta desde la hora de inicio.

## 2. Tablas y relaciones para el DER

Según las migraciones de [database/migrations/](../database/migrations/):

| Tabla | Columnas y claves principales | Relaciones |
|---|---|---|
| `docentes` | `id` PK; `dni` único; `nombre`, `apellido`, `telefono` nullable, `email` nullable, `activo` default `true`, timestamps | Referenciada por `users`, `huellas`, `horarios`, `dy50_comandos` y `asistencias`. |
| `huellas` | `id` PK; `docente_id` FK; `sensor_id` único; `dedo` nullable; timestamps | `docente_id` → `docentes` (`CASCADE`). |
| `materias` | `id` PK; `nombre` único; timestamps | Referenciada por `horarios`. |
| `horarios` | `id` PK; `docente_id`, `materia_id` FKs; `curso`, `division`, `turno`, `dia_semana`, `hora_inicio`, `hora_fin`, `deleted_at` (soft delete), timestamps; índices `(docente_id, dia_semana)` y `(curso, division, dia_semana)` | Docente → `docentes` (`CASCADE`); materia → `materias` (`RESTRICT`). |
| `dy50_comandos` | `id` PK; `dispositivo` y `estado` indexados; `tipo`; `user_id`, `docente_id`, `horario_id` nullable; `sensor_id`, `confianza`, `dedo` nullable; `timeout_segundos`; `progreso`, `mensaje` nullable; `expira_at`, `reclamado_at`, `finalizado_at`; timestamps; índice `(dispositivo, estado, id)` | `user_id` → `users`, `docente_id` → `docentes`, `horario_id` → `horarios`; las FKs nullable usan `SET NULL`. |
| `asistencias` | `id` PK; `fecha`; `docente_id`, `horario_id` FKs; `comando_id` nullable, único; `hora_ingreso`, `hora_egreso` nullable; `estado`; timestamps; único `(fecha, docente_id, horario_id)` e índice por `fecha` | Docente y horario usan `RESTRICT`; comando → `dy50_comandos` (`SET NULL`). |
| `asistencia_ediciones` | `id` PK; `asistencia_id` FK; `user_id` nullable FK; `antes`, `despues` JSON; timestamps | Asistencia → `asistencias` (`CASCADE`); usuario → `users` (`SET NULL`). |
| `users` | `id` PK; `usuario`, `dni`, `email` únicos; `password`, `rol`, `remember_token`, timestamps; `docente_id` nullable y único | `docente_id` → `docentes` (`SET NULL`): una cuenta docente como máximo por ficha. |

## 3. Rutas actuales y permisos

La salida actual de `php artisan route:list -vv`:

```text
GET|HEAD / .. routes/web.php:17
 ⇂ web
 GET|HEAD api/dy50/comandos/siguiente .. Api\Dy50Controller@siguiente
 ⇂ api
 ⇂ App\Http\Middleware\VerificarTokenDy50
 POST api/dy50/comandos/{comando}/progreso .. Api\Dy50Controller@progreso
 ⇂ api
 ⇂ App\Http\Middleware\VerificarTokenDy50
 POST api/dy50/comandos/{comando}/resultado .. Api\Dy50Controller@resultado
 ⇂ api
 ⇂ App\Http\Middleware\VerificarTokenDy50
 GET|HEAD asistencias .. asistencias.index › AsistenciaController@index
 ⇂ web
 ⇂ auth
 ⇂ App\Http\Middleware\EnsureRole:secretario,jefe_preceptores
 PUT asistencias/{asistencia} .. asistencias.update › AsistenciaController@update
 ⇂ web
 ⇂ auth
 ⇂ App\Http\Middleware\EnsureRole:secretario,jefe_preceptores
 GET|HEAD asistencias/{asistencia}/editar .. asistencias.edit › AsistenciaController@edit
 ⇂ web
 ⇂ auth
 ⇂ App\Http\Middleware\EnsureRole:secretario,jefe_preceptores
 GET|HEAD cuentas .. usuarios.index › UsuarioController@index
 ⇂ web
 ⇂ auth
 ⇂ App\Http\Middleware\EnsureRole:secretario
 POST cuentas .. usuarios.store › UsuarioController@store
 ⇂ web
 ⇂ auth
 ⇂ App\Http\Middleware\EnsureRole:secretario
 GET|HEAD docente .. docente.panel › DocentePanelController@index
 ⇂ web
 ⇂ auth
 ⇂ App\Http\Middleware\EnsureRole:docente
 POST docente/asistencia .. docente.asistencia.iniciar › DocentePanelController@iniciar
 ⇂ web
 ⇂ auth
 ⇂ App\Http\Middleware\EnsureRole:docente
 GET|HEAD docentes .. docentes.index › DocenteController@index
 ⇂ web
 ⇂ auth
 ⇂ App\Http\Middleware\EnsureRole:secretario
 POST docentes .. DocenteController@store
 ⇂ web
 ⇂ App\Http\Middleware\EnsureRole:secretario
 GET|HEAD docentes/create .. docentes.create › DocenteController@create
 ⇂ web
 ⇂ auth
 ⇂ App\Http\Middleware\EnsureRole:secretario
 PUT|PATCH docentes/{docente} .. docentes.update › DocenteController@update
 ⇂ web
 ⇂ auth
 ⇂ App\Http\Middleware\EnsureRole:secretario
 DELETE docentes/{docente} .. DocenteController@destroy
 ⇂ web
 ⇂ auth
 ⇂ App\Http\Middleware\EnsureRole:secretario
 PATCH docentes/{docente}/activar .. DocenteController@activar
 ⇂ web
 ⇂ auth
 ⇂ App\Http\Middleware\EnsureRole:secretario
 PATCH docentes/{docente}/desactivar .. DocenteController@desactivar
 ⇂ web
 ⇂ App\Http\Middleware\EnsureRole:secretario
 GET|HEAD docentes/{docente}/edit .. docentes.edit › DocenteController@edit
 ⇂ web
 ⇂ auth
 ⇂ App\Http\Middleware\EnsureRole:secretario
 POST docentes/{docente}/huellas/enrolar .. docentes.huellas.enrolar › DocenteHuellaController@enrolar
 ⇂ web
 ⇂ auth
 ⇂ App\Http\Middleware\EnsureRole:secretario
 DELETE docentes/{docente}/huellas/{huella} .. docentes.huellas.destroy › DocenteHuellaController@destroy
 ⇂ web
 ⇂ auth
 ⇂ App\Http\Middleware\EnsureRole:secretario
 GET|HEAD home .. home › routes/web.php:26
 ⇂ web
 ⇂ auth
 GET|HEAD lector .. lector.diagnostico › LectorController@diagnostico
 ⇂ web
 ⇂ auth
 ⇂ App\Http\Middleware\EnsureRole:secretario
 GET|HEAD lector/comandos/{comando} .. LectorController@estado
 ⇂ web
 ⇂ auth
 DELETE lector/comandos/{comando} .. LectorController@cancelar
 ⇂ web
 ⇂ auth
 GET|HEAD lector/en-linea .. lector.en-linea › LectorController@enLinea
 ⇂ web
 ⇂ auth
 GET|HEAD login .. login › Auth\LoginController@showLoginForm
 ⇂ web
 POST login .. Auth\LoginController@login
 ⇂ web
 ⇂ throttle:10,1
 POST logout .. logout › Auth\LoginController@logout
 ⇂ web
 ⇂ auth
 GET|HEAD storage/{path} storage.local › vendor/laravel/framework/src/Illuminate/Filesystem/FilesystemServiceProvider.php:111
 PUT storage/{path} storage.local.upload › vendor/laravel/framework/src/Illuminate/Filesystem/FilesystemServiceProvider.php:119
 GET|HEAD up .. vendor/laravel/framework/src/Illuminate/Foundation/Configuration/ApplicationBuilder.php:224

 Showing [32] routes
```

La matriz queda así, según [routes/web.php](../routes/web.php) y [routes/api.php](../routes/api.php):

- **Secretario y Jefe:** consultar/editar asistencias.
- **Secretario:** cuentas, docentes, huellas y diagnóstico del lector.
- **Docente:** panel y registro de su asistencia.
- **Solo `auth`, sin `EnsureRole`:** home, logout y endpoints web de estado/cancelación del comando y conexión del lector.
- **API DY50:** no usa `EnsureRole`; aplica `VerificarTokenDy50`.
- **Públicas:** inicio, formulario de login y login con límite `throttle:10,1`.

## 4. `.env` y `.gitignore`

`git ls-files | findstr .env` devuelve:

```text
.env.example
```

`.env` no está rastreado y está ignorado. Contenido de [`.gitignore`](../.gitignore):

```gitignore
# Laravel dependencies and generated assets
/vendor/
/node_modules/
/public/build/
/public/hot
/bootstrap/cache/*.php
!/bootstrap/cache/.gitignore

# Local environment and credentials
.env
.env.*
!.env.example
/dy50-tls/
/server.crt
/server.key
/*.key
/*.csr
/*.srl

# Local databases and generated state
/asistencia_laravel
/database/*.sqlite
/database/*.sqlite-*
/.phpunit.result.cache
/.phpunit.cache/
/storage/logs/*.log
/storage/framework/cache/data/*
!/storage/framework/cache/data/.gitignore
/storage/framework/sessions/*
!/storage/framework/sessions/.gitignore
/storage/framework/testing/*
!/storage/framework/testing/.gitignore
/storage/framework/views/*
!/storage/framework/views/.gitignore

# Python caches and test artifacts
__pycache__/
**/__pycache__/
*.py[cod]
.pytest_cache/

# Local-only/empty web assets
/public/favicon.ico

# OS/editor files
.DS_Store
Thumbs.db
```

## 5. Rutas API y scheduler

El contenido actual de [routes/api.php](../routes/api.php):

```php
<?php

use App\Http\Controllers\Api\Dy50Controller;
use App\Http\Middleware\VerificarTokenDy50;
use Illuminate\Support\Facades\Route;

/*
 * API para el agente de la Raspberry (lector DY50). Todo exige "Authorization: Bearer <DY50_TOKEN>".
 * Ya no hay rutas públicas que listen lecturas: el navegador consulta por sesión (ver routes/web.php).
 */
Route::middleware(VerificarTokenDy50::class)->prefix('dy50/comandos')->group(function () {
    Route::get('/siguiente', [Dy50Controller::class, 'siguiente']);
    Route::post('/{comando}/progreso', [Dy50Controller::class, 'progreso']);
    Route::post('/{comando}/resultado', [Dy50Controller::class, 'resultado']);
});
```

El contenido actual de [routes/console.php](../routes/console.php):

```php
<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// Requiere tener corriendo en la PC:  php artisan schedule:work
Schedule::command('asistencia:marcar-ausentes')
    ->everyMinute()
    ->withoutOverlapping();

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
```

La comparación con [README.md](../README.md), [Listado de comandos](./Listado_comandos_Laravel_y_Raspberry.docx) y [bootstrap/app.php](../bootstrap/app.php) no encontró discrepancias en las rutas DY50 ni en el scheduler de ausencias.

## 6. Ayuda de los scripts

Ejecuté `--help` con `py -3`; los comandos de ayuda no activaron el lector ni borraron huellas.

`dy50_agente.py --help`:

```text
usage: dy50_agente.py [-h] [--url URL] [--token TOKEN]
                      [--dispositivo DISPOSITIVO] [--puerto PUERTO]
                      [--baudios BAUDIOS] [--cafile CAFILE]
                      [--no-verificar-tls] [--intervalo INTERVALO]
                      [--simulado] [--simulado-posicion SIMULADO_POSICION]

Agente del lector DY50 para Laravel

options:
  -h, --help            show this help message and exit
  --url URL             dirección de Laravel o del proxy HTTPS
  --token TOKEN         DY50_TOKEN del .env de Laravel
  --dispositivo DISPOSITIVO
  --puerto PUERTO       puerto serie del DY50 (o /dev/ttyUSB0)
  --baudios BAUDIOS
  --cafile CAFILE       certificado de la CA (ca.crt) para validar el HTTPS
  --no-verificar-tls    no validar el certificado (solo pruebas)
  --intervalo INTERVALO
                        segundos entre consultas a Laravel
  --simulado            no usar el módulo real: simula las lecturas
  --simulado-posicion SIMULADO_POSICION
                        posición que 'lee' el modo simulado
```

`https_proxy.py --help`:

```text
usage: https_proxy.py [-h] --cert CERT --key KEY [--host HOST] [--port PORT]
                      [--backend BACKEND] [--modo {api,sitio}]

Proxy HTTPS de pruebas para DY50 + Laravel

options:
  -h, --help          show this help message and exit
  --cert CERT         server.crt (o cadena completa)
  --key KEY           server.key
  --host HOST         interfaz donde escucha (0.0.0.0 = todas)
  --port PORT
  --backend BACKEND   donde esta Laravel (host:puerto)
  --modo {api,sitio}
```

`sensor_dy50.py --help`:

```text
usage: sensor_dy50.py [-h] --probar [--puerto PUERTO] [--baudios BAUDIOS]
                      [--password PASSWORD]

Prueba de conexión con el DY50

options:
  -h, --help           show this help message and exit
  --probar
  --puerto PUERTO
  --baudios BAUDIOS
  --password PASSWORD
```

`vaciar_memoria_dy50.py --help`:

```text
usage: vaciar_memoria_dy50.py [-h] [--puerto PUERTO] [--baudios BAUDIOS]

Borra de forma irreversible todas las huellas guardadas en un DY50.

options:
  -h, --help         show this help message and exit
  --puerto PUERTO    puerto serie del lector (predeterminado: /dev/serial0)
  --baudios BAUDIOS  velocidad serie (predeterminada: 57600)
```

No ejecuté `vaciar_memoria_dy50.py` sin `--help`; el programa borra de forma irreversible las huellas físicas.
