<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Inventario\ValueObjects\TipoMovimiento;
use App\Infrastructure\Inventario\Models\MovimientoInventarioModel;
use App\Infrastructure\Inventario\Models\ProductoModel;
use App\Models\User;
use Illuminate\Database\Seeder;

class InventarioSeeder extends Seeder
{
    /**
     * Catalogo real de la planta y un historial de transformaciones y ventas
     * que deja stock positivo en todos los productos.
     */
    private const CATALOGO = [
        ['nombre' => 'Queso fresco de 500 g', 'unidad' => 'pieza', 'precio_referencia' => 22.00],
        ['nombre' => 'Queso madurado de 1 kg', 'unidad' => 'pieza', 'precio_referencia' => 55.00],
        ['nombre' => 'Yogurt de frutilla', 'unidad' => 'litro', 'precio_referencia' => 14.00],
        ['nombre' => 'Yogurt natural', 'unidad' => 'litro', 'precio_referencia' => 12.50],
        ['nombre' => 'Mantequilla artesanal', 'unidad' => 'kilo', 'precio_referencia' => 48.00],
        ['nombre' => 'Leche pasteurizada en bolsa', 'unidad' => 'litro', 'precio_referencia' => 6.50],
    ];

    /**
     * Insumos reales de una planta quesera, con su categoria y el minimo
     * que activa la alerta de "por agotarse".
     */
    private const INSUMOS = [
        ['nombre' => 'Sal fina', 'categoria' => 'Aditivos', 'unidad' => 'kilo', 'stock_minimo' => 20],
        ['nombre' => 'Cuajo líquido', 'categoria' => 'Aditivos', 'unidad' => 'litro', 'stock_minimo' => 5],
        ['nombre' => 'Cultivo láctico', 'categoria' => 'Aditivos', 'unidad' => 'sobre', 'stock_minimo' => 10],
        ['nombre' => 'Envases de 500 g', 'categoria' => 'Envases', 'unidad' => 'pieza', 'stock_minimo' => 200],
        ['nombre' => 'Bolsas de empaque', 'categoria' => 'Envases', 'unidad' => 'pieza', 'stock_minimo' => 300],
        ['nombre' => 'Etiquetas', 'categoria' => 'Envases', 'unidad' => 'pieza', 'stock_minimo' => 500],
    ];

    public function run(): void
    {
        $jefa = User::query()->where('email', 'produccion@ecolactea.test')->first();
        $admin = User::query()->where('email', 'admin@ecolactea.test')->first();
        $almacen = User::query()->where('email', 'almacen@ecolactea.test')->first();

        foreach (self::CATALOGO as $datos) {
            $producto = ProductoModel::query()->firstOrCreate(
                ['nombre' => $datos['nombre']],
                $datos + ['tipo' => 'producto_terminado', 'activo' => true],
            );

            // Ocho semanas de transformaciones.
            MovimientoInventarioModel::factory()
                ->count(8)
                ->create([
                    'producto_id' => $producto->id,
                    'usuario_id' => $jefa?->id,
                ]);

            // Se vende menos de lo producido para que quede existencia.
            MovimientoInventarioModel::factory()
                ->venta()
                ->count(5)
                ->create([
                    'producto_id' => $producto->id,
                    'usuario_id' => $admin?->id,
                ]);
        }

        $this->corregirSaldosNegativos();
        $this->sembrarInsumos($almacen);
    }

    /**
     * El cultivo lactico queda deliberadamente por debajo de su minimo,
     * para demostrar la alerta de "por agotarse" en el almacen.
     */
    private function sembrarInsumos(?User $almacen): void
    {
        foreach (self::INSUMOS as $datos) {
            $producto = ProductoModel::query()->firstOrCreate(
                ['nombre' => $datos['nombre']],
                [
                    'tipo' => 'insumo',
                    'categoria' => $datos['categoria'],
                    'unidad' => $datos['unidad'],
                    'precio_referencia' => 0,
                    'stock_minimo' => $datos['stock_minimo'],
                    'activo' => true,
                ],
            );

            $bajoStock = $datos['nombre'] === 'Cultivo láctico';

            MovimientoInventarioModel::query()->create([
                'producto_id' => $producto->id,
                'tipo' => 'compra',
                'fecha' => now()->subDays(20)->format('Y-m-d'),
                'cantidad' => $bajoStock ? $datos['stock_minimo'] * 0.6 : $datos['stock_minimo'] * 4,
                'lote' => 'L-'.now()->subDays(20)->format('Ym'),
                'observaciones' => 'Compra a Distribuidora Illimani',
                'usuario_id' => $almacen?->id,
            ]);

            MovimientoInventarioModel::query()->create([
                'producto_id' => $producto->id,
                'tipo' => 'uso_produccion',
                'fecha' => now()->subDays(5)->format('Y-m-d'),
                'cantidad' => $datos['stock_minimo'] * 0.5,
                'observaciones' => 'Uso en producción semanal',
                'usuario_id' => $almacen?->id,
            ]);
        }
    }

    /**
     * Las cantidades salen del faker, asi que un producto podria terminar con
     * saldo negativo. Ese estado no puede existir en el almacen real, de modo
     * que se compensa con una transformacion adicional.
     */
    private function corregirSaldosNegativos(): void
    {
        foreach (ProductoModel::query()->get() as $producto) {
            $entradas = (float) MovimientoInventarioModel::query()
                ->where('producto_id', $producto->id)
                ->where('tipo', TipoMovimiento::Produccion->value)
                ->sum('cantidad');

            $salidas = (float) MovimientoInventarioModel::query()
                ->where('producto_id', $producto->id)
                ->where('tipo', TipoMovimiento::Venta->value)
                ->sum('cantidad');

            if ($salidas >= $entradas) {
                $faltante = round($salidas - $entradas + 25, 2);

                MovimientoInventarioModel::factory()->create([
                    'producto_id' => $producto->id,
                    'cantidad' => $faltante,
                    'litros_procesados' => round($faltante * 8, 2),
                ]);
            }
        }
    }
}
