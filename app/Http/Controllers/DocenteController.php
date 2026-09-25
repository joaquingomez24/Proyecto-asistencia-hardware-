<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Docente;
use App\Models\Materia;
use Illuminate\Support\Facades\DB;

class DocenteController extends Controller
{
    public function create()
    {
        if (auth()->user()->rol !== 'prosecretario') {
            return redirect()->route('home');
        }
        return view('docentes.create');
    }

    public function store(Request $request)
    {
        if (auth()->user()->rol !== 'prosecretario') {
            return redirect()->route('home');
        }

        // 1. Validar que el DNI sea único
        $request->validate([
            'DNI' => 'required|unique:docentes,DNI',
        ], [
            'DNI.required' => 'El DNI es obligatorio.',
            'DNI.unique'   => 'El DNI ingresado ya pertenece a otro docente registrado.'
        ]);

        $materias   = $request->input('materia', []);
        $turnos     = $request->input('turno', []);
        $cursos     = $request->input('curso', []);
        $divisiones = $request->input('division', []);
        $dias       = $request->input('dia', []);
        $entradas   = $request->input('entrada', []);
        $salidas    = $request->input('salida', []);

        $materiasAProcesar = [];

        // 2. Pre-procesar y validar solapamientos
        foreach ($materias as $index => $nombreMateria) {
            if (!empty($nombreMateria)) {
                $dia      = $dias[$index] ?? null;
                $inicio   = $entradas[$index] ?? null;
                $fin      = $salidas[$index] ?? null;
                $curso    = $cursos[$index] ?? null;
                $division = $divisiones[$index] ?? null;

                // A) Validar solapamientos dentro de las filas del mismo formulario
                foreach ($materiasAProcesar as $materiaCargada) {
                    if ($materiaCargada['dia'] === $dia) {
                        // Conflicto de docente cargando dos clases al mismo tiempo
                        if ($inicio < $materiaCargada['fin'] && $fin > $materiaCargada['inicio']) {
                            return redirect()->back()->withInput()->withErrors([
                                'solapamiento' => "Conflicto interno: El docente no puede dictar dos materias el día {$dia} de {$inicio} a {$fin}."
                            ]);
                        }

                        // Conflicto de curso recibiendo dos materias al mismo tiempo
                        if ($materiaCargada['curso'] == $curso && $materiaCargada['division'] == $division && ($inicio < $materiaCargada['fin'] && $fin > $materiaCargada['inicio'])) {
                            return redirect()->back()->withInput()->withErrors([
                                'solapamiento' => "Conflicto interno: El curso {$curso}° {$division}ª ya tiene asignada otra materia en el mismo horario ({$dia} de {$inicio} a {$fin})."
                            ]);
                        }
                    }
                }

                // B) Validar contra datos previos en la BD
                $errorBD = $this->validarConflictosHorarios(null, $dia, $inicio, $fin, $curso, $division);
                if ($errorBD) {
                    return redirect()->back()->withInput()->withErrors([
                        'solapamiento' => $errorBD
                    ]);
                }

                $materiasAProcesar[] = [
                    'nombre'   => $nombreMateria,
                    'turno'    => $turnos[$index] ?? null,
                    'curso'    => $curso,
                    'division' => $division,
                    'dia'      => $dia,
                    'inicio'   => $inicio,
                    'fin'      => $fin,
                ];
            }
        }

        // 3. Si pasó las validaciones, creamos al docente
        $docente = Docente::create([
            'DNI'       => $request->input('DNI', $request->input('dni')),
            'nombre'    => $request->input('nombre'),
            'apellido'  => $request->input('apellido'),
            'telefono'  => $request->input('telefono'),
            'email'     => $request->input('email'),
            'id_huella' => $request->input('id_huella'),
        ]);

        // 4. Guardar las materias
        foreach ($materiasAProcesar as $datosMateria) {
            $nuevaMateria = Materia::create([
                'nombre'               => $datosMateria['nombre'],
                'turno'                => $datosMateria['turno'],
                'curso'                => $datosMateria['curso'],
                'division'             => $datosMateria['division'],
                'dia'                  => $datosMateria['dia'],
                'horario_inicio'       => $datosMateria['inicio'],
                'horario_finalizacion' => $datosMateria['fin'],
            ]);

            DB::table('docentes_materias')->insert([
                'id_docente'  => $docente->id_docente,
                'id_materias' => $nuevaMateria->id_materias,
            ]);
        }

        // 5. Redirección con mensaje de éxito
        return redirect()->route('home')->with('exito', 'Docente y materias guardados correctamente.');
    }

