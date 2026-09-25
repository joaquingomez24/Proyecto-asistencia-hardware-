<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Docente;
use App\Models\Asistencia;
use App\Models\Materia;
use Carbon\Carbon;

class AsistenciaController extends Controller
{

    public function index(Request $request)
    {
        $query = Asistencia::query();

        if ($request->filled('fecha')) {
            $query->whereDate('fecha', $request->fecha);
        }

        if ($request->filled('docente')) {
            $query->where('nombre_docente', 'like', '%' . $request->docente . '%');
        }

        $asistencias = $query->orderBy('fecha', 'desc')->paginate(10);

        return view('asistencia.index', compact('asistencias'));
    }

    public function create(){
        return view('asistencia.registrar'); 
    }

    public function store(Request $request)
{
    $codigoHuella = trim($request->input('codigo_huella'));

    $ahora = Carbon::now();
    $hoy = $ahora->toDateString();
    $horaActual = $ahora->toTimeString();

    // 1. Mapear el día actual al español
    $diasSemana = [
        1 => 'Lunes',
        2 => 'Martes',
        3 => 'Miércoles',
        4 => 'Jueves',
        5 => 'Viernes',
        6 => 'Sábado',
        0 => 'Domingo',
    ];
    $diaActualNombre = $diasSemana[$ahora->dayOfWeek] ?? null;

    $docente = Docente::where('id_huella', (string) $codigoHuella)->first();

    if (!$docente) {
        return back()->with('error', 'Código de huella no encontrado.');
    }

    $nombreCompleto = $docente->nombre . ' ' . $docente->apellido;

    // 2. Obtener todas las materias del docente para el día de hoy
    $materiasHoy = $docente->materias()
        ->where('dia', $diaActualNombre)
        ->get();

    if ($materiasHoy->isEmpty()) {
        return back()->with('error', 'El docente no tiene materias asignadas para el día de hoy.');
    }

    // 3. Revisar materias pasadas del día sin registro de asistencia y marcarles 'Ausente'
    foreach ($materiasHoy as $mat) {
        $finMateria = Carbon::parse($hoy . ' ' . $mat->horario_finalizacion);
        
        // Si la materia ya terminó
        if ($ahora->greaterThan($finMateria)) {
            $registroPrevio = Asistencia::where('nombre_docente', $nombreCompleto)
                ->where('fecha', $hoy)
                ->where('materia', $mat->nombre)
                ->first();

            // Si no fichó nunca para esa materia pasada, creamos el Ausente
            if (!$registroPrevio) {
                Asistencia::create([
                    'fecha'          => $hoy,
                    'nombre_docente' => $nombreCompleto,
                    'materia'        => $mat->nombre,
                    'turno'          => $mat->turno,
                    'hora_ingreso'   => null,
                    'hora_egreso'    => null,
                    'estado'         => 'Ausente',
                ]);
            }
        }
    }

    // 4. Determinar la materia actual o más cercana al horario del fichaje
    $materiaActual = $materiasHoy->sortBy(function ($item) use ($ahora, $hoy) {
        $inicio = Carbon::parse($hoy . ' ' . $item->horario_inicio);
        return abs($ahora->diffInSeconds($inicio, false));
    })->first();

    $nombreMateria = $materiaActual->nombre;
    $turnoMateria  = $materiaActual->turno;

    // 5. Verificar si ya tiene ficha en la materia actual (para registrar Entrada o Salida)
    $asistencia = Asistencia::where('nombre_docente', $nombreCompleto)
        ->where('fecha', $hoy)
        ->where('materia', $nombreMateria)
        ->first();

    if (!$asistencia) {
        // Marcado de ENTRADA
        $estadoCalculado = 'Presente';

        if ($materiaActual->horario_inicio) {
            $inicioMateria = Carbon::parse($hoy . ' ' . $materiaActual->horario_inicio);
            $limiteTolerancia = $inicioMateria->copy()->addMinutes(15);

            if ($ahora->lessThanOrEqualTo($inicioMateria)) {
                $estadoCalculado = 'Presente';
            } elseif ($ahora->greaterThan($inicioMateria) && $ahora->lessThanOrEqualTo($limiteTolerancia)) {
                $estadoCalculado = 'Tarde';
            } else {
                $estadoCalculado = 'Ausente';
            }
        }

        Asistencia::create([
            'fecha'          => $hoy,
            'nombre_docente' => $nombreCompleto,
            'materia'        => $nombreMateria,
            'turno'          => $turnoMateria,
            'hora_ingreso'   => $horaActual,
            'hora_egreso'    => null,
            'estado'         => $estadoCalculado,
        ]);

        return back()->with('exito', "Entrada registrada a las {$horaActual} ({$nombreMateria}) - Estado: {$estadoCalculado}");
    } else {
        // Marcado de SALIDA
        $asistencia->update([
            'hora_egreso' => $horaActual,
        ]);

        return back()->with('exito', "Salida registrada a las {$horaActual} ({$nombreMateria})");
    }
}
    
    public function edit(Request $request){
        // probando
        if (auth()->user()->rol !== 'jefe_preceptores') {
            return redirect()->route('asistencia.index');
        }//fin
        $id = $request->query('id') ?? $request->id ?? array_key_first($request->query());

        $asistencia = Asistencia::findOrFail($id);

        $materias = Materia::select('nombre')->distinct()->get();

        return view('asistencia.edit', compact('asistencia', 'materias'));
    }

public function update(Request $request){
    // probando
        if (auth()->user()->rol !== 'jefe_preceptores') {
            return redirect()->route('asistencia.index')->with('exito', 'Los cambios se guardaron correctamente.');;
        }//fin
    $id = $request->query('id') ?? $request->id ?? array_key_first($request->query());

    $asistencia = Asistencia::findOrFail($id);

    $asistencia->update([
        'materia'      => $request->materia,
        'hora_ingreso' => $request->entrada,
        'hora_egreso'  => $request->salida,
        'estado'       => $request->estado,
    ]);

    return redirect()->route('asistencia.index')->with('exito', 'Asistencia actualizada correctamente.');
    }
}