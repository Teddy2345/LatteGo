<?php

declare(strict_types=1);

use App\Infrastructure\Inventario\Models\ProductoModel;
use App\Infrastructure\Produccion\Models\ProduccionModel;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

test('la estructura relaciona recetas y lotes con cantidades decimales de distintos productos', function () {
    $lote = ProduccionModel::factory()->create();
    foreach (['Queso de 2 kg' => ['kg', 2.0], 'Yogur natural' => ['litro', 10.5], 'Mantequilla' => ['kg', 0.25]] as $nombre => [$unidad, $cantidad]) {
        $producto = ProductoModel::factory()->create(['nombre' => $nombre, 'unidad' => $unidad]);
        $unidadId = DB::table('unidades_medida')->where('nombre', $unidad)->value('id');
        $receta = DB::table('recetas')->insertGetId([
            'nombre' => 'Receta '.$nombre, 'producto_id' => $producto->id, 'litros_base' => 10,
            'cantidad_base' => $cantidad, 'unidad_medida_id' => $unidadId,
        ]);
        DB::table('produccion_productos')->insert([
            'produccion_id' => $lote->id, 'producto_id' => $producto->id, 'receta_id' => $receta,
            'unidad_medida_id' => $unidadId, 'cantidad_planificada' => $cantidad * 2,
            'cantidad_producida' => $cantidad * 2 - 0.125,
        ]);
        $this->assertDatabaseHas('produccion_productos', [
            'producto_id' => $producto->id, 'cantidad_producida' => $cantidad * 2 - 0.125,
        ]);
    }
    expect(DB::table('produccion_productos')->count())->toBe(3);
    expect(DB::table('movimientos_inventario')->count())->toBe(0);
});

test('la migracion conserva recetas antiguas sin inventar su rendimiento y puede revertirse', function () {
    $migration = require database_path('migrations/2026_09_19_100000_ampliar_estructura_produccion_dinamica.php');
    $migration->down();
    $producto = ProductoModel::factory()->create(['unidad' => 'bolsa']);
    $recetaId = DB::table('recetas')->insertGetId(['nombre' => 'Receta anterior', 'producto_id' => $producto->id, 'litros_base' => 100]);
    $lote = ProduccionModel::factory()->create();
    $antes = DB::table('produccion')->where('id', $lote->id)->first();
    $migration->up();
    $this->assertDatabaseHas('recetas', ['id' => $recetaId, 'litros_base' => 100, 'cantidad_base' => null, 'unidad_medida_id' => null]);
    $this->assertDatabaseHas('unidades_medida', ['nombre' => 'bolsa']);
    expect(DB::table('produccion')->where('id', $lote->id)->first())->toEqual($antes);
});

test('el detalle rechaza una produccion inexistente', function () {
    $producto = ProductoModel::factory()->create();
    expect(fn () => DB::table('produccion_productos')->insert([
        'produccion_id' => 999999, 'producto_id' => $producto->id,
        'unidad_medida_id' => DB::table('unidades_medida')->value('id'), 'cantidad_producida' => 1,
    ]))->toThrow(QueryException::class);
});