    public function buscar(Request $request)
    {
        if (auth()->user()->rol !== 'prosecretario') {
            return redirect()->route('home');
        }

        $query = Docente::query();

        if ($request->filled('buscar')) {
            $term = $request->buscar;
            $query->where(function($q) use ($term) {
                $q->where('nombre', 'LIKE', "%{$term}%")
                  ->orWhere('apellido', 'LIKE', "%{$term}%")
                  ->orWhere('DNI', 'LIKE', "%{$term}%");
            });
        }

        $docentes = $query->get();

        return view('docentes.buscar', compact('docentes'));
    }

    public function edit($id)
    {
        if (auth()->user()->rol !== 'prosecretario') {
            return redirect()->route('home');
        }

        $docente = Docente::findOrFail($id);

        $materias = DB::table('materias')
            ->join('docentes_materias', 'materias.id_materias', '=', 'docentes_materias.id_materias')
            ->where('docentes_materias.id_docente', $id)
            ->select('materias.*')
            ->get();

        return view('docentes.edit', compact('docente', 'materias'));
    }

        public function update(Request $request, $id)
    {
        $request->validate([
            'materia'  => 'required|array',
            'turno'    => 'required|array',
            'curso'    => 'required|array',
            'division' => 'required|array',
            'entrada'  => 'required|array',
            'salida'   => 'required|array',
            'dia'      => 'required|array',
        ]);

        $docente = Docente::findOrFail($id);

        $materias   = $request->input('materia', []);
        $turnos     = $request->input('turno', []);
        $cursos     = $request->input('curso', []);
        $divisiones = $request->input('division', []);
        $entradas   = $request->input('entrada', []);
        $salidas    = $request->input('salida', []);
        $dias       = $request->input('dia', []);
        $idsMaterias = $request->input('id_materia', []);

        // 1. Validar solapamientos en el conjunto de horarios enviados en el mismo formulario
        for ($i = 0; $i < count($materias); $i++) {
            for ($j = $i + 1; $j < count($materias); $j++) {
                if ($dias[$i] === $dias[$j] && $entradas[$i] < $salidas[$j] && $salidas[$i] > $entradas[$j]) {
                    return back()->withErrors(['error' => "Conflicto interno: Se ingresaron horarios superpuestos el día {$dias[$i]}."])->withInput();
                }
            }
        }

        // 2. Validar contra la base de datos
        foreach ($materias as $index => $nombreMateria) {
            $materiaId = $idsMaterias[$index] ?? null;
            $dia       = $dias[$index];
            $entrada   = $entradas[$index];
            $salida    = $salidas[$index];
            $curso     = $cursos[$index];
            $div       = $divisiones[$index];

            $errorBD = $this->validarConflictosHorarios($id, $dia, $entrada, $salida, $curso, $div, $materiaId);
            if ($errorBD) {
                return back()->withErrors(['error' => $errorBD])->withInput();
            }

            // Conflicto del Docente (Mismo día y cruce de horario en sus otras materias)
            $conflictoDocente = Materia::join('docentes_materias', 'materias.id_materias', '=', 'docentes_materias.id_materias')
            ->where('docentes_materias.id_docente', $id)
            ->where('materias.dia', $dia)
            ->where('materias.id_materias', '!=', $materiaId)
            ->where(function ($query) use ($entrada, $salida) {
                $query->where('materias.horario_inicio', '<', $salida)
                    ->where('materias.horario_finalizacion', '>', $entrada);
            })
            ->exists();

            if ($conflictoDocente) {
                return back()->withErrors(['error' => "El docente ya tiene una clase asignada el {$dia} entre {$entrada} y {$salida}."])->withInput();
            }

            // Conflicto del Curso/División (Mismo día, curso, división y cruce de horario con cualquier profesor)
            $conflictoCurso = Materia::where('curso', $curso)
                ->where('division', $div)
                ->where('dia', $dia)
                ->where('id_materias', '!=', $materiaId)
                ->where(function ($query) use ($entrada, $salida) {
                    $query->where('horario_inicio', '<', $salida)
                        ->where('horario_finalizacion', '>', $entrada);
                })
                ->exists();

            if ($conflictoCurso) {
                return back()->withErrors(['error' => "El curso {$curso} {$div} ya tiene un profesor asignado el {$dia} entre {$entrada} y {$salida}."])->withInput();
            }
        }

        // 3. Si no hay conflictos, proceder con las actualizaciones del docente y materias...
            $docente->update([
            'nombre'   => $request->nombre,
            'apellido' => $request->apellido,
            'DNI'      => $request->DNI,
            'telefono' => $request->telefono,
            'email'    => $request->email,
        ]);

        // Recorrer y actualizar o insertar cada materia
        foreach ($materias as $index => $nombreMateria) {
            $materiaId = $idsMaterias[$index] ?? null;

            if ($materiaId) {
                // Si la materia ya existe en la base de datos, se actualiza por su ID
                Materia::where('id_materias', $materiaId)->update([
                    'nombre'               => $nombreMateria,
                    'turno'                => $turnos[$index] ?? null,
                    'curso'                => $cursos[$index] ?? null,
                    'division'             => $divisiones[$index] ?? null,
                    'dia'                  => $dias[$index] ?? null,
                    'horario_inicio'       => $entradas[$index] ?? null,
                    'horario_finalizacion' => $salidas[$index] ?? null,
                ]);
            } else {
                // Si se agregó un nuevo horario desde el JS, se crea en la BD y se asocia al docente
                $nuevaMateria = Materia::create([
                    'nombre'               => $nombreMateria,
                    'turno'                => $turnos[$index] ?? null,
                    'curso'                => $cursos[$index] ?? null,
                    'division'             => $divisiones[$index] ?? null,
                    'dia'                  => $dias[$index] ?? null,
                    'horario_inicio'       => $entradas[$index] ?? null,
                    'horario_finalizacion' => $salidas[$index] ?? null,
                ]);

                // Asociar en la tabla pivote docentes_materias
                \DB::table('docentes_materias')->insert([
                    'id_docente'  => $id,
                    'id_materias' => $nuevaMateria->id_materias,
                ]);
            }
        }
        return redirect()->route('docentes.buscar')->with('exito', 'Docente actualizado correctamente.');
    }

