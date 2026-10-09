<?php

namespace App\Exceptions;

class MonthClosedException extends DomainException
{
    public function __construct(string $message, ?array $details = null)
    {
        parent::__construct('MONTH_CLOSED', $message, 422, $details);
    }
}
