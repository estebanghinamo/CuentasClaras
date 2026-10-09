<?php

namespace App\Exceptions;

class InsufficientFundsException extends DomainException
{
    public function __construct(string $message = 'Fondos insuficientes para esta operación.', ?array $details = null)
    {
        parent::__construct('INSUFFICIENT_FUNDS', $message, 422, $details);
    }
}
