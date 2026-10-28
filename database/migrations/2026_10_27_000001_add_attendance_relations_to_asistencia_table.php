<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('asistencia', 'id_docente')) {
            Schema::table('asistencia', function (Blueprint $table) {
                $table->unsignedBigInteger('id_docente')->nullable()->after('id_asistencia');
                $table->foreign('id_docente', 'asistencia_id_docente_fk')
                    ->references('id_docente')->on('docentes')->nullOnDelete();
            });
        }

        if (!Schema::hasColumn('asistencia', 'id_materia')) {
            Schema::table('asistencia', function (Blueprint $table) {
                $table->unsignedBigInteger('id_materia')->nullable()->after('id_docente');
                $table->foreign('id_materia', 'asistencia_id_materia_fk')
                    ->references('id_materias')->on('materias')->nullOnDelete();
            });
        }

        if (!Schema::hasColumn('asistencia', 'dy50_event_id')) {
            Schema::table('asistencia', function (Blueprint $table) {
                $table->unsignedBigInteger('dy50_event_id')->nullable()->after('id_materia');
                $table->foreign('dy50_event_id', 'asistencia_dy50_event_id_fk')
                    ->references('id')->on('dy50_events')->nullOnDelete();
                $table->unique('dy50_event_id', 'asistencia_dy50_event_id_unique');
            });
        }

        // Las ausencias necesitan poder guardarse sin hora de ingreso.
        Schema::table('asistencia', function (Blueprint $table) {
            $table->time('hora_ingreso')->nullable()->change();
        });

        Schema::table('asistencia', function (Blueprint $table) {
            $table->index(['fecha', 'id_docente'], 'asistencia_fecha_docente_idx');
            $table->unique(['fecha', 'id_docente', 'id_materia'], 'asistencia_fecha_docente_materia_unique');
        });
    }

    public function down(): void
    {
        Schema::table('asistencia', function (Blueprint $table) {
            $table->dropIndex('asistencia_fecha_docente_idx');
            $table->dropUnique('asistencia_fecha_docente_materia_unique');
        });

        Schema::table('asistencia', function (Blueprint $table) {
            if (Schema::hasColumn('asistencia', 'dy50_event_id')) {
                $table->dropUnique('asistencia_dy50_event_id_unique');
            }
        });

        foreach ([
            ['asistencia_dy50_event_id_fk', 'dy50_event_id'],
            ['asistencia_id_materia_fk', 'id_materia'],
            ['asistencia_id_docente_fk', 'id_docente'],
        ] as [$foreignKey, $column]) {
            if (Schema::hasColumn('asistencia', $column)) {
                Schema::table('asistencia', function (Blueprint $table) use ($foreignKey, $column) {
                    $table->dropForeign($foreignKey);
                    $table->dropColumn($column);
                });
            }
        }

        // No se revierte la nulabilidad de hora_ingreso porque podría haber ausencias guardadas.
    }
};
