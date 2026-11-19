# Informe de verificación del código

**Proyecto:** Laravel + agente Python en Raspberry Pi + lector DY50  
**Fecha de revisión:** 2026-11-17  
**Alcance:** archivos presentes en el repositorio de trabajo y comandos de inspección/pruebas indicados abajo. No se muestran valores del `.env`, contraseñas, tokens ni contenido de certificados o claves privadas.

> **Criterio de evidencia.** Las referencias `ruta:línea` apuntan a líneas del repositorio. Los archivos Word no tienen líneas de texto estables; para ellos se indica el número de párrafo/tabla. Las conclusiones negativas indican el ámbito de búsqueda. «No encontrado» significa que no está declarado en los archivos inspeccionados; no demuestra que no exista en una instalación externa.

## 1. Versiones y dependencias

### PHP y Laravel

- `composer.json` exige PHP `^8.3` y Laravel `^13.17` (`composer.json:9-10`). El `composer.lock` fija Laravel Framework `v13.26.1` (`composer.lock:1058-1059`) y Tinker `v3.0.2` (`composer.lock:1405-1406`).
- El lock declara plataforma PHP `^8.3` y override `8.3.4` (`composer.lock:8440-8445`). El proyecto también requiere PHPUnit `^12.5.12` para desarrollo (`composer.json:13-20`).

### Python y DY50

- El README pide **Python 3** para Raspberry y `pyserial` (`README.md:17-18`). El encabezado del sensor repite que usa `pyserial`, y la dependencia se importa al abrir el puerto (`sensor_dy50.py:1-5`, `sensor_dy50.py:96-102`).
- **Versión mínima exacta de Python:** No encontrado. No se encontró `requirements.txt`, `pyproject.toml` ni `setup.py`; tampoco hay versión de `pyserial` fijada en un manifiesto (`sensor_dy50.py:1-5`, `sensor_dy50.py:96-102`).
- El agente y el protocolo usan la biblioteca estándar de Python además de `pyserial` para el puerto serie (`dy50_agente.py:1-20`, `sensor_dy50.py:22-25`, `sensor_dy50.py:96-102`).

### Node y Vite

- `package.json` define scripts `build` y `dev` con Vite y declara Vite `^8.0.0`, `laravel-vite-plugin` `^3.1` y Tailwind Vite `^4.0.0` (`package.json:5-17`).
- No se declara una versión mínima de Node en `package.json` y no se encontró `package-lock.json`; por lo tanto, **versión mínima/reproducible de Node y versión exacta de Vite:** No encontrado (`package.json:1-18`, `package-lock.json: No encontrado`).
- El script Composer `setup` instala dependencias npm y construye los assets (`composer.json:35-41`). La vista Laravel de bienvenida invoca `@vite` si hay manifiesto o servidor Vite (`resources/views/welcome.blade.php:12-13`).

## 2. Árbol hasta tres niveles y clasificación

**ACTIVO** = conectado al flujo actual o a su mantenimiento. **HISTÓRICO** = prototipo/referencia de una etapa anterior. **HUÉRFANO** = sin referencia en el flujo actual, artefacto local o generado. La clasificación indica su relación con la aplicación Laravel actual, no si conviene borrarlo.

```text
Proyecto-asistencia-hardware-/
├── .editorconfig, .gitattributes, .gitignore, .npmrc                 [ACTIVO: configuración]
├── .env                                                              [ACTIVO local; contiene configuración, valores no reproducidos]
├── .env.example                                                      [ACTIVO: plantilla]
├── README.md                                                         [ACTIVO: documentación]
├── Guia_funcionamiento_proyecto.docx                                 [ACTIVO: guía de referencia]
├── Listado_comandos_Laravel_y_Raspberry.docx                         [ACTIVO: guía de referencia]
├── artisan, composer.json, composer.lock                              [ACTIVO: Laravel/Composer]
├── package.json, vite.config.js, phpunit.xml                          [ACTIVO: Vite opcional / pruebas]
├── iniciar-sistema.bat, actualizar-app-url.ps1                        [ACTIVO: inicio alternativo y URL]
├── preparar-https.ps1, https_proxy.py                                  [ACTIVO: TLS/proxy]
├── dy50_agente.py, sensor_dy50.py, vaciar_memoria_dy50.py              [ACTIVO: agente, hardware y mantenimiento]
├── dy50-agente.service                                                 [ACTIVO opcional: systemd]
├── asistencia_laravel                                                 [HISTÓRICO/local: SQLite, no MySQL vigente]
├── asistencia_docente.sql, conexion.php, index.php, navbar.php,
│   registrar_asistencia.php, registrar_docentes.php,
│   editar_asistencia.php, ver_asistencia.php, style.css                [HISTÓRICO: prototipo PHP]
├── server.crt, server.key                                              [HUÉRFANO/local: fuera de rutas actuales]
├── app/
│   ├── Console/Commands/MarcarAusentes.php                             [ACTIVO]
│   ├── Enums/{DiaSemana,EstadoAsistencia,Rol}.php                      [ACTIVO]
│   ├── Http/Controllers/{Asistencia,Docente,DocenteHuella,
│   │   DocentePanel,Huella,Lector,Usuario}Controller.php               [ACTIVOS salvo HuellaController]
│   ├── Http/Controllers/Api/Dy50Controller.php                         [ACTIVO]
│   ├── Http/Controllers/Auth/LoginController.php                       [ACTIVO]
│   ├── Http/Middleware/{EnsureRole,VerificarTokenDy50}.php             [ACTIVO]
│   ├── Http/Requests/DocenteRequest.php                                [ACTIVO]
│   ├── Models/{Asistencia,AsistenciaEdicion,Docente,Dy50Comando,
│   │   Horario,Huella,Materia,User}.php                                [ACTIVO]
│   ├── Providers/AppServiceProvider.php                                [ACTIVO: integración Laravel]
│   └── Services/{FichajeException,FichajeService,HorarioService}.php   [ACTIVO]
├── archivosDeProceso/
│   ├── README.md                                                       [HISTÓRICO: explica el archivo]
│   └── dy50_diagnostico_https.py                                       [HISTÓRICO: API retirada]
├── bootstrap/{app.php,providers.php,cache/}                            [ACTIVO: arranque; cachés generadas]
├── config/{app,asistencia,auth,cache,database,dy50,filesystems,
│   logging,mail,queue,services,session}.php                            [ACTIVO/configuración Laravel]
├── database/
│   ├── factories/UserFactory.php                                       [ACTIVO en pruebas]
│   ├── migrations/*.php                                                [ACTIVO: esquema]
│   └── seeders/DatabaseSeeder.php                                      [ACTIVO: cuenta inicial]
├── dy50-tls/{certificados,https_proxy.py}                              [HUÉRFANO/local: copia TLS/proxy]
├── pruebas/{test_agente,test_proxy,test_sensor}.py                     [ACTIVO: pruebas Python]
├── public/
│   ├── css/{style,docente}.css, images/logo_epet.png                   [ACTIVO: interfaz]
│   ├── js/{lector,docente-panel,enrolar-huella,horarios-form}.js      [ACTIVO]
│   ├── index.php, .htaccess                                            [ACTIVO: entrada web/configuración]
│   ├── favicon.ico                                                     [HUÉRFANO/ignorado]
│   └── robots.txt                                                      [ACTIVO/auxiliar web]
├── resources/
│   ├── css/app.css, js/app.js                                          [HISTÓRICO/andamiaje Vite]
│   └── views/{asistencias,auth,components,docente,docentes,
│       layouts,lector,usuarios}/                                       [ACTIVO]
│       └── welcome.blade.php                                           [HUÉRFANO respecto a rutas actuales]
├── routes/{api,console,web}.php                                        [ACTIVO]
├── storage/                                                            [ACTIVO como estado/logs generados]
├── tests/{Feature,Unit}/                                                [ACTIVO: pruebas PHP]
├── vendor/                                                            [ACTIVO: dependencias Composer]
├── __pycache__/, pruebas/__pycache__/, .phpunit.result.cache           [HUÉRFANO: artefactos generados]
└── docs/                                                               [No encontrado]
```

