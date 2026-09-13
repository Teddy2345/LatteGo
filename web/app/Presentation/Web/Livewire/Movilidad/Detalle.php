<?php

declare(strict_types=1);

namespace App\Presentation\Web\Livewire\Movilidad;

use App\Application\Movilidad\DTOs\RegistrarNoEntregaData;
use App\Application\Movilidad\UseCases\ObtenerDetalleMovilidadUseCase;
use App\Application\Movilidad\UseCases\RegistrarNoEntregaUseCase;
use App\Application\Proveedor\DTOs\CrearSolicitudCambioZonaData;
use App\Application\Proveedor\UseCases\CrearSolicitudCambioZonaUseCase;
use App\Application\Proveedor\UseCases\ListarRutasDisponiblesUseCase;
use App\Domain\Proveedor\Exceptions\SolicitudCambioZonaException;
use App\Infrastructure\Movilidad\Models\MovilidadModel;
use App\Infrastructure\Proveedor\Models\SolicitudCambioZonaModel;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
final class Detalle extends Component
{
    public int $movilidadId;

    public ?int $proveedorParaNoEntrega = null;
    public string $motivoNoEntrega = '';

    public ?int $proveedorParaCambioZona = null;
    public ?int $rutaSolicitadaId = null;
    public string $fechaCambioZona = '';
    public string $motivoCambioZona = '';
    public string $errorCambioZona = '';

    public function mount(int $movilidadId): void
    {
        $this->authorize('view', MovilidadModel::class);

        $user = auth()->user();

        if (! $user->can('reportes.ver')) {
            $movilidad = MovilidadModel::query()->findOrFail($movilidadId);
            abort_if($movilidad->usuario_id !== $user->id, 403, 'Esta movilidad no te fue asignada.');
        }

        $this->movilidadId = $movilidadId;
        $this->fechaCambioZona = now()->format('Y-m-d');
    }

    public function abrirNoEntrega(int $proveedorId): void
    {
        $this->proveedorParaNoEntrega = $proveedorId;
        $this->motivoNoEntrega = '';
    }

    public function cancelarNoEntrega(): void
    {
        $this->proveedorParaNoEntrega = null;
    }

    public function confirmarNoEntrega(RegistrarNoEntregaUseCase $registrar): void
    {
        $this->authorize('registrarIncidencia', MovilidadModel::class);

        if ($this->proveedorParaNoEntrega === null) {
            return;
        }

        $registrar->ejecutar(new RegistrarNoEntregaData(
            proveedorId: $this->proveedorParaNoEntrega,
            movilidadId: $this->movilidadId,
            fecha: now()->format('Y-m-d'),
            motivo: $this->motivoNoEntrega !== '' ? $this->motivoNoEntrega : null,
            registradoPor: auth()->id(),
        ));

        $this->proveedorParaNoEntrega = null;
    }

    public function abrirCambioZona(int $proveedorId): void
    {
        $this->authorize('create', SolicitudCambioZonaModel::class);

        $this->proveedorParaCambioZona = $proveedorId;
        $this->rutaSolicitadaId = null;
        $this->motivoCambioZona = '';
        $this->errorCambioZona = '';
    }

    public function cancelarCambioZona(): void
    {
        $this->proveedorParaCambioZona = null;
    }

    public function confirmarCambioZona(CrearSolicitudCambioZonaUseCase $crear): void
    {
        $this->authorize('create', SolicitudCambioZonaModel::class);

        if ($this->proveedorParaCambioZona === null) {
            return;
        }

        $this->validate([
            'rutaSolicitadaId' => ['required', 'integer'],
            'fechaCambioZona' => ['required', 'date'],
        ], [], ['rutaSolicitadaId' => 'nueva zona', 'fechaCambioZona' => 'fecha del cambio']);

        $this->errorCambioZona = '';

        try {
            $crear->ejecutar(new CrearSolicitudCambioZonaData(
                proveedorId: $this->proveedorParaCambioZona,
                rutaSolicitadaId: (int) $this->rutaSolicitadaId,
                fechaCambio: $this->fechaCambioZona,
                motivo: $this->motivoCambioZona !== '' ? $this->motivoCambioZona : null,
                solicitadoPor: auth()->id(),
            ));
        } catch (SolicitudCambioZonaException $e) {
            $this->errorCambioZona = $e->getMessage();

            return;
        }

        $this->proveedorParaCambioZona = null;
    }

    public function render(ObtenerDetalleMovilidadUseCase $obtener, ListarRutasDisponiblesUseCase $listarRutas): View
    {
        return view('livewire.movilidad.detalle', [
            'movilidad' => $obtener->ejecutar($this->movilidadId),
            'rutasDisponibles' => $listarRutas->ejecutar(),
        ]);
    }
}
