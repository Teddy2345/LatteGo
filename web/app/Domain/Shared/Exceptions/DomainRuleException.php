<?php

declare(strict_types=1);

namespace App\Domain\Shared\Exceptions;

/**
 * Marca las excepciones de violacion de una regla de negocio de dominio,
 * para que la capa Api pueda traducirlas a 422 sin acoplarse a cada feature.
 */
interface DomainRuleException
{
}