### Observaciones de clasificación

- `HuellaController.php` existe, pero no se encontró referencia suya en rutas; las rutas importan `DocenteHuellaController` y `Dy50Controller` (`app/Http/Controllers/HuellaController.php:10-11`, `routes/web.php:7-8`, `routes/web.php:54-55`, `routes/api.php:3-14`). Es **HUÉRFANO**.
- `welcome.blade.php` es la vista de bienvenida del esqueleto y usa Vite, pero `/` redirige a login o home en vez de renderizarla (`resources/views/welcome.blade.php:12-13`, `routes/web.php:17`). Es **HUÉRFANO** para la navegación actual.
- `archivosDeProceso/README.md` declara expresamente que contiene material descartado y que su diagnóstico llama a la ruta antigua `/api/dy50/eventos` (`archivosDeProceso/README.md:1-7`). La API vigente registra comandos, no esa ruta (`routes/api.php:11-14`).
- El archivo sin extensión `asistencia_laravel` tiene cabecera SQLite y está ignorado por Git; la configuración de ejemplo apunta a MySQL (`.gitignore:21`, `.env.example:11-16`). README también lo identifica como base SQLite local antigua (`README.md:123`). No es la base activa documentada.
- El prototipo de raíz se conecta a una base llamada `asistencia_docente` y consulta/escribe tablas/columnas antiguas (`conexion.php:3-9`, `registrar_asistencia.php:8-49`, `asistencia_docente.sql:21-33`, `asistencia_docente.sql:47-62`). No forma parte del front controller Laravel, que es `public/index.php`.
- `app/Http/Controllers/HuellaController.php` contiene métodos de un esquema anterior y no tiene ruta activa; `app/Http/Controllers/DocenteHuellaController.php` es el flujo actual (`app/Http/Controllers/HuellaController.php:12-65`, `routes/web.php:54-55`).
- Existe `dy50-tls/https_proxy.py` además del proxy de raíz. Son copias diferentes; el comando documentado y el batch usan el proxy de raíz (`https_proxy.py`, `dy50-tls/https_proxy.py`, `README.md:85`, `iniciar-sistema.bat:67-68`, `iniciar-sistema.bat:85`).
- `docs/` no existe en el inventario. En la raíz sí hay dos `.docx` citados arriba; al ser binarios no tienen referencias `:línea`. `.gitignore` excluye `vendor`, cachés Python/PHPUnit, favicon y certificados/claves locales (`.gitignore:1-4`, `.gitignore:13-25`, `.gitignore:33-37`).

## 3. Rutas completas y permisos

Salida observada de `php artisan route:list -vv`: **32 rutas**. `web`, `api`, `auth` y los middlewares indicados son los que muestra la lista detallada. Las rutas dinámicas `storage` y `up` las aporta Laravel (`vendor/laravel/framework/src/Illuminate/Filesystem/FilesystemServiceProvider.php:111-119`, `vendor/laravel/framework/src/Illuminate/Foundation/Configuration/ApplicationBuilder.php:224`).

| Método | URI | Nombre | Controlador/acción | Middleware |
|---|---|---|---|---|
| GET\|HEAD | `/` | — | Closure `routes/web.php:17` | web |
| GET\|HEAD | `api/dy50/comandos/siguiente` | — | `Api\Dy50Controller@siguiente` | api, `VerificarTokenDy50` |
| POST | `api/dy50/comandos/{comando}/progreso` | — | `Api\Dy50Controller@progreso` | api, `VerificarTokenDy50` |
| POST | `api/dy50/comandos/{comando}/resultado` | — | `Api\Dy50Controller@resultado` | api, `VerificarTokenDy50` |
| GET\|HEAD | `asistencias` | `asistencias.index` | `AsistenciaController@index` | web, auth, `EnsureRole:secretario,jefe_preceptores` |
| PUT | `asistencias/{asistencia}` | `asistencias.update` | `AsistenciaController@update` | web, auth, `EnsureRole:secretario,jefe_preceptores` |
| GET\|HEAD | `asistencias/{asistencia}/editar` | `asistencias.edit` | `AsistenciaController@edit` | web, auth, `EnsureRole:secretario,jefe_preceptores` |
| GET\|HEAD | `cuentas` | `usuarios.index` | `UsuarioController@index` | web, auth, `EnsureRole:secretario` |
| POST | `cuentas` | `usuarios.store` | `UsuarioController@store` | web, auth, `EnsureRole:secretario` |
| GET\|HEAD | `docente` | `docente.panel` | `DocentePanelController@index` | web, auth, `EnsureRole:docente` |
| POST | `docente/asistencia` | `docente.asistencia.iniciar` | `DocentePanelController@iniciar` | web, auth, `EnsureRole:docente` |
| GET\|HEAD | `docentes` | `docentes.index` | `DocenteController@index` | web, auth, `EnsureRole:secretario` |
| POST | `docentes` | `docentes.store` | `DocenteController@store` | web, auth, `EnsureRole:secretario` |
| GET\|HEAD | `docentes/create` | `docentes.create` | `DocenteController@create` | web, auth, `EnsureRole:secretario` |
| PUT\|PATCH | `docentes/{docente}` | `docentes.update` | `DocenteController@update` | web, auth, `EnsureRole:secretario` |
| DELETE | `docentes/{docente}` | `docentes.destroy` | `DocenteController@destroy` | web, auth, `EnsureRole:secretario` |
| PATCH | `docentes/{docente}/activar` | `docentes.activar` | `DocenteController@activar` | web, auth, `EnsureRole:secretario` |
| PATCH | `docentes/{docente}/desactivar` | `docentes.desactivar` | `DocenteController@desactivar` | web, auth, `EnsureRole:secretario` |
| GET\|HEAD | `docentes/{docente}/edit` | `docentes.edit` | `DocenteController@edit` | web, auth, `EnsureRole:secretario` |
| POST | `docentes/{docente}/huellas/enrolar` | `docentes.huellas.enrolar` | `DocenteHuellaController@enrolar` | web, auth, `EnsureRole:secretario` |
| DELETE | `docentes/{docente}/huellas/{huella}` | `docentes.huellas.destroy` | `DocenteHuellaController@destroy` | web, auth, `EnsureRole:secretario` |
| GET\|HEAD | `home` | `home` | Closure `routes/web.php:26` | web, auth |
| GET\|HEAD | `lector` | `lector.diagnostico` | `LectorController@diagnostico` | web, auth, `EnsureRole:secretario` |
| GET\|HEAD | `lector/comandos/{comando}` | `lector.estado` | `LectorController@estado` | web, auth |
| DELETE | `lector/comandos/{comando}` | `lector.cancelar` | `LectorController@cancelar` | web, auth |
| GET\|HEAD | `lector/en-linea` | `lector.en-linea` | `LectorController@enLinea` | web, auth |
| GET\|HEAD | `login` | `login` | `Auth\LoginController@showLoginForm` | web |
| POST | `login` | — | `Auth\LoginController@login` | web, `throttle:10,1` |
| POST | `logout` | `logout` | `Auth\LoginController@logout` | web, auth |
| GET\|HEAD | `storage/{path}` | `storage.local` | Framework FilesystemServiceProvider | No aparece middleware en `route:list -vv` |
| PUT | `storage/{path}` | `storage.local.upload` | Framework FilesystemServiceProvider | No aparece middleware en `route:list -vv` |
| GET\|HEAD | `up` | — | Framework health route | No aparece middleware en `route:list -vv` |

