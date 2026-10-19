<?php

declare(strict_types=1);

namespace App\Presentation\Web\Livewire\Ventas;

use App\Application\Comprobantes\UseCases\EmitirComprobanteUseCase;
use App\Application\Inventario\DTOs\RegistrarVentaData;
use App\Application\Inventario\UseCases\ListarProductosConStockUseCase;
use App\Application\Inventario\UseCases\RegistrarVentaUseCase;
use App\Domain\Inventario\Exceptions\InventarioException;
use App\Domain\Inventario\ValueObjects\TipoCliente;
use App\Domain\Inventario\ValueObjects\TipoProducto;
use App\Infrastructure\Inventario\Models\MovimientoInventarioModel;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.app')]
final class Formulario extends Component
{
    #[Validate('required|integer|exists:productos,id')]
    public ?int $productoId = null;

    #[Validate('required|date')]
    public string $fecha = '';

    #[Validate('required|numeric|min:0.01')]
    public float $cantidad = 0.0;

    #[Validate('required|numeric|min:0.01')]
    public float $precioUnitario = 0.0;

    #[Validate('nullable|string|max:160')]
    public ?string $cliente = null;

    /** A quien se le vende: decide que precio se propone. */
    #[Validate('required|string|in:externo,vecino,proveedor')]
    public string $tipoCliente = 'externo';

    /** El papel que se lleva el cliente. Sin comprobante tambien se puede vender. */
    #[Validate('required|string|in:ninguno,boleta,factura')]
    public string $tipoComprobante = 'boleta';

    #[Validate('nullable|string|max:20')]
    public ?string $clienteDocumento = null;

    public string $error = '';

    public function mount(): void
    {
        $this->authorize('vender', MovimientoInventarioModel::class);

        $this->fecha = now()->format('Y-m-d');
    }

    /**
     * Al elegir producto —o al cambiar a quien se le vende— se propone el
     * precio que le toca a ese comprador, que el usuario aun puede ajustar
     * antes de confirmar: el tipo de cliente queda guardado igual, asi que
     * en el historial se ve con que precio se cobro y si se movio.
     */
    public function updatedProductoId(ListarProductosConStockUseCase $listar): void
    {
        $this->proponerPrecio($listar);
    }

    public function updatedTipoCliente(ListarProductosConStockUseCase $listar): void
    {
        $this->proponerPrecio($listar);
    }

    private function proponerPrecio(ListarProductosConStockUseCase $listar): void
    {
        $tipo = TipoCliente::desde($this->tipoCliente);

        foreach ($listar->ejecutar(tipo: TipoProducto::ProductoTerminado) as $producto) {
            if ($producto->id === (int) $this->productoId) {
                $this->precioUnitario = match ($tipo) {
                    TipoCliente::Vecino => $producto->precioVecino ?: $producto->precioReferencia,
                    TipoCliente::Proveedor => $producto->precioProveedor ?: $producto->precioReferencia,
                    TipoCliente::Externo => $producto->precioReferencia,
                };

                return;
            }
        }
    }

    public function guardar(RegistrarVentaUseCase $registrar, EmitirComprobanteUseCase $emitir): void
    {
        $this->authorize('vender', MovimientoInventarioModel::class);

        $this->validate();
        $this->error = '';

        try {
            $venta = $registrar->ejecutar(new RegistrarVentaData(
                productoId: (int) $this->productoId,
                fecha: $this->fecha,
                cantidad: $this->cantidad,
                precioUnitario: $this->precioUnitario,
                cliente: $this->cliente,
                usuarioId: auth()->id(),
                tipoCliente: TipoCliente::desde($this->tipoCliente),
            ));
        } catch (InventarioException $e) {
            $this->error = $e->getMessage();

            return;
        }

        if ($this->tipoComprobante !== 'ninguno') {
            try {
                $comprobante = $emitir->ejecutar([
                    'tipo' => $this->tipoComprobante,
                    'fecha' => $this->fecha,
                    'clienteNombre' => $this->cliente ?: 'Cliente de mostrador',
                    'clienteDocumento' => $this->clienteDocumento,
                    'total' => round($this->cantidad * $this->precioUnitario, 2),
                    'movimientoInventarioId' => $venta->id,
                ], (int) auth()->id());

                session()->flash('comprobanteEmitido', $comprobante['id']);
            } catch (ValidationException $e) {
                // La venta ya quedo registrada: se avisa y se puede emitir
                // el comprobante desde la lista, sin repetir la salida de stock.
                $this->error = $e->validator->errors()->first().' La venta quedó registrada; emite su comprobante desde la lista.';

                return;
            }
        }

        $this->redirect(route('ventas.index'), navigate: true);
    }

    public function render(ListarProductosConStockUseCase $listar): View
    {
        return view('livewire.ventas.formulario', [
            'productos' => $listar->ejecutar(tipo: TipoProducto::ProductoTerminado),
            'tiposCliente' => TipoCliente::cases(),
        ]);
    }
}
