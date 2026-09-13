<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('acopios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proveedor_id')->constrained('proveedores')->restrictOnDelete();
            $table->foreignId('acopiador_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('ruta_id')->nullable()->constrained('rutas')->nullOnDelete();
            $table->date('fecha');
            $table->decimal('cantidad_litros', 8, 2);
            $table->string('estado', 30)->default('pendiente_sincronizar');
            $table->decimal('perdida_litros', 8, 2)->nullable();
            $table->string('motivo_perdida', 255)->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index(['proveedor_id', 'fecha']);
            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acopios');
    }
};