Las rutas web y API de aplicación se definen en `routes/web.php:17-62` y `routes/api.php:11-14`. El grupo web aplica autenticación en `routes/web.php:22`; los grupos por rol están en `routes/web.php:36-62`. El middleware de API exige Bearer token, no rol (`routes/api.php:8-14`, `app/Http/Middleware/VerificarTokenDy50.php:9-19`).

### Matriz de permisos por rol

| Área | Secretario | Jefe de preceptores | Docente |
|---|---:|---:|---:|
| Inicio autenticado `/home` | Sí | Sí | Sí; redirige a panel docente |
| Consultar, editar y guardar asistencias | Sí | Sí | No |
| Alta/edición/baja/activación de docentes | Sí | No | No |
| Enrolar o desvincular huellas | Sí | No | No |
| Crear cuentas | Sí | No | No |
| Pantalla de diagnóstico del lector | Sí | No | No |
| Panel y solicitud de fichaje propio | No | No | Sí |
| Consultar estado del lector/orden | Sí | Sí | Sí; el estado/cancelación se limita a su propio comando |
| API DY50 | No por rol web | No por rol web | No por rol web; el agente usa token API |

La matriz resulta de los grupos de rutas y del menú (`routes/web.php:31-62`, `resources/views/components/navbar.blade.php:13-45`). `estado` y `cancelar` verifican que el comando pertenezca al usuario autenticado (`app/Http/Controllers/LectorController.php:22-45`). El método `EnsureRole` rechaza roles no permitidos (`app/Http/Middleware/EnsureRole.php:14-30`).

## 4. Base de datos y migraciones

Los tipos son los declarados con Laravel Blueprint; el tipo SQL concreto depende del driver. El ejemplo de configuración selecciona MySQL (`.env.example:11-16`). `id()` es clave primaria autoincremental; `foreignId()` es un identificador unsigned grande; `timestamps()` agrega `created_at`/`updated_at`. Columnas no marcadas como `nullable()` son obligatorias. Se indican defaults explícitos cuando existen.

### `0001_01_01_000000_create_users_table.php`

- `users`: `id`; `usuario` string(100) UNIQUE; `dni` string(10) UNIQUE; `email` string UNIQUE; `password` string; `rol` string(30); `remember_token` nullable; `created_at`, `updated_at`. `docente_id` se agrega en la migración de docentes (`database/migrations/0001_01_01_000000_create_users_table.php:11-20`, `database/migrations/2026_11_01_000001_create_docentes_table.php:22-26`).
- `password_reset_tokens`: `email` string PK; `token` string; `created_at` timestamp nullable (`database/migrations/0001_01_01_000000_create_users_table.php:22-26`).
- `sessions`: `id` string PK; `user_id` foreignId nullable e indexado, **sin constraint FK declarada**; `ip_address` string(45) nullable; `user_agent` text nullable; `payload` longText; `last_activity` integer indexado (`database/migrations/0001_01_01_000000_create_users_table.php:28-35`).

### `0001_01_01_000001_create_cache_table.php`

- `cache`: `key` string PK; `value` mediumText; `expiration` bigInteger indexado.
- `cache_locks`: `key` string PK; `owner` string; `expiration` bigInteger indexado.
- No declara FKs ni defaults (`database/migrations/0001_01_01_000001_create_cache_table.php:14-24`).

### `0001_01_01_000002_create_jobs_table.php`

- `jobs`: `id`; `queue` string indexado; `payload` longText; `attempts` unsignedSmallInteger; `reserved_at` unsignedInteger nullable; `available_at`, `created_at` unsignedInteger.
- `job_batches`: `id` string PK; `name` string; `total_jobs`, `pending_jobs`, `failed_jobs` integer; `failed_job_ids` longText; `options` mediumText nullable; `cancelled_at` integer nullable; `created_at` integer; `finished_at` integer nullable.
- `failed_jobs`: `id`; `uuid` string UNIQUE; `connection`, `queue` string; `payload`, `exception` longText; `failed_at` timestamp `useCurrent`; índice compuesto `(connection, queue, failed_at)`. Sin FKs declaradas (`database/migrations/0001_01_01_000002_create_jobs_table.php:14-47`).

### `2026_11_01_000001_create_docentes_table.php`

- `docentes`: `id`; `dni` string(10) UNIQUE; `nombre`/`apellido` string(50); `telefono` string(20) nullable; `email` string(100) nullable; `activo` boolean default `true`; timestamps.
- `users.docente_id`: foreignId nullable UNIQUE, FK a `docentes`, `ON DELETE SET NULL` (`database/migrations/2026_11_01_000001_create_docentes_table.php:11-26`).

### `2026_11_01_000002_create_huellas_table.php`