    public function destroyMateria($id)
    {
        if (auth()->user()->rol !== 'prosecretario') {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        DB::table('docentes_materias')->where('id_materias', $id)->delete();
        Materia::where('id_materias', $id)->delete();

        return response()->json(['success' => true]);
    }

    public function desactivar($id)
    {
        if (auth()->user()->rol !== 'prosecretario') {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $docente = Docente::findOrFail($id);
        $docente->activo = false;
        $docente->save();

        return response()->json([
            'success' => true,
            'message' => 'Docente desactivado correctamente'
        ], 200);
    }

    public function activar($id)
    {
        if (auth()->user()->rol !== 'prosecretario') {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $docente = Docente::findOrFail($id);
        $docente->activo = true;
        $docente->save();

        return response()->json([
            'success' => true,
            'message' => 'Docente activado correctamente'
        ], 200);
    }

    public function destroy($id)
    {
        if (auth()->user()->rol !== 'prosecretario') {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $docente = Docente::findOrFail($id);
        
        DB::table('docentes_materias')->where('id_docente', $id)->delete();
        $docente->delete();

        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Docente eliminado correctamente']);
        }

        return redirect()->back()->with('exito', 'Docente eliminado correctamente.');
    }

    private function validarConflictosHorarios($docenteId, $dia, $inicio, $fin, $curso, $division, $materiaIdIgnorar = null)
    {
        // 1. Conflicto del mismo Docente
        if ($docenteId) {
            $conflictoDocente = DB::table('materias')
                ->join('docentes_materias', 'materias.id_materias', '=', 'docentes_materias.id_materias')
                ->join('docentes', 'docentes.id_docente', '=', 'docentes_materias.id_docente')
                ->where('docentes_materias.id_docente', $docenteId)
                ->where('materias.dia', $dia)
                ->when($materiaIdIgnorar, fn($q) => $q->where('materias.id_materias', '!=', $materiaIdIgnorar))
                ->where('materias.horario_inicio', '<', $fin)
                ->where('materias.horario_finalizacion', '>', $inicio)
                ->select('docentes.nombre', 'docentes.apellido', 'materias.nombre as materia')
                ->first();

            if ($conflictoDocente) {
                return "El docente {$conflictoDocente->nombre} {$conflictoDocente->apellido} ya tiene asignada la materia '{$conflictoDocente->materia}' el día {$dia} de {$inicio} a {$fin}.";
            }
        }

        // 2. Conflicto del Curso/División (Trae el nombre del otro profesor)
        $conflictoCurso = DB::table('materias')
            ->join('docentes_materias', 'materias.id_materias', '=', 'docentes_materias.id_materias')
            ->join('docentes', 'docentes.id_docente', '=', 'docentes_materias.id_docente')
            ->where('materias.curso', $curso)
            ->where('materias.division', $division)
            ->where('materias.dia', $dia)
            ->when($docenteId, fn($q) => $q->where('docentes.id_docente', '!=', $docenteId))
            ->when($materiaIdIgnorar, fn($q) => $q->where('materias.id_materias', '!=', $materiaIdIgnorar))
            ->where('materias.horario_inicio', '<', $fin)
            ->where('materias.horario_finalizacion', '>', $inicio)
            ->select('docentes.nombre', 'docentes.apellido', 'materias.nombre as materia')
            ->first();

        if ($conflictoCurso) {
            return "El curso {$curso}° {$division}ª ya tiene asignado a {$conflictoCurso->nombre} {$conflictoCurso->apellido} ('{$conflictoCurso->materia}') el día {$dia} entre {$inicio} y {$fin}.";
        }

        return null;
    }
}