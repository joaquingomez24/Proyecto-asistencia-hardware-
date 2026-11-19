<?php

namespace App\Http\Requests;

use App\Enums\DiaSemana;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Validación común de alta y edición de docentes (datos personales + horarios). */
class DocenteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->esSecretario() ?? false;
    }

    public function rules(): array
    {
        $docente = $this->route('docente'); // null al crear
        $dias = array_map(fn (DiaSemana $d) => $d->value, DiaSemana::laborables());

        return [
            'dni' => ['required', 'digits_between:7,10', Rule::unique('docentes', 'dni')->ignore($docente?->id)],
            'nombre' => ['required', 'string', 'max:50'],
            'apellido' => ['required', 'string', 'max:50'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:100'],

            'horarios' => ['nullable', 'array'],
            'horarios.*.id' => ['nullable', 'integer'],
            'horarios.*.materia' => ['required', 'string', 'max:70'],
            'horarios.*.turno' => ['required', Rule::in(['Mañana', 'Tarde', 'Vespertino'])],
            'horarios.*.curso' => ['required', 'string', 'max:10'],
            'horarios.*.division' => ['required', 'string', 'max:10'],
            'horarios.*.dia_semana' => ['required', Rule::in($dias)],
            'horarios.*.hora_inicio' => ['required', 'date_format:H:i'],
            'horarios.*.hora_fin' => ['required', 'date_format:H:i', 'after:horarios.*.hora_inicio'],
        ];
    }

    public function messages(): array
    {
        return [
            'dni.unique' => 'El DNI ingresado ya pertenece a otro docente registrado.',
            'dni.digits_between' => 'El DNI debe tener entre 7 y 10 números, sin puntos.',
            'horarios.*.hora_fin.after' => 'En cada horario, la salida debe ser posterior a la entrada.',
            'horarios.*.materia.required' => 'Completá la materia de cada horario.',
            'horarios.*.dia_semana.required' => 'Elegí el día de cada horario.',
        ];
    }
}