- `huellas`: `id`; `docente_id` FK a docentes con `ON DELETE CASCADE`; `sensor_id` unsignedSmallInteger UNIQUE; `dedo` string(40) nullable; timestamps. No guarda plantilla biométrica (`database/migrations/2026_11_01_000002_create_huellas_table.php:14-22`).

### `2026_11_01_000003_create_materias_y_horarios_tables.php`

- `materias`: `id`; `nombre` string(70) UNIQUE; timestamps.
- `horarios`: `id`; `docente_id` FK a docentes con CASCADE; `materia_id` FK a materias con RESTRICT; `curso`/`division` string(10); `turno` string(20); `dia_semana` unsignedTinyInteger; `hora_inicio`/`hora_fin` time; `deleted_at` de soft delete nullable; timestamps. Índices `(docente_id, dia_semana)` y `(curso, division, dia_semana)`; no hay UNIQUE compuesto declarado (`database/migrations/2026_11_01_000003_create_materias_y_horarios_tables.php:12-35`).

### `2026_11_01_000004_create_dy50_comandos_table.php`

- `dy50_comandos`: `id`; `dispositivo` string(50) indexado; `tipo` string(20); `estado` string(20) default `pendiente` e indexado; `user_id`, `docente_id`, `horario_id` foreignId nullable con `ON DELETE SET NULL`; `sensor_id`, `confianza` unsignedSmallInteger nullable; `dedo` string(40) nullable; `timeout_segundos` unsignedSmallInteger default `20`; `progreso` string(160) nullable; `mensaje` string(255) nullable; `expira_at` timestamp obligatorio; `reclamado_at`, `finalizado_at` nullable; timestamps. Índice compuesto `(dispositivo, estado, id)` (`database/migrations/2026_11_01_000004_create_dy50_comandos_table.php:16-37`).

### `2026_11_01_000005_create_asistencias_table.php`

- `asistencias`: `id`; `fecha` date; `docente_id` FK RESTRICT; `horario_id` FK RESTRICT; `comando_id` foreignId nullable UNIQUE con FK a comandos y `ON DELETE SET NULL`; `hora_ingreso`, `hora_egreso` time nullable; `estado` string(20); timestamps. UNIQUE `(fecha, docente_id, horario_id)` e índice `fecha` (`database/migrations/2026_11_01_000005_create_asistencias_table.php:11-28`).
- `asistencia_ediciones`: `id`; `asistencia_id` FK a asistencias CASCADE; `user_id` nullable FK a users SET NULL; `antes`, `despues` JSON obligatorios; timestamps (`database/migrations/2026_11_01_000005_create_asistencias_table.php:30-38`).

### Diagrama ER del esquema de aplicación

```mermaid
erDiagram
    DOCENTES ||--o| USERS : "users.docente_id UNIQUE"
    DOCENTES ||--o{ HUELLAS : "docente_id"
    DOCENTES ||--o{ HORARIOS : "docente_id"
    MATERIAS ||--o{ HORARIOS : "materia_id"
    USERS o|--o{ DY50_COMANDOS : "user_id"
    DOCENTES o|--o{ DY50_COMANDOS : "docente_id"
    HORARIOS o|--o{ DY50_COMANDOS : "horario_id"
    DOCENTES ||--o{ ASISTENCIAS : "docente_id"
    HORARIOS ||--o{ ASISTENCIAS : "horario_id"
    DY50_COMANDOS o|--o| ASISTENCIAS : "comando_id UNIQUE"
    ASISTENCIAS ||--o{ ASISTENCIA_EDICIONES : "asistencia_id"
    USERS o|--o{ ASISTENCIA_EDICIONES : "user_id"

    USERS {
      bigint id PK
      varchar usuario UK
      varchar dni UK
      varchar email UK
      varchar rol
      bigint docente_id FK_UK
    }
    DOCENTES {
      bigint id PK
      varchar dni UK
      varchar nombre
      varchar apellido
      boolean activo
    }
    HUELLAS {
      bigint id PK
      bigint docente_id FK
      smallint sensor_id UK
      varchar dedo
    }
    MATERIAS {
      bigint id PK
      varchar nombre UK
    }
    HORARIOS {
      bigint id PK
      bigint docente_id FK
      bigint materia_id FK
      tinyint dia_semana
      time hora_inicio
      time hora_fin
      timestamp deleted_at
    }
    DY50_COMANDOS {
      bigint id PK
      bigint user_id FK
      bigint docente_id FK
      bigint horario_id FK
      varchar tipo
      varchar estado
      smallint sensor_id
    }
    ASISTENCIAS {
      bigint id PK
      date fecha
      bigint docente_id FK
      bigint horario_id FK
      bigint comando_id FK_UK
      time hora_ingreso
      time hora_egreso
      varchar estado
    }
    ASISTENCIA_EDICIONES {
      bigint id PK
      bigint asistencia_id FK
      bigint user_id FK
      json antes
      json despues
    }
    PASSWORD_RESET_TOKENS {
      varchar email PK
      varchar token
      timestamp created_at
    }
    SESSIONS {
      varchar id PK
      bigint user_id
      varchar ip_address
      text user_agent
      longtext payload
      integer last_activity
    }
    CACHE {
      varchar key PK
      mediumtext value
      bigint expiration
    }
    CACHE_LOCKS {
      varchar key PK
      varchar owner
      bigint expiration
    }
    JOBS {
      bigint id PK
      varchar queue
      longtext payload
      smallint attempts
      integer reserved_at
      integer available_at
      integer created_at
    }
    JOB_BATCHES {
      varchar id PK
      varchar name
      integer total_jobs
      integer pending_jobs
      integer failed_jobs
      longtext failed_job_ids
      mediumtext options
      integer cancelled_at
      integer created_at
      integer finished_at
    }
    FAILED_JOBS {
      bigint id PK
      varchar uuid UK
      varchar connection
      varchar queue
      longtext payload
      longtext exception
      timestamp failed_at
    }
```

Las tablas Laravel `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs` y `password_reset_tokens` son auxiliares; no tienen relaciones FK de negocio declaradas en estas migraciones (`database/migrations/0001_01_01_000000_create_users_table.php:22-35`, `database/migrations/0001_01_01_000001_create_cache_table.php:14-24`, `database/migrations/0001_01_01_000002_create_jobs_table.php:14-47`).

## 5. Configuración DY50/asistencia y variables de entorno

