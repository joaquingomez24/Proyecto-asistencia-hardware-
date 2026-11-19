<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('docentes', function (Blueprint $table) {
            $table->id();
            $table->string('dni', 10)->unique();
            $table->string('nombre', 50);
            $table->string('apellido', 50);
            $table->string('telefono', 20)->nullable();
            $table->string('email', 100)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // Una cuenta de usuario con rol "docente" apunta a su ficha de docente.
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('docente_id')->nullable()->unique()
                ->after('rol')->constrained('docentes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('docente_id');
        });
        Schema::dropIfExists('docentes');
    }
};
