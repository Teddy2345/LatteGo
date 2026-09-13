<?php

declare(strict_types=1);

namespace App\Presentation\Web\Livewire\Movilidad;

use App\Application\Acopio\DTOs\RegistrarAcopioData;
use App\Application\Acopio\UseCases\RegistrarAcopioUseCase;
use App\Application\Proveedor\UseCases\ObtenerProveedorUseCase;
use App\Domain\Acopio\Exceptions\AcopioException;
use App\Infrastructure\Acopio\Models\AcopioModel;
use App\Infrastructure\Movilidad\Models\MovilidadModel;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

/**
 * Registro de acopio de pocos toques: el acopiador ya eligio al proveedor
 * desde el detalle de su movilidad, asi que aqui solo confirma los litros.
 * Fecha, acopiador, ruta y movilidad se completan solos.
 */
#[Layout('layouts.app')]
final class RegistroRapido extends Component
{
    public int $movilidadId;
    public int $proveedorId;
    public string $proveedorNombre = '';

    #[Validate('required|numeric|min:0.01')]
    public float $litros = 0.0;

    public string $error = '';

    public function mount(int $movilidadId, int $proveedorId, ObtenerProveedorUseCase $obtener): void
    {
        $this->authorize('create', AcopioModel::class);

        $user = auth()->user();

        if (! $user->can('reportes.ver')) {
            $movilidad = MovilidadModel::query()->findOrFail($movilidadId);
            abort_if($movilidad->usuario_id !== $user->id, 403, 'Esta movilidad no te fue asignada.');
        }

        $this->movilidadId = $movilidadId;
        $this->proveedorId = $proveedorId;
        $this->proveedorNombre = $obtener->ejecutar($proveedorId)->nombre;
    }

    public function registrar(ObtenerProveedorUseCase $obtenerProveedor, RegistrarAcopioUseCase $registrar): void
    {
        $this->authorize('create', AcopioModel::class);

        $this->validate();
        $this->error = '';

        $proveedor = $obtenerProveedor->ejecutar($this->proveedorId);

        try {
            $registrar->ejecutar(new RegistrarAcopioData(
                proveedorId: $this->proveedorId,
                acopiadorId: auth()->id(),
                rutaId: $proveedor->rutaId,
                fecha: now()->format('Y-m-d'),
                cantidadLitros: $this->litros,
                observaciones: null,
                perdidaLitros: null,
                motivoPerdida: null,
                movilidadId: $this->movilidadId,
            ));
        } catch (AcopioException $e) {
            $this->error = $e->getMessage();

            return;
        }

        $this->redirect(route('movilidades.detalle', $this->movilidadId), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.movilidad.registro-rapido');
    }
}
