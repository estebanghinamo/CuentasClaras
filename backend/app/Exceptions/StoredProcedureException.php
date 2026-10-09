<?php

namespace App\Exceptions;

use RuntimeException;

class StoredProcedureException extends RuntimeException
{
    public function __construct(
        public readonly string $procedure,
        public readonly string $spCode,
    ) {
        parent::__construct("Stored procedure {$procedure} devolvió error: {$spCode}");
    }
}
