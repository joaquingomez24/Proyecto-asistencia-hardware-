<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Única fuente de verdad de "qué huella es de quién".
     * sensor_id es la posición donde el DY50 guardó la plantilla. La plantilla en sí
     * NUNCA sale del sensor: acá solo se guarda el vínculo.
     */
    public function up(): void
    {
        Schema::create('huellas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('docente_id')->constrained('docentes')->cascadeOnDelete();
            $table->unsignedSmallInteger('sensor_id')->unique();
            $table->string('dedo', 40)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('huellas');
    }
};
