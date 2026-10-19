<?php

declare(strict_types=1);

namespace App\Application\Comprobantes\UseCases;

use App\Domain\Comprobantes\Repositories\ComprobanteRepository;
use App\Domain\Comprobantes\ValueObjects\TipoComprobante;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Emite la boleta o la factura de un cobro que ya ocurrio: una venta de
 * mostrador o un pedido de la tienda. No cobra ni mueve stock; solo pone
 * el documento con su numeracion.
 */
final class EmitirComprobanteUseCase
{
    public function __construct(private readonly ComprobanteRepository $comprobantes)
    {
    }

    /** @return array<string, mixed> */
    public function ejecutar(array $datos, int $usuarioId): array
    {
        $datos = Validator::make($datos, [
            'tipo' => 'required|string|in:boleta,factura',
            'fecha' => 'required|date_format:Y-m-d',
            'clienteNombre' => 'required|string|max:160',
            'clienteDocumento' => 'nullable|string|max:20',
            'clienteDireccion' => 'nullable|string|max:255',
            'total' => 'required|numeric|min:0|max:99999999.99',
            'movimientoInventarioId' => 'nullable|integer|exists:movimientos_inventario,id',
            'pedidoId' => 'nullable|integer|exists:pedidos,id',
        ])->validate();

        if (empty($datos['movimientoInventarioId']) && empty($datos['pedidoId'])) {
            throw ValidationException::withMessages([
                'tipo' => 'Un comprobante se emite por una venta o por un pedido.',
            ]);
        }

        $tipo = TipoComprobante::desde($datos['tipo']);

        // La factura va a nombre de una empresa: el RUC no es opcional.
        if ($tipo->exigeDocumento() && trim((string) ($datos['clienteDocumento'] ?? '')) === '') {
            throw ValidationException::withMessages([
                'clienteDocumento' => 'Una factura necesita el RUC del cliente. Si no lo tienes, emite una boleta.',
            ]);
        }

        return $this->comprobantes->emitir($datos, $usuarioId);
    }
}
