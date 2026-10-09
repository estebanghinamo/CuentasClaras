<?php

namespace App\Exceptions;

use RuntimeException;

abstract class DomainException extends RuntimeException
{
    /**
     * @param string $errorCode código de error de la API (ej. "NOT_FOUND"). No se llama
     *   $code porque esa propiedad ya existe en \Exception (numérica, no readonly).
     */
    public function __construct(
        public readonly string $errorCode,
        string $message,
        public readonly int $status,
        public readonly ?array $details = null,
    ) {
        parent::__construct($message);
    }
}