- `config/dy50.php`: token tomado de `DY50_TOKEN`; dispositivo desde `DY50_DISPOSITIVO`, fallback `raspberry-1`; capacidad desde `DY50_CAPACIDAD`, fallback **100**; umbral en línea **15 s**; espera para que el agente tome la orden **35 s**; verificación **20 s**; enrolamiento total **90 s** (`config/dy50.php:4-22`).
- Cada apoyo individual del dedo en el enrolamiento tiene límite **15 s** (`dy50_agente.py:33`, `dy50_agente.py:153-156`).
- `config/asistencia.php`: anticipación **20 minutos**; tolerancia desde inicio **20 minutos** (`config/asistencia.php:3-9`).
- Nombres de variables definidos por `.env.example` (se enumeran sin valores): `APP_NAME`, `APP_ENV`, `APP_KEY`, `APP_DEBUG`, `APP_URL`, `LOG_CHANNEL`, `LOG_DEPRECATIONS_CHANNEL`, `LOG_LEVEL`, `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, `DY50_TOKEN`, `DY50_DISPOSITIVO`, `DY50_CAPACIDAD`, `ADMIN_USUARIO`, `ADMIN_DNI`, `ADMIN_EMAIL`, `ADMIN_PASSWORD` (`.env.example:1-25`).
- En el `.env` local también aparece el nombre `DY50_API_TOKEN`; no figura en `.env.example` y la búsqueda en la aplicación no encontró referencias de código a ese identificador. Se informa solo el nombre y la línea, sin valor (`.env:18`; el código toma `DY50_TOKEN` en `config/dy50.php:5`, `dy50_agente.py:198-200`).

## 6. Capacidad del lector y asignación de posiciones

- El ejemplo de entorno declara `DY50_CAPACIDAD=150` (`.env.example:20`). La capacidad **fallback** del código es 100, por lo que 150 solo es el valor de ejemplo/override, no el default (`config/dy50.php:10-12`).
- `SensorDY50.capacidad()` consulta los parámetros del hardware (`CMD_PARAMETROS`) y decodifica el campo de capacidad que devuelve el sensor (`sensor_dy50.py:55`, `sensor_dy50.py:168-173`). `buscar()` usa esa capacidad reportada por el sensor para definir el rango de búsqueda (`sensor_dy50.py:154-159`).
- La asignación Laravel de posiciones libres **no consulta esa capacidad física**: `proximoSensorLibre()` busca entre `1` y `config('dy50.capacidad')`, excluyendo posiciones vinculadas o reservadas (`app/Http/Controllers/DocenteHuellaController.php:59-78`).
- **Discrepancia/riesgo de configuración:** no se encontró comparación automática entre la capacidad configurada en Laravel y la reportada por hardware. Si el máximo configurado supera el del lector, Laravel podría encolar una posición fuera de rango; el sensor puede rechazarla (`config/dy50.php:10-12`, `app/Http/Controllers/DocenteHuellaController.php:72-78`, `sensor_dy50.py:168-173`, `sensor_dy50.py:267-290`).

## 7. Entrada, egreso y reglas de FichajeService

- El fichaje automático revalida el horario y crea `hora_ingreso` con la hora actual, pero escribe `hora_egreso = null` (`app/Services/FichajeService.php:85-100`). El comando de ausencias también deja ambos campos nulos (`app/Console/Commands/MarcarAusentes.php:37-48`).
- No se encontró un flujo automático de salida/egreso desde la Raspberry ni una ruta dedicada. El formulario administrativo sí permite editar manualmente `hora_egreso`; `AsistenciaController::update` valida y guarda el valor recibido (`app/Http/Controllers/AsistenciaController.php:56-84`; formulario `resources/views/asistencias/edit.blade.php:30-43`). Por lo tanto: el sistema registra automáticamente **entrada**; egreso solo se puede completar manualmente desde la edición administrativa.
- `FichajeService::validar()` exige ficha activa, horario asignado al docente, día actual, horario con fin posterior al inicio, instante desde 20 min antes del inicio y anterior al fin; impide repetir docente+horario+fecha (`app/Services/FichajeService.php:22-65`, `config/asistencia.php:3-8`).
- `estadoPara()` clasifica como `Presente` antes del inicio, `SobreLaHora` antes de inicio+tolerancia y `Tarde` desde el límite de tolerancia (`app/Services/FichajeService.php:69-80`).
- `registrar()` vuelve a validar, guarda fecha, docente, horario, comando, entrada y estado; atrapa una violación de unicidad y la convierte en rechazo de negocio (`app/Services/FichajeService.php:85-101`). No asigna egreso.

## 8. Reportes, funciones por rol y edición de asistencias

- Función dedicada de reporte/exportación (PDF, Excel, CSV o descarga): **No encontrado** en `app/`, `routes/`, `resources/` y `public/` (búsqueda de `export`, `reporte`, `download`, `Excel`, `CSV`, `PDF`, `Maatwebsite` y `PhpSpreadsheet` sin coincidencias). Lo encontrado es una consulta filtrable por fecha/docente y paginada (`app/Http/Controllers/AsistenciaController.php:16-36`, `resources/views/asistencias/index.blade.php:12-24`, `resources/views/asistencias/index.blade.php:82-87`).
- **Jefe de preceptores:** puede ver y editar asistencias (`routes/web.php:42-45`); su menú muestra Inicio y Asistencias, no gestión de docentes/cuentas/lector (`resources/views/components/navbar.blade.php:13-20`, `resources/views/components/navbar.blade.php:22-35`).
- **Secretario:** puede consultar/editar asistencias y además administrar fichas de docentes, activar/desactivar, gestionar huellas, crear cuentas y ver diagnóstico del lector (`routes/web.php:42-62`; menú `resources/views/components/navbar.blade.php:22-35`).
- **Docente:** tiene panel propio y puede solicitar registrar asistencia (`routes/web.php:36-38`). El controlador carga horarios del día y la elegibilidad de cada uno (`app/Http/Controllers/DocentePanelController.php:20-58`). No tiene acceso a `/asistencias` ni a las rutas de gestión.
- **Quién puede editar asistencias:** Secretario y Jefe de preceptores, por middleware en `routes/web.php:42-45`. Los cambios quedan en `asistencia_ediciones` solo si cambian los valores (`app/Http/Controllers/AsistenciaController.php:56-98`).
- La API DY50 está autenticada por token y no por un rol de la interfaz (`routes/api.php:8-14`).

## 9. Apariciones de “prosecretario”

Búsqueda insensible a mayúsculas en archivos de aplicación, vistas, enums, comentarios, SQL y documentación de texto, más extracción de texto/tablas de los `.docx` de raíz:

| Archivo | Línea/ubicación | Contexto |
|---|---:|---|
| `asistencia_docente.sql` | 113 | Comentario de estructura |
| `asistencia_docente.sql` | 116 | `CREATE TABLE` del esquema histórico |
| `asistencia_docente.sql` | 117 | Columna identificadora del esquema histórico |
| `asistencia_docente.sql` | 166 | Comentario de índices |
| `asistencia_docente.sql` | 168 | `ALTER TABLE` de índices |
| `asistencia_docente.sql` | 169 | Clave primaria |
| `asistencia_docente.sql` | 206 | Comentario de autoincremento |
| `asistencia_docente.sql` | 208 | `ALTER TABLE` de autoincremento |
| `asistencia_docente.sql` | 209 | Columna autoincremental |
| `tests/Feature/UsuarioControllerTest.php` | 28 | Aserción que comprueba que la etiqueta no aparezca |

No se encontraron apariciones en `app/`, vistas Blade, enums, README ni en el texto/tablas de los dos `.docx` de raíz. El SQL es del prototipo histórico (`asistencia_docente.sql:21`, `asistencia_docente.sql:113-117`); la prueba confirma la etiqueta `Secretario` y la ausencia de esa antigua etiqueta (`tests/Feature/UsuarioControllerTest.php:24-29`).

## 10. Arranque, batch, escucha de Laravel y proxy

### `iniciar-sistema.bat`

Existe. Detecta la IP, presenta opciones HTTP/HTTPS, detener y salir (`iniciar-sistema.bat:15-37`). Para HTTP actualiza `APP_URL`, limpia cachés, abre el puerto 8000, inicia scheduler y lanza Laravel enlazado a `0.0.0.0:8000` (`iniciar-sistema.bat:39-55`, `iniciar-sistema.bat:104-112`). Para HTTPS ejecuta el preparador, usa la IP de `C:\dy50-tls\ip.txt`, actualiza `APP_URL`, abre 8443 y lanza Laravel en `127.0.0.1:8000`, scheduler y proxy en modo `sitio` (`iniciar-sistema.bat:62-92`). La opción detener cierra ventanas con títulos de esos procesos (`iniciar-sistema.bat:99-101`, `iniciar-sistema.bat:120-124`).

### Comandos manuales documentados

- Laravel: `php artisan optimize:clear` y `php artisan serve --host=127.0.0.1 --port=8000` (`README.md:68-74`).
- Scheduler: `php artisan schedule:work` (`README.md:77-80`).
- Proxy: `py -3 .\https_proxy.py ... --host 0.0.0.0 --port 8443 --backend 127.0.0.1:8000 --modo sitio` (`README.md:83-88`).

**Respuesta de escucha:** el arranque HTTPS/manual recomendado liga Laravel a **127.0.0.1**; el proxy público de la LAN escucha en **0.0.0.0**. El modo HTTP alternativo del `.bat` cambia Laravel a **0.0.0.0** (`README.md:73`, `README.md:85`, `iniciar-sistema.bat:46`, `iniciar-sistema.bat:83-85`).

### Proxy

- `https_proxy.py` está en la **raíz**, no en `tools/`; el `.bat` verifica que esté junto a sí mismo (`iniciar-sistema.bat:67-68`, `iniciar-sistema.bat:85`). Existe una copia diferente bajo `dy50-tls/`, no usada por los comandos documentados.
- Argumentos: `--cert` obligatorio, `--key` obligatorio, `--host` (default `0.0.0.0`), `--port` (default `8443`), `--backend` (default `127.0.0.1:8000`) y `--modo {api,sitio}` (default `api`) (`https_proxy.py:143-150`).

## 11. Scheduler y marcado de ausentes

Contenido de `routes/console.php`:

```php
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::command('asistencia:marcar-ausentes')
    ->everyMinute()
    ->withoutOverlapping();

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
```

La programación requiere `php artisan schedule:work` (`routes/console.php:7-14`); el comando de ausencias se ejecuta cada minuto y evita superponerse (`routes/console.php:7-10`).

`MarcarAusentes::handle()` busca horarios del día actual ya terminados, de docentes activos, que no tengan asistencia para la fecha; rechaza horario inválido o creado después de la hora de finalización. Inserta una fila `Ausente` con entrada/egreso nulos y tolera una colisión de unicidad concurrente (`app/Console/Commands/MarcarAusentes.php:15-53`). Mensaje/contador final se emite al terminar (`app/Console/Commands/MarcarAusentes.php:55-58`). El scheduler debe permanecer activo con `php artisan schedule:work` (`routes/console.php:7`).

## 12. Agente Python y `dy50-agente.service`

### Argumentos y variables de entorno

| Opción | Uso/default |
|---|---|
| `--url` | URL base; también `DY50_URL`; sin default fijo |
| `--token` | Token de API; también `DY50_TOKEN`; sin default fijo |
| `--dispositivo` | `DY50_DISPOSITIVO`; default `raspberry-1` |
| `--puerto` | Serial; default `/dev/serial0`, admite por ejemplo `/dev/ttyUSB0` |
| `--baudios` | Default `57600` |
| `--cafile` | CA que valida TLS; opcional sintácticamente |
| `--no-verificar-tls` | Desactiva validación TLS, solo para pruebas |
| `--intervalo` | Consulta cada `1.0` segundo cuando no hay comando |
| `--simulado` | Usa sensor simulado |
| `--simulado-posicion` | Posición simulada default `1` |

Los argumentos se declaran en `dy50_agente.py:196-207`; las variables de entorno reconocidas y validación de URL/token están en `dy50_agente.py:17`, `dy50_agente.py:198-211`; el loop aplica el intervalo y backoff de red en `dy50_agente.py:228-241`.

### Contenido de `dy50-agente.service` (token omitido)

```ini
# Opcional: arrancar el agente solo al encender la Raspberry.
#   sudo cp dy50-agente.service /etc/systemd/system/
#   sudo nano /etc/systemd/system/dy50-agente.service (ajustar URL, TOKEN y rutas)
#   sudo systemctl daemon-reload && sudo systemctl enable --now dy50-agente
#   journalctl -u dy50-agente -f
[Unit]
Description=Agente del lector DY50
After=network-online.target
Wants=network-online.target

