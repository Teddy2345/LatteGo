<?php

declare(strict_types=1);

namespace App\Presentation\Web\Livewire\Pedidos;

use App\Domain\Pedidos\ValueObjects\MetodoPago;
use App\Infrastructure\Pedidos\Models\PedidoModel;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Los cobros que no fueron en efectivo, para cotejarlos con el banco.
 *
 * Yape, una transferencia o un QR dejan un numero de operacion. Esta
 * pantalla los junta por fecha para compararlos uno a uno contra el estado
 * de cuenta: es lo que convierte una captura de pantalla en algo que se
 * puede verificar. El efectivo no aparece porque se cuadra con la caja, no
 * con el banco.
 */
#[Layout('layouts.app')]
final class Cobros extends Component
{
    use WithFileUploads;

    public string $desde = '';

    public string $hasta = '';

    public string $metodo = '';

    public function mount(): void
    {
        $this->authorize('viewAny', PedidoModel::class);

        // Por defecto, la semana que se esta cerrando.
        $this->desde = now()->subDays(7)->format('Y-m-d');
        $this->hasta = now()->format('Y-m-d');
    }


    /** El QR de Yape de la planta, que el repartidor muestra al cobrar. */
    public $qrCobro = null;

    public string $avisoQr = '';

    /**
     * Guarda el QR de la planta en una ruta fija: al reemplazarlo, todos
     * los telefonos toman el nuevo sin tener que actualizar la app.
     */
    public function guardarQr(): void
    {
        $this->authorize('revisar', PedidoModel::class);

        $this->validate(['qrCobro' => 'required|image|max:4096'], [], ['qrCobro' => 'imagen del QR']);

        Storage::disk('public')->put(config('ecolactea.cobros.qr_path'), file_get_contents($this->qrCobro->getRealPath()));

        $this->qrCobro = null;
        $this->avisoQr = 'QR actualizado. Los repartidores ya lo ven en su celular.';
    }

    public function render(): View
    {
        $this->authorize('viewAny', PedidoModel::class);

        $digitales = array_map(
            static fn (MetodoPago $m): string => $m->value,
            array_values(array_filter(MetodoPago::cases(), static fn (MetodoPago $m): bool => $m->exigeReferencia())),
        );

        $query = PedidoModel::query()
            ->whereNotNull('fecha_entrega')
            ->whereIn('metodo_pago', $this->metodo !== '' ? [$this->metodo] : $digitales)
            ->orderByDesc('fecha_entrega')
            ->orderByDesc('id');

        if ($this->desde !== '') {
            $query->whereDate('fecha_entrega', '>=', $this->desde);
        }

        if ($this->hasta !== '') {
            $query->whereDate('fecha_entrega', '<=', $this->hasta);
        }

        $cobros = $query->limit(200)->get();

        return view('livewire.pedidos.cobros', [
            'cobros' => $cobros,
            'total' => round((float) $cobros->sum('monto_cobrado'), 2),
            'sinReferencia' => $cobros->filter(static fn (PedidoModel $p): bool => blank($p->referencia_pago))->count(),
            'metodos' => array_filter(MetodoPago::cases(), static fn (MetodoPago $m): bool => $m->exigeReferencia()),
            'qrActual' => Storage::disk('public')->exists(config('ecolactea.cobros.qr_path'))
                ? Storage::disk('public')->url(config('ecolactea.cobros.qr_path'))
                : null,
        ]);
    }
}
