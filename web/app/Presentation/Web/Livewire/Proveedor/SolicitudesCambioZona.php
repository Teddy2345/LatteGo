<?php

declare(strict_types=1);

namespace App\Presentation\Web\Livewire\Proveedor;

use App\Application\Proveedor\UseCases\AprobarCambioZonaUseCase;
use App\Application\Proveedor\UseCases\ListarSolicitudesPendientesUseCase;
use App\Application\Proveedor\UseCases\RechazarCambioZonaUseCase;
use App\Domain\Proveedor\Exceptions\SolicitudCambioZonaException;
use App\Infrastructure\Proveedor\Models\SolicitudCambioZonaModel;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
final class SolicitudesCambioZona extends Component
{
    public ?int $solicitudParaRechazar = null;
    public string $observacionRechazo = '';
    public string $error = '';

    public function mount(): void
    {
        $this->authorize('viewAny', SolicitudCambioZonaModel::class);
    }

    public function aprobar(int $solicitudId, AprobarCambioZonaUseCase $aprobar): void
    {
        $this->authorize('revisar', SolicitudCambioZonaModel::class);

        $this->error = '';

        try {
            $aprobar->ejecutar($solicitudId, auth()->id(), null);
        } catch (SolicitudCambioZonaException $e) {
            $this->error = $e->getMessage();
        }
    }

    public function abrirRechazo(int $solicitudId): void
    {
        $this->solicitudParaRechazar = $solicitudId;
        $this->observacionRechazo = '';
    }

    public function cancelarRechazo(): void
    {
        $this->solicitudParaRechazar = null;
    }

    public function confirmarRechazo(RechazarCambioZonaUseCase $rechazar): void
    {
        $this->authorize('revisar', SolicitudCambioZonaModel::class);

        if ($this->solicitudParaRechazar === null) {
            return;
        }

        $this->error = '';

        try {
            $rechazar->ejecutar(
                $this->solicitudParaRechazar,
                auth()->id(),
                $this->observacionRechazo !== '' ? $this->observacionRechazo : null,
            );
        } catch (SolicitudCambioZonaException $e) {
            $this->error = $e->getMessage();
        }

        $this->solicitudParaRechazar = null;
    }

    public function render(ListarSolicitudesPendientesUseCase $listar): View
    {
        return view('livewire.proveedor.solicitudes-cambio-zona', [
            'solicitudes' => $listar->ejecutar(),
        ]);
    }
}
