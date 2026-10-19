<?php

declare(strict_types=1);

namespace App\Presentation\Web\Livewire\Tienda\Concerns;

use App\Application\Inventario\DTOs\ProductoStockData;
use App\Application\Inventario\UseCases\ListarProductosConStockUseCase;
use App\Domain\Inventario\ValueObjects\TipoProducto;

/**
 * El carrito de la tienda publica vive en la sesion del navegador, no en
 * base de datos: es de un visitante sin cuenta y solo importa mientras
 * arma su pedido. Estructura: [productoId => cantidad].
 */
trait InteractuaConCarrito
{
    private function carritoActual(): array
    {
        return session('carrito', []);
    }

    /**
     * Ajusta lo que escribio el visitante a lo que se puede entregar: en
     * productos por pieza (o sobre, bolsa…) no hay fracciones, asi que se
     * redondea a la unidad, con un minimo de una.
     */
    private function cantidadEntregable(ListarProductosConStockUseCase $listar, int $productoId, float $cantidad): float
    {
        if (! is_finite($cantidad) || $cantidad <= 0) {
            return 0.0;
        }

        foreach ($listar->ejecutar(soloActivos: true, tipo: TipoProducto::ProductoTerminado) as $producto) {
            if ($producto->id === $productoId) {
                return $producto->seVendePorUnidadEntera() ? max(1.0, round($cantidad)) : round($cantidad, 2);
            }
        }

        // Fuera del catalogo publico: resolverCarrito ya lo ignora.
        return 0.0;
    }

    private function guardarCarrito(array $carrito): void
    {
        session(['carrito' => array_filter($carrito, static fn (float $cantidad): bool => $cantidad > 0)]);
    }

    /**
     * Cruza el carrito guardado con el catalogo vivo: si un producto se dio
     * de baja o se quedo sin stock desde que se agrego, no revienta, solo
     * lo excluye de las lineas resueltas.
     *
     * @return array{lineas: array<int, array{producto: ProductoStockData, cantidad: float, subtotal: float}>, total: float}
     */
    private function resolverCarrito(ListarProductosConStockUseCase $listar): array
    {
        $carrito = $this->carritoActual();

        if ($carrito === []) {
            return ['lineas' => [], 'total' => 0.0];
        }

        $productos = $listar->ejecutar(soloActivos: true, tipo: TipoProducto::ProductoTerminado);
        $porId = [];
        foreach ($productos as $producto) {
            $porId[$producto->id] = $producto;
        }

        $lineas = [];
        $total = 0.0;

        foreach ($carrito as $productoId => $cantidad) {
            $producto = $porId[(int) $productoId] ?? null;

            if ($producto === null) {
                continue;
            }

            $subtotal = round($producto->precioReferencia * (float) $cantidad, 2);
            $lineas[] = ['producto' => $producto, 'cantidad' => (float) $cantidad, 'subtotal' => $subtotal];
            $total += $subtotal;
        }

        return ['lineas' => $lineas, 'total' => round($total, 2)];
    }
}
