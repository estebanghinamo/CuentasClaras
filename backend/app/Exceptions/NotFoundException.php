<?php

namespace App\Exceptions;

class NotFoundException extends DomainException
{
    public function __construct(string $message = 'El recurso solicitado no existe.', ?array $details = null)
    {
        parent::__construct('NOT_FOUND', $message, 404, $details);
    }
}
