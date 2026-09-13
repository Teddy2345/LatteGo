<?php

declare(strict_types=1);

namespace App\Presentation\Web\Livewire\Acopio;

use App\Application\Acopio\DTOs\AcopioData;
use App\Application\Acopio\UseCases\ListarAcopiosUseCase;
use App\Application\Acopio\UseCases\SincronizarAcopioUseCase;
use App\Application\Movilidad\UseCases\ActivarCamionUseCase;
use App\Application\Movilidad\UseCases\AgregarPersonaCamionUseCase;
use App\Application\Movilidad\UseCases\AsignarCamionARutaUseCase;
use App\Application\Movilidad\UseCases\AsignarUsuarioAMovilidadUseCase;
use App\Application\Movilidad\UseCases\ListarRutasConMovilidadesUseCase;
use App\Application\Movilidad\UseCases\QuitarCamionUseCase;
use App\Application\Movilidad\UseCases\QuitarPersonaCamionUseCase;
use App\Application\Movilidad\UseCases\RenombrarCamionUseCase;
use App\Application\Proveedor\UseCases\ListarProveedoresUseCase;
use App\Domain\Movilidad\Exceptions\MovilidadException;
use App\Infrastructure\Acopio\Models\AcopioModel;
use App\Infrastructure\Movilidad\Models\MovilidadModel;
use App\Models\User;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
final class Index extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'tailwind';
    public string $filtroEstado = '';
    public bool $showRutasModal = false;

    /** @var array<int, array{id: int, nombre: string}> */
    public array $rutas = [];

    /** @var array<int, array<int, array{id: int, nombre: string, activa: bool, personas: array, usuarioId: ?int}>> */
    public array $camionesPorRuta = [];

    /** @var array<int, string> nombre en edicion para "agregar persona", por movilidad */
    public array $nuevaPersonaPorCamion = [];

    public ?string $errorCamion = null;

    public function updatingFiltroEstado(): void
    {
        $this->resetPage();
    }

    public function mount(): void
    {
        $this->authorize('viewAny', AcopioModel::class);
    }

    public function sincronizar(int $id, SincronizarAcopioUseCase $useCase): void
    {
        $this->authorize('sincronizar', AcopioModel::class);

        $useCase->ejecutar($id);
    }

    public function abrirModalRutas(ListarRutasConMovilidadesUseCase $listar): void
    {
        $this->authorize('gestionar', MovilidadModel::class);

        $this->cargarRutasYCamiones($listar);
        $this->showRutasModal = true;
    }

    public function cerrarModalRutas(): void
    {
        $this->showRutasModal = false;
    }

    public function agregarCamion(int $rutaId, AsignarCamionARutaUseCase $asignar, ListarRutasConMovilidadesUseCase $listar): void
    {
        $this->authorize('gestionar', MovilidadModel::class);

        $asignar->ejecutar($rutaId);
        $this->cargarRutasYCamiones($listar);
    }

    public function quitarCamion(int $movilidadId, QuitarCamionUseCase $quitar, ListarRutasConMovilidadesUseCase $listar): void
    {
        $this->authorize('gestionar', MovilidadModel::class);

        $this->errorCamion = null;

        try {
            $quitar->ejecutar($movilidadId);
        } catch (MovilidadException $e) {
            $this->errorCamion = $e->getMessage();
        }

        $this->cargarRutasYCamiones($listar);
    }

    public function actualizarNombreCamion(int $movilidadId, string $nombre, RenombrarCamionUseCase $renombrar, ListarRutasConMovilidadesUseCase $listar): void
    {
        $this->authorize('gestionar', MovilidadModel::class);

        if (trim($nombre) !== '') {
            $renombrar->ejecutar($movilidadId, trim($nombre));
        }

        $this->cargarRutasYCamiones($listar);
    }

    public function seleccionarCamion(int $movilidadId, ActivarCamionUseCase $activar, ListarRutasConMovilidadesUseCase $listar): void
    {
        $this->authorize('gestionar', MovilidadModel::class);

        $activar->ejecutar($movilidadId);
        $this->cargarRutasYCamiones($listar);
    }

    public function agregarPersona(int $movilidadId, AgregarPersonaCamionUseCase $agregar, ListarRutasConMovilidadesUseCase $listar): void
    {
        $this->authorize('gestionar', MovilidadModel::class);

        $agregar->ejecutar($movilidadId, $this->nuevaPersonaPorCamion[$movilidadId] ?? '');
        $this->nuevaPersonaPorCamion[$movilidadId] = '';
        $this->cargarRutasYCamiones($listar);
    }

    public function quitarPersona(int $personaId, QuitarPersonaCamionUseCase $quitar, ListarRutasConMovilidadesUseCase $listar): void
    {
        $this->authorize('gestionar', MovilidadModel::class);

        $quitar->ejecutar($personaId);
        $this->cargarRutasYCamiones($listar);
    }

    /**
     * Fija (o quita, con $usuarioId vacio) al acopiador titular de una
     * movilidad: la persona real a quien pertenecen esa ruta y sus
     * proveedores.
     */
    public function asignarUsuario(int $movilidadId, string $usuarioId, AsignarUsuarioAMovilidadUseCase $asignar, ListarRutasConMovilidadesUseCase $listar): void
    {
        $this->authorize('gestionar', MovilidadModel::class);

        $asignar->ejecutar($movilidadId, $usuarioId === '' ? null : (int) $usuarioId);
        $this->cargarRutasYCamiones($listar);
    }

    private function cargarRutasYCamiones(ListarRutasConMovilidadesUseCase $listar): void
    {
        $datos = $listar->ejecutar();

        $this->rutas = array_map(static fn (array $r): array => ['id' => $r['id'], 'nombre' => $r['nombre']], $datos);

        $this->camionesPorRuta = [];
        foreach ($datos as $r) {
            $this->camionesPorRuta[$r['id']] = array_map(static fn ($c): array => [
                'id' => $c->id,
                'nombre' => $c->nombre,
                'activa' => $c->activa,
                'personas' => $c->personas,
                'usuarioId' => $c->usuarioId,
            ], $r['camiones']);
        }
    }

    public function render(ListarAcopiosUseCase $listar, ListarProveedoresUseCase $listarProveedores): View
    {
        $query = AcopioModel::query()->orderByDesc('fecha');

        if ($this->filtroEstado !== '') {
            $query->where('estado', $this->filtroEstado);
        }

        $paginador = $query->paginate(8);

        $mapped = $paginador->getCollection()->map(function (AcopioModel $m): AcopioData {
            return new AcopioData(
                id: $m->id,
                proveedorId: (int) $m->proveedor_id,
                acopiadorId: $m->acopiador_id,
                rutaId: $m->ruta_id,
                fecha: $m->fecha->format('Y-m-d'),
                cantidadLitros: (float) $m->cantidad_litros,
                estado: (string) $m->estado,
                observaciones: $m->observaciones,
                perdidaLitros: $m->perdida_litros === null ? null : (float) $m->perdida_litros,
                motivoPerdida: $m->motivo_perdida,
                semanaPagoInicio: $m->fecha->startOfWeek()->format('Y-m-d'),
                semanaPagoFin: $m->fecha->endOfWeek()->format('Y-m-d'),
            );
        });

        $paginador->setCollection($mapped);

        $proveedores = collect($listarProveedores->ejecutar())->keyBy('id');

        return view('livewire.acopio.index', [
            'acopios' => $paginador,
            'proveedores' => $proveedores,
            'usuariosAcopiadores' => User::role('acopiador')->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
