<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Camiones, furgones, motocargas y la "entrega directa en planta" (tipo
     * planta, sin vehiculo) que ejecutan el recorrido de una ruta.
     */
    public function up(): void
    {
        Schema::create('movilidades', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 60);
            $table->string('tipo', 20);
            $table->foreignId('ruta_id')->nullable()->constrained('rutas')->nullOnDelete();
            $table->boolean('activa')->default(true);
            $table->timestamps();

            $table->index('ruta_id');
            $table->index('tipo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movilidades');
    }
};
