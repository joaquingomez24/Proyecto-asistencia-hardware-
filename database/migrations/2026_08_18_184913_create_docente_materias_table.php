<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('docentes_materias', function (Blueprint $table) {
            $table->foreignId('id_docente')
                  ->constrained('docentes', 'id_docente')
                  ->onDelete('cascade');

            $table->foreignId('id_materias')
                  ->constrained('materias', 'id_materias')
                  ->onDelete('cascade');

            $table->primary(['id_docente', 'id_materias']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('docentes_materias');
    }
};