[Service]
User=alumnos
WorkingDirectory=/home/alumnos/dy50
Environment=DY50_URL=[VALOR OMITIDO]
Environment=DY50_TOKEN=[REDACTADO]
ExecStart=/usr/bin/python3 /home/alumnos/dy50/dy50_agente.py --cafile /home/alumnos/certs/ca.crt
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
```

Las directivas corresponden a `dy50-agente.service:1-21`; se omite deliberadamente el valor de la URL de la unidad además del token para respetar la regla de mostrar de `.env` solo nombres de variables. La unidad depende de `DY50_URL`, `DY50_TOKEN`, certificado y rutas; la configuración mostrada es una plantilla que hay que adaptar.

## 13. Modelo/protocolo de sensor mencionado

El comentario de `sensor_dy50.py` nombra DY50 y compatibilidad con módulos **R307 / ZFM-60**; menciona como alternativas de biblioteca `pyfingerprint` y `adafruit_fingerprint` (`sensor_dy50.py:2-13`). El código implementa paquete serial con cabecera `EF01`, dirección, PID, longitud, checksum y comandos como captura, búsqueda, borrado y vaciado (`sensor_dy50.py:27-57`, `sensor_dy50.py:104-119`). El repositorio no identifica fabricante/revisión física exacta del módulo instalado: **No encontrado**.

## 14. JavaScript frontend e inactividad

- `public/js/lector.js`: expone `window.Lector`; gestiona token CSRF, envío JSON con `fetch`, sondeo periódico de estado y vigilancia de conexión (`public/js/lector.js:6-94`).
- `public/js/docente-panel.js`: habilita selección de horario si hay conexión, envía solicitud de fichaje, muestra progreso/resultado y permite cancelar/reintentar (`public/js/docente-panel.js:46-69`, `public/js/docente-panel.js:72-154`).
- **Inactividad del panel docente:** `SEGUNDOS_OCIO = 120`; reinicia temporizador ante `click`, `touchstart` o `keydown`; si no hay actividad envía el formulario de logout. El temporizador no se reinicia mientras hay comando activo (`public/js/docente-panel.js:9-10`, `public/js/docente-panel.js:32-43`).
- El resultado exitoso se muestra **5 s** antes de volver al login (`public/js/docente-panel.js:9`, `public/js/docente-panel.js:92-104`, `public/js/docente-panel.js:138`).
- `public/js/enrolar-huella.js`: solicitud de enrolamiento, actualización de avance, cancelación y recarga tras éxito (`public/js/enrolar-huella.js:26-91`).
- `public/js/horarios-form.js`: carga filas iniciales, clona la plantilla, asigna nombres de campos y agrega/quita horarios (`public/js/horarios-form.js:8-32`).

## 15. TLS: `preparar-https.ps1`

- No declara parámetros de entrada PowerShell (`preparar-https.ps1:1-10`). Usa `C:\dy50-tls` para CA, certificado, clave y registro de IP; fija el usuario SSH `alumnos` y destino `/home/alumnos/certs/ca.crt` (`preparar-https.ps1:3-10`).
- Exige que existan CA pública y clave privada, busca OpenSSL, solicita una IPv4 de Raspberry y determina/solicita la IP de la PC hacia esa Raspberry (`preparar-https.ps1:106-121`, `preparar-https.ps1:130-179`).
- `Test-ServerCertificate` comprueba que el certificado valide con la CA para la IP, tenga al menos 30 días de vigencia y que la clave pública coincida con la clave privada (`preparar-https.ps1:76-101`).
- Al crear certificado agrega `subjectAltName` de las IPs, lo verifica contra CA/IP/vigencia/clave antes de reemplazarlo (`preparar-https.ps1:201-244`).
- Requiere `ssh.exe`/`scp.exe`; crea el directorio remoto, copia solo `ca.crt` y verifica que exista (`preparar-https.ps1:251-270`). No se muestran ni reproducen claves/certificados.

## 16. Pruebas y resultados

### Laravel/PHP

| Archivo y test | Cobertura |
|---|---|
| `tests/Feature/ExampleTest.php:12-19` — `test_the_guest_is_redirected_to_the_login_page` | Comprueba que `/` redirija a login. |
| `tests/Feature/UsuarioControllerTest.php:14-43` — `test_secretario_can_create_another_secretary_account` | Con `RefreshDatabase`, prueba acceso de secretario, etiqueta de rol, ausencia de etiqueta antigua y alta de otra cuenta secretaria. |
| `tests/Unit/ExampleTest.php:12-15` — `test_that_true_is_true` | Prueba de ejemplo trivial. |

**Resultado ejecutado:** `php artisan test`: 3 pruebas aprobadas, 8 aserciones.

### Python

- `pruebas/test_agente.py:79-168` (15 tests): consulta con/sin comandos, autorización, red, verificación, huella desconocida, expiración/cancelación, enrolamiento, duplicado, temporizadores, borrado y error de sensor.
- `pruebas/test_proxy.py:95-103` (2 tests): deja pasar las rutas válidas del agente y bloquea otras.
- `pruebas/test_sensor.py:98-207` (18 tests): password/capacidad, lectura y búsqueda, timeout, cancelación, imágenes borrosas, enrolamiento/duplicados, posición inválida, borrado/vaciado y respuestas rotas/sin respuesta.

**Resultado ejecutado:** `py -3 -m unittest discover -s pruebas -v`: 35 tests aprobados. La salida incluyó `ResourceWarning` de `HTTPError`/sockets no cerrados, pero los tests terminaron `OK`.

## 17. Git: rama, ramas y remoto

Datos obtenidos de `git branch --show-current`, `git branch -a` y `git remote -v`, saneando credenciales de las URL:

- **Rama actual:** `Reorganizacion-2`.
- **Ramas locales:** `Arreglos`, `Prueba-Server-2`, `Pruebra-Interfaz-1`, `Reorganizacion-1`, `Reorganizacion-2`, `Server}`, `develop`, `master`.
- **Ramas remotas:** `origin/Arreglos`, `origin/HEAD -> origin/master`, `origin/Prueba-Server-2`, `origin/Server}`, `origin/UsersInconsistencias.1`, `origin/master`, `origin/origin/api`.
- **Remoto:** `origin` → `https://github.com/joaquingomez24/Proyecto-asistencia-hardware-` (fetch/push).

