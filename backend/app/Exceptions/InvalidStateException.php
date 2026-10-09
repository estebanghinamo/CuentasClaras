<?php

namespace App\Exceptions;

class InvalidStateException extends DomainException
{
    public function __construct(
        string $message = 'La operación no es válida en el estado actual.',
        ?array $details = null,
    ) {
        parent::__construct('INVALID_STATE', $message, 422, $details);
    }
}
