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
            $table->unsignedSmallInteger('fingerprint_id');
            $table->unsignedSmallInteger('confidence')->nullable();
            $table->timestamps();
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dy50_events');
    }
};