Git guarda estos datos en `.git`, por lo que no hay una ruta de archivo fuente/`línea` equivalente para estas salidas.

## 18. Discrepancias entre código, README y guías

1. **Capacidad:** `.env.example` propone 150; el fallback de `config/dy50.php` es 100. El código de búsqueda del hardware usa su capacidad reportada; la reserva del enrolamiento usa solo `config('dy50.capacidad')`, sin comparación (`.env.example:20`, `config/dy50.php:10-12`, `sensor_dy50.py:154-173`, `app/Http/Controllers/DocenteHuellaController.php:72-78`). No se muestra el valor del `.env` local.
2. **Variable local adicional:** `.env` contiene el nombre `DY50_API_TOKEN`, pero la configuración Laravel y el agente consultan `DY50_TOKEN`; el nombre adicional no está en `.env.example` ni tiene referencias halladas en código (`.env:18`, `config/dy50.php:4-5`, `dy50_agente.py:198-211`, `.env.example:18-20`).
3. **Dirección de escucha en el batch:** README y arranque HTTPS usan Laravel en `127.0.0.1:8000`; la opción HTTP de `iniciar-sistema.bat` lo abre en `0.0.0.0:8000` y anuncia HTTP a la Raspberry (`README.md:71-85`, `iniciar-sistema.bat:39-54`). Es un modo alternativo del batch, no el mismo perfil que HTTPS; si se elige, el backend queda directamente en la red local y el transporte no es TLS.
4. **Node/Vite:** README enumera PHP, Composer, MySQL y Python, pero no Node/npm (`README.md:15-21`). El script Composer `setup` sí ejecuta `npm install`/`npm run build`, y `welcome.blade.php` puede usar Vite (`composer.json:35-41`, `package.json:5-14`, `resources/views/welcome.blade.php:12-13`). Para las vistas operativas enlazadas a `public/` no se encontró dependencia directa de Vite; es una omisión condicional, no un requisito probado del flujo normal.
5. **Proxy duplicado:** la ruta documentada y el batch señalan `https_proxy.py` de raíz; existe otra copia bajo `dy50-tls/` con contenido distinto. La guía de funcionamiento de raíz también advierte que el script usa `C:\dy50-tls`, no esa carpeta local (`README.md:83-85`, `iniciar-sistema.bat:67-68`, `iniciar-sistema.bat:85`; `Guia_funcionamiento_proyecto.docx`, sección 9.1, párrafos 155-156).
6. **Prototipo SQL frente a esquema actual:** README describe MySQL `asistencia_laravel` y Laravel (`README.md:17-19`); el SQL histórico declara base `asistencia_docente`, tablas singulares `asistencia`, `materia`, `huella_digital` y rol antiguo `prosecretario` (`asistencia_docente.sql:21-33`, `asistencia_docente.sql:59-117`). No es esquema de la aplicación actual y no debe importarse como sustituto de las migraciones.
7. **Guías de Word:** los comandos/IP y modo HTTPS en la guía de funcionamiento coinciden con README y código según sus apartados de inicio (párrafos 124-145). El catálogo de comandos también documenta `serve` en loopback y la IP/proxy actuales (párrafos 5-23). No se detectaron discrepancias de valor en esos puntos; las guías Word se citan por párrafo, no por línea. La limitación de Node/Vite y la diferencia de capacidad listadas arriba también aplican a esas guías porque no definen una versión Node ni un chequeo contra la capacidad física.
8. **Python mínimo:** la documentación dice Python 3, pero no fija versión mínima ni versión de `pyserial`; esto no contradice una versión declarada porque no existe una en el repositorio (`README.md:17-18`, `sensor_dy50.py:1-5`).
9. **Egreso/reporte:** ni README ni las rutas/servicios describen una captura automática de egreso o una exportación. La ruta de asistencia escribe egreso nulo y solo el formulario de edición lo acepta (`app/Services/FichajeService.php:85-100`, `app/Http/Controllers/AsistenciaController.php:56-84`). La consulta actual solo filtra y pagina (`app/Http/Controllers/AsistenciaController.php:16-36`).
10. **Herramientas/docs:** `https_proxy.py` está en la raíz y no se encontró una carpeta `tools/`; no existe `docs/`. Las guías actuales están en archivos `.docx` de la raíz, mientras README es Markdown (`README.md:1-3`; inventario de raíz).

