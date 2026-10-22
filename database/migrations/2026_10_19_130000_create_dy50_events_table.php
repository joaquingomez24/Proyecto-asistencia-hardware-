<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dy50_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('fingerprint_id');   // ID de la plantilla dentro del DY50
            $table->unsignedSmallInteger('confidence');       // puntaje de coincidencia
            $table->string('device', 50)->nullable();         // nombre del lector (raspberry-1, ...)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dy50_events');
    }
};
