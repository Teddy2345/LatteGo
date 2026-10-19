<?php

declare(strict_types=1);

namespace App\Presentation\Web\Livewire\Tienda;

use App\Application\Inventario\UseCases\ListarProductosConStockUseCase;
use App\Application\Pedidos\DTOs\CrearPedidoData;
use App\Application\Pedidos\DTOs\ItemCarritoData;
use App\Application\Pedidos\UseCases\CrearPedidoUseCase;
use App\Domain\Shared\Exceptions\DomainRuleException;
use App\Presentation\Web\Livewire\Tienda\Concerns\InteractuaConCarrito;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.tienda')]
final class Checkout extends Component
{
    use InteractuaConCarrito;

    #[Validate('required|string|max:120')]
    public string $nombre = '';

    #[Validate('required|string|max:20')]
    public string $telefono = '';

    #[Validate('required|string|max:255')]
    public string $direccion = '';

    #[Validate('nullable|string|max:500')]
    public ?string $observaciones = null;

    public string $error = '';

    /** Pedidos que un mismo visitante puede enviar seguidos antes de esperar. */
    private const PEDIDOS_POR_VENTANA = 5;

    private const VENTANA_SEGUNDOS = 600;

    public function confirmar(CrearPedidoUseCase $crear, ListarProductosConStockUseCase $listar): void
    {
        $this->validate();
        $this->error = '';

        // La tienda es publica y no pide cuenta: sin un tope, cualquiera
        // podria llenar de pedidos falsos la bandeja de ventas.
        $limite = 'tienda-pedidos:'.request()->ip();

        if (RateLimiter::tooManyAttempts($limite, self::PEDIDOS_POR_VENTANA)) {
            $this->error = 'Ya enviaste varios pedidos seguidos. Espera unos minutos antes de enviar otro.';

            return;
        }

        $carrito = $this->resolverCarrito($listar);

        if ($carrito['lineas'] === []) {
            $this->error = 'Tu carrito está vacío.';

            return;
        }

        try {
            $pedido = $crear->ejecutar(new CrearPedidoData(
                clienteNombre: $this->nombre,
                clienteTelefono: $this->telefono,
                clienteDireccion: $this->direccion,
                fecha: now()->format('Y-m-d'),
                observaciones: $this->observaciones,
                items: array_map(
                    static fn (array $linea): ItemCarritoData => new ItemCarritoData(
                        productoId: $linea['producto']->id,
                        cantidad: $linea['cantidad'],
                    ),
                    $carrito['lineas'],
                ),
            ));
        } catch (DomainRuleException $e) {
            $this->error = $e->getMessage();

            return;
        }

        RateLimiter::hit($limite, self::VENTANA_SEGUNDOS);
        session()->forget('carrito');
        // Deja constancia de que este visitante es quien hizo el pedido: la
        // pantalla de gracias muestra nombre y telefono del cliente y es
        // publica, asi que solo debe abrirla quien acaba de comprarlo.
        session()->push('tienda.pedidos', $pedido->id);

        $this->redirect(route('tienda.confirmacion', $pedido->id), navigate: true);
    }

    public function render(ListarProductosConStockUseCase $listar): View
    {
        return view('livewire.tienda.checkout', $this->resolverCarrito($listar));
    }
}
