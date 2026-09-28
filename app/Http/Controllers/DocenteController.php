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
                $errorBD = $this->validarConflictoHorario(null, null, $curso, $division, $dia, $inicio, $fin);
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

        /**
     * Actualizar los datos del docente y sus materias/horarios.
     */
    public function update(Request $request, $id)
    {
        $docente = DB::table('docentes')->where('id_docente', $id)->first();

        if (!$docente) {
            return back()->withErrors(['No se encontró el docente.'])->withInput();
        }

        // 1. Validar conflictos de horario antes de actualizar
        if ($request->has('materia') && is_array($request->materia)) {
            foreach ($request->materia as $i => $nombreMateria) {
                $materiaIdIgnorar = $request->id_materia[$i] ?? null;
                $curso = $request->curso[$i] ?? null;
                $division = $request->division[$i] ?? null;
                $dia = $request->dia[$i] ?? null;
                $inicio = $request->entrada[$i] ?? null;
                $fin = $request->salida[$i] ?? null;

                if ($curso && $division && $dia && $inicio && $fin) {
                    $error = $this->validarConflictoHorario($id, $materiaIdIgnorar, $curso, $division, $dia, $inicio, $fin);
                    if ($error) {
                        return back()->withErrors([$error])->withInput();
                    }
                }
            }
        }

        // 2. Actualizar datos básicos del docente
        DB::table('docentes')->where('id_docente', $id)->update([
            'nombre'    => $request->nombre,
            'apellido'  => $request->apellido,
            'DNI'       => $request->DNI,
            'telefono'  => $request->telefono,
            'email'     => $request->email,
            'id_huella' => $request->id_huella,
        ]);

        // 3. Procesar las materias/horarios
        if ($request->has('materia') && is_array($request->materia)) {
            foreach ($request->materia as $i => $nombreMateria) {
                $idMateria = $request->id_materia[$i] ?? null;

                $datosMateria = [
                    'nombre'               => $nombreMateria,
                    'turno'                => $request->turno[$i] ?? null,
                    'curso'                => $request->curso[$i] ?? null,
                    'division'             => $request->division[$i] ?? null,
                    'horario_inicio'       => $request->entrada[$i] ?? null,
                    'horario_finalizacion' => $request->salida[$i] ?? null,
                    'dia'                  => $request->dia[$i] ?? null,
                ];

                if ($idMateria) {
                    // Actualizar materia existente
                    DB::table('materias')->where('id_materias', $idMateria)->update($datosMateria);
                } else {
                    // Crear nueva materia y vincularla al docente
                    $nuevoIdMateria = DB::table('materias')->insertGetId($datosMateria);
                    DB::table('docentes_materias')->insert([
                        'id_docente'  => $id,
                        'id_materias' => $nuevoIdMateria,
                    ]);
                }
            }
        }

        return redirect()->route('home')->with('exito', 'Docente actualizado correctamente.');
    }

    /**
     * Función privada para validar solapamiento de horarios ignorando al docente actual y la materia que se edita.
     */
    private function validarConflictoHorario($docenteId, $materiaIdIgnorar, $curso, $division, $dia, $inicio, $fin)
    {
        // 1. Conflicto de Horario del mismo Docente (ignora el horario que está editando)
        $conflictoDocente = DB::table('materias')
            ->join('docentes_materias', 'materias.id_materias', '=', 'docentes_materias.id_materias')
            ->where('docentes_materias.id_docente', $docenteId)
            ->where('materias.dia', $dia)
            ->when($materiaIdIgnorar, function ($q) use ($materiaIdIgnorar) {
                return $q->where('materias.id_materias', '!=', $materiaIdIgnorar);
            })
            ->where('materias.horario_inicio', '<', $fin)
            ->where('materias.horario_finalizacion', '>', $inicio)
            ->select('materias.nombre as materia')
            ->first();

        if ($conflictoDocente) {
            return "El docente ya tiene asignada la materia '{$conflictoDocente->materia}' el día {$dia} entre {$inicio} y {$fin}.";
        }

        // 2. Conflicto de Aula/Curso (ignora al docente actual y la materia que está editando)
        $conflictoCurso = DB::table('materias')
            ->join('docentes_materias', 'materias.id_materias', '=', 'docentes_materias.id_materias')
            ->join('docentes', 'docentes.id_docente', '=', 'docentes_materias.id_docente')
            ->where('materias.curso', $curso)
            ->where('materias.division', $division)
            ->where('materias.dia', $dia)
            ->where('docentes.id_docente', '!=', $docenteId)
            ->when($materiaIdIgnorar, function ($q) use ($materiaIdIgnorar) {
                return $q->where('materias.id_materias', '!=', $materiaIdIgnorar);
            })
            ->where('materias.horario_inicio', '<', $fin)
            ->where('materias.horario_finalizacion', '>', $inicio)
            ->select('docentes.nombre', 'docentes.apellido', 'materias.nombre as materia')
            ->first();

        if ($conflictoCurso) {
            return "El curso {$curso} {$division} ya tiene un profesor asignado ({$conflictoCurso->nombre} {$conflictoCurso->apellido}) el {$dia} entre {$inicio} y {$fin}.";
        }

        return null;
    }
}