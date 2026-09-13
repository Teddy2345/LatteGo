<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('analisis_calidad', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proveedor_id')->constrained('proveedores')->restrictOnDelete();
            $table->foreignId('acopio_id')->constrained('acopios')->restrictOnDelete();
            $table->date('fecha');
            $table->decimal('temperatura', 5, 2);
            $table->decimal('grasa', 5, 2);
            $table->decimal('solidos_no_grasos', 5, 2);
            $table->decimal('densidad', 5, 2);
            $table->decimal('proteina', 5, 2);
            $table->decimal('lactosa', 5, 2);
            $table->decimal('sales', 5, 2);
            $table->decimal('agua_agregada', 5, 2);
            $table->decimal('ph', 4, 2);
            $table->string('prueba_alcohol', 20);
            $table->string('resultado', 20);
            $table->string('motivo_rechazo', 20)->nullable();
            $table->string('sancion_aplicada', 20)->default('ninguna');
            $table->timestamps();

            $table->index(['proveedor_id', 'fecha']);
            $table->index('resultado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analisis_calidad');
    }
};
