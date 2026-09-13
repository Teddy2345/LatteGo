<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proveedor_id')->constrained('proveedores')->restrictOnDelete();
            $table->date('semana_inicio');
            $table->date('semana_fin');
            $table->decimal('total_litros', 10, 2);
            $table->decimal('precio_litro', 8, 2);
            $table->decimal('total_pagar', 12, 2);
            $table->date('fecha_pago')->nullable();
            $table->string('estado', 20)->default('pendiente');
            $table->timestamps();

            $table->unique(['proveedor_id', 'semana_inicio']);
            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};
