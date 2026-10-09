<?php

namespace App\Repositories;

use App\Exceptions\StoredProcedureException;
use Illuminate\Support\Facades\DB;
use JsonException;

abstract class BaseRepository
{
    /**
     * Llama un Stored Procedure con contrato JSON in / JSON out
     * (ver ESPECIFICACION_TECNICA.md §0.6 y skills/database.md).
     *
     * @throws StoredProcedureException si el SP devuelve success=false o no hay resultado.
     * @throws JsonException si el payload o el resultado no son JSON válido.
     */
    protected function call(string $procedure, array $payload = []): mixed
    {
        $json = json_encode($payload, JSON_THROW_ON_ERROR);

        $rows = DB::select("CALL {$procedure}(?)", [$json]);

        if (empty($rows)) {
            throw new StoredProcedureException($procedure, 'EMPTY_RESULT');
        }

        $result = json_decode($rows[0]->result, true, 512, JSON_THROW_ON_ERROR);

        if (($result['success'] ?? false) !== true) {
            throw new StoredProcedureException($procedure, $result['error'] ?? 'INTERNAL');
        }

        return $result['data'] ?? null;
    }
}
