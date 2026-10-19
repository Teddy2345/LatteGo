<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('unidades_medida', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 80)->unique();
            $table->string('simbolo', 20);
            $table->timestamps();
        });

        // Preserve the catalog's existing unit names without assuming conversions.
        $unidades = DB::table('productos')->whereNotNull('unidad')->distinct()->pluck('unidad');
        foreach ($unidades as $unidad) {
            DB::table('unidades_medida')->insert([
                'nombre' => $unidad, 'simbolo' => $unidad,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        foreach (['kg' => 'kg', 'g' => 'g', 'litro' => 'L', 'mililitro' => 'ml', 'unidad' => 'ud'] as $nombre => $simbolo) {
            if (!DB::table('unidades_medida')->where('nombre', $nombre)->exists()) {
                DB::table('unidades_medida')->insert([
                    'nombre' => $nombre, 'simbolo' => $simbolo,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }

        Schema::table('recetas', function (Blueprint $table) {
            // Nullable: an old recipe describes milk input, not product yield.
            // Its output quantity cannot safely be inferred from litros_base.
            $table->decimal('cantidad_base', 12, 4)->nullable();
            $table->foreignId('unidad_medida_id')->nullable()->constrained('unidades_medida')->restrictOnDelete();
        });

        Schema::create('produccion_productos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produccion_id')->constrained('produccion')->restrictOnDelete();
            $table->foreignId('producto_id')->constrained('productos')->restrictOnDelete();
            $table->foreignId('receta_id')->nullable()->constrained('recetas')->restrictOnDelete();
            $table->foreignId('unidad_medida_id')->constrained('unidades_medida')->restrictOnDelete();
            $table->decimal('cantidad_planificada', 12, 4)->nullable();
            $table->decimal('cantidad_producida', 12, 4);
            $table->text('descripcion')->nullable();
            // Links the result to its existing inventory entry; this is not a second stock ledger.
            $table->foreignId('movimiento_inventario_id')->nullable()->unique()
                ->constrained('movimientos_inventario')->restrictOnDelete();
            $table->timestamps();
            $table->index(['produccion_id', 'producto_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produccion_productos');
        Schema::table('recetas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('unidad_medida_id');
            $table->dropColumn('cantidad_base');
        });
        Schema::dropIfExists('unidades_medida');
    }
};
