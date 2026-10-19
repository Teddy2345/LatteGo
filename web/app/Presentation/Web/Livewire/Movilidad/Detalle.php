<?php

declare(strict_types=1);

namespace App\Presentation\Web\Livewire\Movilidad;

use App\Application\Movilidad\DTOs\RegistrarNoEntregaData;
use App\Application\Movilidad\UseCases\ObtenerDetalleMovilidadUseCase;
use App\Application\Movilidad\UseCases\RegistrarNoEntregaUseCase;
use App\Infrastructure\Movilidad\Models\MovilidadModel;
use App\Infrastructure\Proveedor\Models\ProveedorModel;
use App\Presentation\Support\AlcanceMovilidades;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('layouts.app')]
final class Detalle extends Component
{
    #[Locked]
    public int $movilidadId;

    public ?int $proveedorParaNoEntrega = null;
    public string $motivoNoEntrega = '';

    public function mount(int $movilidadId): void
    {
        $this->authorize('view', MovilidadModel::class);

        AlcanceMovilidades::propia(auth()->user(), $movilidadId);

        $this->movilidadId = $movilidadId;
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

    /**
     * El proveedor llega del navegador, asi que se comprueba que sea del
     * recorrido de esta movilidad: si no, se podria marcar un "no entrego" o
     * pedir el traslado de un ganadero de otra ruta.
     */
    private function asegurarProveedorDelRecorrido(int $proveedorId): void
    {
        AlcanceMovilidades::asegurarProveedorDelRecorrido(
            AlcanceMovilidades::propia(auth()->user(), $this->movilidadId),
            ProveedorModel::query()->whereKey($proveedorId)->value('ruta_id'),
        );
    }

    public function confirmarNoEntrega(RegistrarNoEntregaUseCase $registrar): void
    {
        $this->authorize('registrarIncidencia', MovilidadModel::class);

        if ($this->proveedorParaNoEntrega === null) {
            return;
        }

        $this->asegurarProveedorDelRecorrido($this->proveedorParaNoEntrega);

        $registrar->ejecutar(new RegistrarNoEntregaData(
            proveedorId: $this->proveedorParaNoEntrega,
            movilidadId: $this->movilidadId,
            fecha: now()->format('Y-m-d'),
            motivo: $this->motivoNoEntrega !== '' ? $this->motivoNoEntrega : null,
            registradoPor: auth()->id(),
        ));

        $this->proveedorParaNoEntrega = null;
    }

    public function render(ObtenerDetalleMovilidadUseCase $obtener): View
    {
        return view('livewire.movilidad.detalle', [
            'movilidad' => $obtener->ejecutar($this->movilidadId),
        ]);
    }
}
