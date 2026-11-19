<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class Dy50Comando extends Model
{
    protected $table = 'dy50_comandos';

    public const VERIFICAR = 'verificar';

    public const ENROLAR = 'enrolar';

    public const BORRAR = 'borrar';

    public const PENDIENTE = 'pendiente';

    public const EN_CURSO = 'en_curso';

    public const COMPLETADO = 'completado';

    public const ERROR = 'error';

    public const CANCELADO = 'cancelado';

    public const EXPIRADO = 'expirado';

    protected $fillable = [
        'dispositivo', 'tipo', 'estado', 'user_id', 'docente_id', 'horario_id',
        'sensor_id', 'confianza', 'dedo', 'timeout_segundos',
        'progreso', 'mensaje', 'expira_at', 'reclamado_at', 'finalizado_at',
    ];

    protected function casts(): array
    {
        return [
            'expira_at' => 'datetime',
            'reclamado_at' => 'datetime',
            'finalizado_at' => 'datetime',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function docente(): BelongsTo
    {
        return $this->belongsTo(Docente::class);
    }

    public function horario(): BelongsTo
    {
        return $this->belongsTo(Horario::class)->withTrashed();
    }

    /** Crea un comando listo para que lo tome el agente. */
    public static function nuevo(string $tipo, int $timeoutSegundos, array $datos = []): self
    {
        return self::create($datos + [
            'dispositivo' => config('dy50.dispositivo'),
            'tipo' => $tipo,
            'estado' => self::PENDIENTE,
            'timeout_segundos' => $timeoutSegundos,
            'expira_at' => now()->addSeconds(config('dy50.espera_inicio')),
        ]);
    }

    public function terminado(): bool
    {
        return in_array($this->estado, [self::COMPLETADO, self::ERROR, self::CANCELADO, self::EXPIRADO], true);
    }

    /** Cierra el comando solo si todavía estaba abierto. Devuelve true si lo cerró. */
    public function finalizar(string $estado, ?string $mensaje = null, array $extra = []): bool
    {
        $filas = self::query()
            ->whereKey($this->getKey())
            ->whereIn('estado', [self::PENDIENTE, self::EN_CURSO])
            ->update($extra + [
                'estado' => $estado,
                'mensaje' => $mensaje !== null ? mb_substr($mensaje, 0, 255) : null,
                'finalizado_at' => now(),
                'updated_at' => now(),
            ]);

        $this->refresh();

        return $filas === 1;
    }

    /**
     * Marca como vencidos los comandos que superaron su plazo.
     * Pendiente vencido = nadie lo tomó (lector apagado). En curso vencido = no se apoyó el dedo.
     */
    public static function vencer(): void
    {
        $ahora = now();

        self::query()->where('estado', self::PENDIENTE)->where('expira_at', '<', $ahora)->update([
            'estado' => self::EXPIRADO,
            'mensaje' => 'El lector no respondió. Verificá que la Raspberry y el programa del lector estén encendidos.',
            'finalizado_at' => $ahora,
            'updated_at' => $ahora,
        ]);

        self::query()
            ->where('estado', self::EN_CURSO)
            ->where('tipo', self::ENROLAR)
            ->where('expira_at', '<', $ahora)
            ->update([
                'estado' => self::EXPIRADO,
                'mensaje' => 'El lector no informó el resultado del enrolamiento dentro del plazo. Revisá el sensor y volvé a intentarlo.',
                'finalizado_at' => $ahora,
                'updated_at' => $ahora,
            ]);

        self::query()
            ->where('estado', self::EN_CURSO)
            ->where('tipo', '!=', self::ENROLAR)
            ->where('expira_at', '<', $ahora)
            ->update([
                'estado' => self::EXPIRADO,
                'mensaje' => 'Se agotó el tiempo sin completar la lectura. Volvé a intentarlo.',
                'finalizado_at' => $ahora,
                'updated_at' => $ahora,
            ]);
    }

    /** El agente avisa que está vivo cada vez que consulta. */
    public static function marcarContacto(string $dispositivo): void
    {
        Cache::put("dy50:contacto:{$dispositivo}", now()->timestamp, now()->addDay());
    }

    /** Segundos desde el último contacto del agente, o null si nunca se conectó. */
    public static function segundosDesdeContacto(?string $dispositivo = null): ?int
    {
        $ultimo = Cache::get('dy50:contacto:'.($dispositivo ?? config('dy50.dispositivo')));

        return $ultimo === null ? null : max(0, now()->timestamp - (int) $ultimo);
    }

    public static function lectorEnLinea(?string $dispositivo = null): bool
    {
        $segundos = self::segundosDesdeContacto($dispositivo);

        return $segundos !== null && $segundos <= config('dy50.segundos_en_linea');
    }

    /** Lo que ve el navegador al consultar el estado. */
    public function paraCliente(): array
    {
        return [
            'id' => $this->id,
            'tipo' => $this->tipo,
            'estado' => $this->estado,
            'terminado' => $this->terminado(),
            'exito' => $this->estado === self::COMPLETADO,
            'progreso' => $this->progreso,
            'mensaje' => $this->mensaje,
        ];
    }
}
