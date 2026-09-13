<?php

declare(strict_types=1);

namespace App\Domain\Shared\Exceptions;

/**
 * Marca las excepciones de dominio que representan "recurso no encontrado",
 * para que la capa Api pueda traducirlas a 404 sin acoplarse a cada feature.
 */
interface NotFoundException
{
}
