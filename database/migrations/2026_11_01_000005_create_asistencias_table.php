<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asistencias', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            // Se prohíbe borrar un docente o un horario que ya tiene asistencias: se desactiva.
            $table->foreignId('docente_id')->constrained('docentes')->restrictOnDelete();
            $table->foreignId('horario_id')->constrained('horarios')->restrictOnDelete();
            // Lectura del DY50 que originó el registro (null en ausencias y ediciones manuales).
            // Es único: una lectura no puede usarse dos veces.
            $table->foreignId('comando_id')->nullable()->unique()->constrained('dy50_comandos')->nullOnDelete();
            $table->time('hora_ingreso')->nullable();
            $table->time('hora_egreso')->nullable();
            $table->string('estado', 20); // ver App\Enums\EstadoAsistencia
            $table->timestamps();

            // Una sola asistencia por docente, horario y día.
            $table->unique(['fecha', 'docente_id', 'horario_id']);
            $table->index('fecha');
        });

        // Bitácora de ediciones manuales (secretario / jefe de preceptores).
        Schema::create('asistencia_ediciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asistencia_id')->constrained('asistencias')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('antes');
            $table->json('despues');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asistencia_ediciones');
        Schema::dropIfExists('asistencias');
    }
};
