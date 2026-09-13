<?php

declare(strict_types=1);

namespace App\Domain\Produccion\ValueObjects;

/**
 * Quesos producidos por cada 100 litros procesados. El rango esperado es
 * de 11 a 12; fuera de ese rango no bloquea el registro, solo se puede
 * marcar como anomalia para revision.
 */
final readonly class RendimientoPorcentaje
{
    private const MINIMO_ESPERADO = 11.0;

    private const MAXIMO_ESPERADO = 12.0;

    public float $valor;

    private function __construct(float $valor)
    {
        $this->valor = round($valor, 2);
    }

    public static function calcular(LitrosProcesados $litros, QuesosProducidos $quesos): self
    {
        return new self(($quesos->valor / $litros->valor) * 100);
    }

    public function dentroDelRangoEsperado(): bool
    {
        return $this->valor >= self::MINIMO_ESPERADO && $this->valor <= self::MAXIMO_ESPERADO;
    }
}
