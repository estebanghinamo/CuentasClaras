<?php

namespace App\Exceptions;

class ForbiddenException extends DomainException
{
    public function __construct(
        string $message = 'No tenés permiso para realizar esta acción.',
        ?array $details = null,
    ) {
        parent::__construct('FORBIDDEN', $message, 403, $details);
    }
}
