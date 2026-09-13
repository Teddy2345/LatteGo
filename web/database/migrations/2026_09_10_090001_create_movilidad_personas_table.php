<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Ayudantes/tripulacion informal asignada a un camion (nombre libre, no
     * necesariamente un usuario del sistema).
     */
    public function up(): void
    {
        Schema::create('movilidad_personas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('movilidad_id')->constrained('movilidades')->cascadeOnDelete();
            $table->string('nombre', 120);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movilidad_personas');
    }
};
