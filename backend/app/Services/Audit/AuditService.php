<?php

namespace App\Services\Audit;

use App\DTOs\Audit\AuditEntryDto;
use App\DTOs\Audit\AuditLogDto;
use App\Repositories\AuditLogRepository;
use Illuminate\Support\Facades\Log;
use Throwable;

class AuditService
{
    public function __construct(private readonly AuditLogRepository $logs)
    {
    }

    /**
     * Registra una entrada de auditoría después de que la operación de negocio ya
     * ocurrió. Un fallo acá NUNCA debe romper la respuesta al cliente (ver
     * ESPECIFICACION_TECNICA.md §M-03 punto 4.4) salvo para category/service, que
     * auditan dentro del mismo SP de la operación (M-04/M-08, no acá).
     */
    public function log(AuditEntryDto $entry): void
    {
        try {
            $this->logs->create($entry);
        } catch (Throwable $e) {
            Log::error('No se pudo registrar la auditoría', [
                'entity_type' => $entry->entityType,
                'entity_id' => $entry->entityId,
                'action' => $entry->action,
                'exception' => $e,
            ]);
        }
    }

    /** @return array{items: \App\DTOs\Audit\AuditLogDto[], total: int} */
    public function list(int $workspaceId, int $page, int $perPage, ?string $entityType, ?int $userId): array
    {
        $result = $this->logs->listByWorkspace($workspaceId, $page, $perPage, $entityType, $userId);

        return [
            'items' => array_map(fn (array $row) => AuditLogDto::fromArray($row), $result['items']),
            'total' => (int) $result['total'],
        ];
    }
}
