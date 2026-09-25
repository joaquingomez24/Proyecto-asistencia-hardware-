<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asistencia', function (Blueprint $table) {
            $table->id('id_asistencia');
            $table->date('fecha');
            $table->string('nombre_docente' , 100);
            $table->string('materia' , 70);
            $table->string('turno' , 20)->nullable();
            $table->time('hora_ingreso');
            $table->time('hora_egreso')->nullable();
            $table->string('estado' , 20);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asistencia');
    }
};