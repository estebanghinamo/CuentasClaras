<?php

namespace App\Exceptions;

class InvalidCredentialsException extends DomainException
{
    public function __construct(string $message = 'Email o contraseña incorrectos.', ?array $details = null)
    {
        parent::__construct('INVALID_CREDENTIALS', $message, 401, $details);
    }
}
