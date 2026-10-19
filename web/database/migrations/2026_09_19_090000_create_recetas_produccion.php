<?php

declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('recetas', function (Blueprint $t) {
            $t->id();
            $t->string('nombre', 120)->unique();
            $t->foreignId('producto_id')->constrained('productos')->restrictOnDelete();
            $t->decimal('litros_base', 10, 2);
            $t->text('descripcion')->nullable();
            $t->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });
        Schema::create('receta_detalles', function (Blueprint $t) {
            $t->id();
            $t->foreignId('receta_id')->constrained('recetas')->restrictOnDelete();
            $t->foreignId('insumo_id')->constrained('productos')->restrictOnDelete();
            $t->decimal('cantidad', 10, 2);
            $t->string('unidad', 20);
            $t->unique(['receta_id', 'insumo_id']);
        });
        Schema::table('produccion', function (Blueprint $t) {
            $t->foreignId('receta_id')->nullable()->constrained('recetas')->restrictOnDelete();
        });
        Schema::create('produccion_detalles', function (Blueprint $t) {
            $t->id();
            $t->foreignId('produccion_id')->constrained('produccion')->restrictOnDelete();
            $t->foreignId('insumo_id')->constrained('productos')->restrictOnDelete();
            $t->decimal('cantidad', 10, 2);
            $t->string('unidad', 20);
            $t->unique(['produccion_id', 'insumo_id']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('produccion_detalles');
        Schema::table('produccion', fn (Blueprint $t) => $t->dropConstrainedForeignId('receta_id'));
        Schema::dropIfExists('receta_detalles');
        Schema::dropIfExists('recetas');
    }
};
