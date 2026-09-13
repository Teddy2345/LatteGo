<?php

declare(strict_types=1);

namespace App\Presentation\Web\Livewire\Pagos;

use App\Application\Pagos\UseCases\ObtenerPagoUseCase;
use App\Application\Proveedor\UseCases\ObtenerProveedorUseCase;
use App\Infrastructure\Pagos\Models\PagoModel;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.print')]
final class Comprobante extends Component
{
    public int $pagoId;

    public function mount(int $pagoId): void
    {
        $this->authorize('view', PagoModel::class);

        $this->pagoId = $pagoId;
    }

    public function render(ObtenerPagoUseCase $obtenerPago, ObtenerProveedorUseCase $obtenerProveedor): View
    {
        $pago = $obtenerPago->ejecutar($this->pagoId);
        $proveedor = $obtenerProveedor->ejecutar($pago->proveedorId);

        return view('livewire.pagos.comprobante', [
            'pago' => $pago,
            'proveedor' => $proveedor,
        ]);
    }
}
