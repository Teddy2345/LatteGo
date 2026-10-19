<?php

declare(strict_types=1);

namespace App\Presentation\Api\V1\Controllers\Mobile;

use App\Application\Inventario\DTOs\ProductoStockData;
use App\Application\Inventario\UseCases\ListarProductosConStockUseCase;
use App\Application\Pedidos\DTOs\ItemCarritoData;
use App\Application\Pedidos\UseCases\CrearPedidoDeProveedorUseCase;
use App\Domain\Inventario\ValueObjects\TipoCliente;
use App\Domain\Inventario\ValueObjects\TipoProducto;
use App\Domain\Pedidos\ValueObjects\TipoEntrega;
use App\Http\Controllers\Controller;
use App\Infrastructure\Pedidos\Models\PedidoModel;
use App\Infrastructure\Proveedor\Models\ProveedorModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * La tienda del proveedor dentro de la app.
 *
 * Muestra los productos con SU precio y recibe su pedido. El precio nunca
 * llega del telefono: se toma del catalogo al construir el pedido, asi que
 * manipular la app no abarata nada.
 */
final class TiendaProveedorController extends Controller
{
    /** Los productos terminados con existencia, al precio del proveedor. */
    public function productos(Request $request, ListarProductosConStockUseCase $listar): JsonResponse
    {
        $this->exigirProveedor($request);

        $productos = array_values(array_filter(
            $listar->ejecutar(tipo: TipoProducto::ProductoTerminado),
            static fn (ProductoStockData $p): bool => $p->activo && $p->stock > 0,
        ));

        return response()->json([
            'data' => array_map(static function (ProductoStockData $p): array {
                $suyo = $p->precioProveedor !== null && $p->precioProveedor > 0
                    ? $p->precioProveedor
                    : $p->precioReferencia;

                return [
                    'id' => $p->id,
                    'nombre' => $p->nombre,
                    'unidad' => $p->unidad,
                    'descripcion' => $p->descripcion,
                    'stock' => round($p->stock, 2),
                    // El proveedor ya esta identificado: el precio que ve
                    // es el suyo y no hay cupones ni comparaciones que hacer.
                    'precio' => round($suyo, 2),
                    'por_unidad_entera' => $p->seVendePorUnidadEntera(),
                ];
            }, $productos),
        ]);
    }

    public function crearPedido(Request $request, CrearPedidoDeProveedorUseCase $crear): JsonResponse
    {
        $this->exigirProveedor($request);

        $datos = $request->validate([
            'tipo_entrega' => ['required', 'string', 'in:delivery,recojo'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.producto_id' => ['required', 'integer', 'distinct', 'exists:productos,id'],
            'items.*.cantidad' => ['required', 'numeric', 'min:0.01', 'max:9999.99'],
        ]);

        $pedido = $crear->ejecutar(
            usuario: $request->user(),
            items: array_map(
                static fn (array $i): ItemCarritoData => new ItemCarritoData(
                    productoId: (int) $i['producto_id'],
                    cantidad: (float) $i['cantidad'],
                ),
                $datos['items'],
            ),
            tipoEntrega: TipoEntrega::from($datos['tipo_entrega']),
            direccion: (string) ($datos['direccion'] ?? ''),
            observaciones: $datos['observaciones'] ?? null,
        );

        return response()->json([
            'data' => [
                'id' => $pedido->id,
                'codigo' => $pedido->codigo,
                'total' => $pedido->total,
                'estado' => $pedido->estado,
                'tipo_entrega' => $pedido->tipoEntrega,
                'mensaje' => 'Tu pedido quedó registrado. El código está en tu campana.',
            ],
        ], 201);
    }

    /** Los pedidos que hizo, para seguir en que van. */
    public function misPedidos(Request $request): JsonResponse
    {
        $this->exigirProveedor($request);

        $pedidos = PedidoModel::query()
            ->with('items')
            ->where('user_id', $request->user()->id)
            ->latest('id')
            ->limit(30)
            ->get();

        return response()->json([
            'data' => $pedidos->map(static fn (PedidoModel $p): array => [
                'id' => $p->id,
                'codigo' => $p->codigo,
                'fecha' => $p->fecha->format('Y-m-d'),
                'estado' => $p->estado,
                'tipo_entrega' => $p->tipo_entrega,
                'total' => round((float) $p->items->sum(fn ($i) => (float) $i->cantidad * (float) $i->precio_unitario), 2),
                'items' => $p->items->map(static fn ($i): array => [
                    'producto' => $i->nombre_producto,
                    'cantidad' => (float) $i->cantidad,
                    'precio_unitario' => (float) $i->precio_unitario,
                ])->all(),
            ])->all(),
        ]);
    }

    /**
     * La cuenta tiene que estar ligada a una ficha de proveedor: el rol por
     * si solo no basta para cobrar el precio de proveedor.
     */
    private function exigirProveedor(Request $request): ProveedorModel
    {
        $proveedor = ProveedorModel::query()->where('user_id', $request->user()->id)->first();

        abort_if($proveedor === null, 403, 'Esta cuenta no está ligada a un proveedor de la planta.');

        return $proveedor;
    }

    /** Para dejar claro en un solo sitio con que precio compra. */
    public static function tipoCliente(): TipoCliente
    {
        return TipoCliente::Proveedor;
    }
}
