<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'id_docente')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedBigInteger('id_docente')->nullable()->unique('users_id_docente_unique')->after('rol');
                $table->foreign('id_docente', 'users_id_docente_fk')
                    ->references('id_docente')->on('docentes')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'id_docente')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign('users_id_docente_fk');
                $table->dropUnique('users_id_docente_unique');
                $table->dropColumn('id_docente');
            });
        }
    }
};
