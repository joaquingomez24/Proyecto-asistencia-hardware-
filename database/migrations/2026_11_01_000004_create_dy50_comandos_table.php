<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cola de órdenes para el lector. La página crea un comando ("verificar", "enrolar", "borrar"),
     * el agente de la Raspberry lo toma, opera el DY50 y devuelve el resultado.
     * Reemplaza a la vieja tabla dy50_events.
     */
    public function up(): void
    {
        Schema::create('dy50_comandos', function (Blueprint $table) {
            $table->id();
            $table->string('dispositivo', 50)->index();
            $table->string('tipo', 20);                       // verificar | enrolar | borrar
            $table->string('estado', 20)->default('pendiente')->index();
            // pendiente -> en_curso -> completado | error | cancelado | expirado
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('docente_id')->nullable()->constrained('docentes')->nullOnDelete();
            $table->foreignId('horario_id')->nullable()->constrained('horarios')->nullOnDelete(); // solo "verificar"
            $table->unsignedSmallInteger('sensor_id')->nullable();  // enrolar/borrar: posición; verificar: huella leída
            $table->unsignedSmallInteger('confianza')->nullable();  // puntaje de coincidencia
            $table->string('dedo', 40)->nullable();                 // solo "enrolar"
            $table->unsignedSmallInteger('timeout_segundos')->default(20);
            $table->string('progreso', 160)->nullable();            // texto que ve la persona mientras espera
            $table->string('mensaje', 255)->nullable();             // resultado final
            $table->timestamp('expira_at');
            $table->timestamp('reclamado_at')->nullable();
            $table->timestamp('finalizado_at')->nullable();
            $table->timestamps();

            $table->index(['dispositivo', 'estado', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dy50_comandos');
    }
};
