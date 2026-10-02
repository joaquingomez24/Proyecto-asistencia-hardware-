<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('huellas', function (Blueprint $table) {
            $table->id('id_huella');
            $table->unsignedBigInteger('id_docente');
            $table->foreign('id_docente')
                  ->references('id_docente')
                  ->on('docentes')
                  ->onDelete('cascade');
            $table->integer('sensor_id')->unique();
            $table->string('dedo', 50)->nullable();
            $table->text('template_data')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('huellas');
    }
};