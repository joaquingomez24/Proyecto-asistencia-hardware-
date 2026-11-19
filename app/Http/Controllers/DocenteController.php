<?php

namespace App\Http\Controllers;

use App\Enums\DiaSemana;
use App\Http\Requests\DocenteRequest;
use App\Models\Docente;
use App\Models\Dy50Comando;
use App\Services\HorarioService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Alta, edición y baja de docentes (solo secretario). */
class DocenteController extends Controller
{
    public function __construct(private HorarioService $horarios)
    {
    }

    public function index(Request $request)
    {
        $docentes = Docente::query()
            ->withCount('huellas')
            ->when($request->input('buscar'), function ($q, $texto) {
                $q->where(fn ($w) => $w
                    ->where('nombre', 'like', "%{$texto}%")
                    ->orWhere('apellido', 'like', "%{$texto}%")
                    ->orWhere('dni', 'like', "%{$texto}%"));
            })
            ->orderBy('apellido')
            ->orderBy('nombre')
            ->get();

        return view('docentes.index', compact('docentes'));
    }

    public function create()
    {
        return view('docentes.create', ['dias' => DiaSemana::laborables()]);
    }

    public function store(DocenteRequest $request)
    {
        $filas = $request->input('horarios', []);

        if ($errores = $this->horarios->conflictos($filas)) {
            return back()->withInput()->withErrors(['horarios' => $errores]);
        }

        DB::transaction(function () use ($request, $filas) {
            $docente = Docente::create($request->safe()->only(['dni', 'nombre', 'apellido', 'telefono', 'email']));
            $this->horarios->sincronizar($docente, $filas);
        });

        return redirect()->route('docentes.index')->with('exito', 'Docente y horarios guardados correctamente.');
    }

    public function edit(Docente $docente)
    {
        $docente->load(['horarios.materia', 'huellas']);

        return view('docentes.edit', [
            'docente' => $docente,
            'dias' => DiaSemana::laborables(),
        ]);
    }

    public function update(DocenteRequest $request, Docente $docente)
    {
        $filas = $request->input('horarios', []);

        if ($errores = $this->horarios->conflictos($filas, $docente)) {
            return back()->withInput()->withErrors(['horarios' => $errores]);
        }

        DB::transaction(function () use ($request, $docente, $filas) {
            $docente->update($request->safe()->only(['dni', 'nombre', 'apellido', 'telefono', 'email']));

            // El DNI de una cuenta docente es el de su ficha: se mantienen iguales.
            $docente->usuario?->update(['dni' => $docente->dni]);

            $this->horarios->sincronizar($docente, $filas);
        });

        return redirect()->route('docentes.index')->with('exito', 'Docente actualizado correctamente.');
    }

    public function activar(Docente $docente)
    {
        $docente->update(['activo' => true]);

        return back()->with('exito', "{$docente->nombre_completo} fue activado.");
    }

    public function desactivar(Docente $docente)
    {
        $docente->update(['activo' => false]);

        return back()->with('exito', "{$docente->nombre_completo} fue desactivado. Ya no puede iniciar sesión ni registrar asistencia.");
    }

    public function destroy(Docente $docente)
    {
        if ($docente->asistencias()->exists()) {
            return back()->withErrors([
                'docente' => 'Este docente tiene asistencias registradas y no se puede eliminar. Desactivalo para conservar el historial.',
            ]);
        }

        DB::transaction(function () use ($docente) {
            // Pide al lector que libere las plantillas (si el agente está conectado).
            foreach ($docente->huellas as $huella) {
                Dy50Comando::nuevo(Dy50Comando::BORRAR, 10, [
                    'user_id' => auth()->id(),
                    'sensor_id' => $huella->sensor_id,
                ]);
            }

            $docente->usuario?->delete();
            $docente->delete();
        });

        return redirect()->route('docentes.index')->with('exito', 'Docente eliminado.');
    }
}
