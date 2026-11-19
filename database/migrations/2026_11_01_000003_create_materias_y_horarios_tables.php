<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Catálogo: el nombre de cada materia existe una sola vez.
        Schema::create('materias', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 70)->unique();
            $table->timestamps();
        });

        // Un horario es "un docente dicta una materia a un curso, un día, de tal a tal hora".
        // (Antes esto se guardaba en "materias" + tabla puente "docentes_materias".)
        Schema::create('horarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('docente_id')->constrained('docentes')->cascadeOnDelete();
            $table->foreignId('materia_id')->constrained('materias')->restrictOnDelete();
            $table->string('curso', 10);
            $table->string('division', 10);
            $table->string('turno', 20);
            $table->unsignedTinyInteger('dia_semana'); // 1 = lunes ... 7 = domingo
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->softDeletes(); // un horario quitado no borra el historial de asistencias
            $table->timestamps();

            $table->index(['docente_id', 'dia_semana']);
            $table->index(['curso', 'division', 'dia_semana']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('horarios');
        Schema::dropIfExists('materias');
    }
};