## 19. No determinado / datos faltantes

| Dato | Estado | Qué falta |
|---|---|---|
| Versión mínima exacta de Python | No encontrado | Declararla en README o en un manifiesto de proyecto Python. |
| Versión exacta/min Node y lock reproducible de npm | No encontrado | `engines.node` y `package-lock.json` (o equivalente) si Node debe ser reproducible/requerido. |
| Versión instalada de pyserial en la Raspberry de destino | No encontrado | Inventario de paquetes de esa Raspberry; el repo solo nombra `pyserial`. |
| Capacidad física concreta del DY50 conectado | No encontrado en repo | Consulta `sensor.capacidad()` al hardware; el código implementa la lectura pero no conserva el resultado del dispositivo real (`sensor_dy50.py:168-173`). |
| Valor efectivo de `DY50_CAPACIDAD` del `.env` | Omitido intencionalmente | No se reproduce ningún valor de `.env`; el informe usa solo el nombre y el default/ejemplo versionado. |
| Fabricante/revisión exacta del lector instalado | No encontrado | Identificación del módulo físico; el comentario solo menciona compatibilidad R307/ZFM-60 (`sensor_dy50.py:2-5`). |
| Detalle de archivos binarios y artefactos externos | Parcial | El árbol clasifica por nombre/uso visible; claves/certificados no fueron abiertos ni inspeccionados por contenido. |
| Line numbers de Git y Word | No aplicable | Git branch/remotes son metadatos de `.git`; Word es un formato binario sin líneas fuente. Se citan comandos y párrafos. |

## Método y resultados de ejecución

- Inventario de rutas: `php artisan route:list -vv` → 32 rutas.
- Pruebas Laravel: `php artisan test` → 3 tests, 8 assertions, aprobados.
- Pruebas Python: `py -3 -m unittest discover -s pruebas -v` → 35 tests, aprobados; se observaron warnings de recursos al limpiar sockets/HTTPError.
- Búsquedas de `prosecretario` en código/documentación de texto excluyeron dependencias de `vendor/` y `.git`; el texto y las tablas de los dos `.docx` de raíz también se inspeccionaron. No se inspeccionaron valores de secretos de `.env` ni contenidos de claves/certificados.

## Anexo A – Segunda ronda de verificación

- **Versiones:** PHP CLI 8.5.4; Composer 2.10.3, ejecutado con PHP 8.5.4.
- **Comparación de `https_proxy.py`:** el de raíz limita el modo API a tres rutas exactas, restringe cuerpos a 4 KiB y ofrece modo sitio; `dy50-tls/https_proxy.py` admite todo el prefijo `/api/dy50/`, solo GET/POST y cuerpos hasta 1 MiB. El primero permite configurar backend/modo; el segundo fija el backend en `127.0.0.1:8000`.
- **Variables (nombres y ubicaciones):**
  - `DY50_API_TOKEN`: `.env:18`; también aparece como texto de ayuda en `archivosDeProceso/dy50_diagnostico_https.py:309`, sin leer la variable. No se hallaron usos por la aplicación; se puede borrar la asignación de `.env:18` sin afectar el token activo `DY50_TOKEN` (`.env:20`).
  - `DY50_TOKEN`: `.env:20`, `.env.example:18`, `config/dy50.php:4-5`, `app/Http/Middleware/VerificarTokenDy50.php:9`, `app/Http/Controllers/Api/Dy50Controller.php:17`, `routes/api.php:8`, `dy50_agente.py:17, 86, 199, 211`, `dy50-agente.service:15`, `iniciar-sistema.bat:54, 92`, `README.md:32, 95, 98`.
- **Capacidad:** el fallback de `config/dy50.php` pasa a 150, coincidiendo con `DY50_CAPACIDAD` en `.env.example`; un `DY50_CAPACIDAD` de entorno prevalece sobre el fallback. La búsqueda del sensor consulta su capacidad reportada, pero Laravel reserva posiciones hasta el límite configurado. Conviene mantener ese límite en la capacidad física del lector para no asignar una posición inexistente.
