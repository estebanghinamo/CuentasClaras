<?php

namespace App\Services\Finance;

use App\DTOs\Audit\AuditEntryDto;
use App\DTOs\Finance\SmartSuggestionDto;
use App\DTOs\Workspace\WorkspaceMembershipDto;
use App\Exceptions\InvalidStateException;
use App\Exceptions\NotFoundException;
use App\Repositories\SmartSuggestionRepository;
use App\Repositories\WorkspaceRepository;
use App\Services\Audit\AuditService;
use App\Services\Audit\AuditSummaries;
use App\Services\Notifications\NotificationService;

/**
 * Recordatorios inteligentes (ESPECIFICACION_TECNICA.md M-18): detecta gastos
 * recurrentes no modelados como servicio y sugiere convertirlos.
 */
class SmartSuggestionService
{
    public function __construct(
        private readonly SmartSuggestionRepository $suggestions,
        private readonly WorkspaceRepository $workspaces,
        private readonly ServiceService $services,
        private readonly AuditService $audit,
        private readonly NotificationService $notifications,
    ) {}

    /** Disparado por ScanSmartSuggestionsJob (tras cada cierre - M-13, y semanal). */
    public function scan(int $workspaceId): void
    {
        $lookbackMonths = (int) config('cuentas.smart_suggestions_lookback_months');
        $minOccurrences = (int) config('cuentas.smart_suggestions_min_occurrences');
        $tolerancePct = (float) config('cuentas.smart_suggestions_amount_tolerance_pct');
        $dateFrom = now()->subMonthsNoOverflow($lookbackMonths)->format('Y-m-d');

        $candidates = $this->suggestions->candidates($workspaceId, $dateFrom, $minOccurrences, $tolerancePct);

        foreach ($candidates as $candidate) {
            $result = $this->suggestions->upsert($workspaceId, $candidate);

            if (!empty($result['created'])) {
                $members = $this->workspaces->listMembers($workspaceId);
                foreach ($members as $member) {
                    $this->notifications->notify(
                        userId: (int) $member['user_id'],
                        type: 'smart_suggestion',
                        title: 'Gasto recurrente detectado',
                        body: "Detectamos un gasto recurrente: {$candidate['description']}"
                            ." ~\${$candidate['avg_amount']} por mes.",
                        route: "/w/{$workspaceId}/suggestions",
                        workspaceId: $workspaceId,
                    );
                }
            }
        }
    }

    /** @return SmartSuggestionDto[] */
    public function list(int $workspaceId, string $status): array
    {
        return array_map(
            fn (array $row) => SmartSuggestionDto::fromArray($row),
            $this->suggestions->list($workspaceId, $status),
        );
    }

    /** @return array{suggestion: SmartSuggestionDto, service: \App\DTOs\Finance\ServiceDto} */
    public function accept(WorkspaceMembershipDto $membership, int $suggestionId, array $serviceData): array
    {
        $row = $this->suggestions->find($membership->workspaceId, $suggestionId);

        if ($row === null) {
            throw new NotFoundException();
        }

        if ($row['status'] !== 'pending') {
            throw new InvalidStateException();
        }

        $serviceDto = $this->services->create($membership, $serviceData);

        $updated = $this->suggestions->updateStatus(
            $membership->workspaceId,
            $suggestionId,
            'accepted',
            $serviceDto->id,
            $membership->userId,
        );

        $this->audit->log(new AuditEntryDto(
            workspaceId: $membership->workspaceId,
            userId: $membership->userId,
            entityType: 'smart_suggestion',
            entityId: $suggestionId,
            action: 'updated',
            summary: AuditSummaries::for('smart_suggestion', 'updated', [
                'description' => $row['description'],
                'action' => 'accepted',
            ]),
        ));

        return ['suggestion' => SmartSuggestionDto::fromArray($updated), 'service' => $serviceDto];
    }

    public function dismiss(WorkspaceMembershipDto $membership, int $suggestionId): SmartSuggestionDto
    {
        $row = $this->suggestions->find($membership->workspaceId, $suggestionId);

        if ($row === null) {
            throw new NotFoundException();
        }

        if ($row['status'] !== 'pending') {
            throw new InvalidStateException();
        }

        $updated = $this->suggestions->updateStatus(
            $membership->workspaceId,
            $suggestionId,
            'dismissed',
            null,
            $membership->userId,
        );

        $this->audit->log(new AuditEntryDto(
            workspaceId: $membership->workspaceId,
            userId: $membership->userId,
            entityType: 'smart_suggestion',
            entityId: $suggestionId,
            action: 'updated',
            summary: AuditSummaries::for('smart_suggestion', 'updated', [
                'description' => $row['description'],
                'action' => 'dismissed',
            ]),
        ));

        return SmartSuggestionDto::fromArray($updated);
    }
}
