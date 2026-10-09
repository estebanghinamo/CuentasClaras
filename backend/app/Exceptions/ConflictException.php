<?php

namespace App\Exceptions;

class ConflictException extends DomainException
{
    public function __construct(string $message = 'El recurso ya existe.', ?array $details = null)
    {
        parent::__construct('CONFLICT', $message, 409, $details);
    }
}
