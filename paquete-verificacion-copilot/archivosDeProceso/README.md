# Archivos de proceso

Esta carpeta conserva material que se utilizó durante etapas anteriores del proyecto, pero que ya no forma parte del flujo activo. Se guarda como referencia para entender decisiones y pruebas previas; no se debe copiar a la Raspberry ni ejecutar como parte del servicio actual.

## `dy50_diagnostico_https.py`

Era una herramienta manual para probar el sensor y enviar eventos a Laravel mediante `POST /api/dy50/eventos`. Esa integración fue reemplazada por `dy50_agente.py`, que consulta la cola de comandos de Laravel. La ruta de eventos ya no está registrada, por lo que este diagnóstico no funciona con la API actual.

## Elementos retirados

- Los seeders de docentes, huellas y usuarios contenían datos de ejemplo fijos y no eran llamados por `DatabaseSeeder`. El seeder activo crea la cuenta inicial a partir de variables locales de entorno.
- La vista antigua de creación de usuarios y la segunda hoja de estilos del panel docente no tenían referencias desde las rutas o vistas activas.
- Los modelos vacíos o sin referencias `DocenteMateria`, `JefeModificaAsistencia` y `JefePreceptor` pertenecían a tablas de un diseño anterior. El código vigente usa horarios y una bitácora de edición de asistencias.

Los archivos de esta carpeta son históricos. Si se recupera alguna idea, debe adaptarse y probarse contra el código y las rutas actuales.
