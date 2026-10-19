<?php

declare(strict_types=1);

namespace App\Domain\Calidad\Exceptions;

/**
 * La entrega ya tiene su analisis. Un segundo analisis del mismo acopio
 * contaria la primera adulteracion como reincidencia y suspenderia al
 * proveedor la semana entera por una sola entrega.
 */
final class AcopioYaAnalizadoException extends CalidadException
{
}
