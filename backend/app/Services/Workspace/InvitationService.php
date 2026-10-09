<?php

namespace App\Services\Workspace;

use App\DTOs\Audit\AuditEntryDto;
use App\DTOs\Workspace\InvitationDto;
use App\DTOs\Workspace\InvitationPreviewDto;
use App\DTOs\Workspace\WorkspaceDto;
use App\DTOs\Workspace\WorkspaceMembershipDto;
use App\Exceptions\ConflictException;
use App\Exceptions\ForbiddenException;
use App\Exceptions\InvalidStateException;
use App\Exceptions\InvitationInvalidException;
use App\Exceptions\StoredProcedureException;
use App\Mail\WorkspaceInvitationMail;
use App\Repositories\InvitationRepository;
use App\Repositories\UserRepository;
use App\Repositories\WorkspaceRepository;
use App\Services\Audit\AuditService;
use App\Services\Audit\AuditSummaries;
use App\Services\Notifications\NotificationDispatcher;
use App\Services\Notifications\NotificationService;
use App\Services\Support\SpErrorMapper;
use Illuminate\Support\Facades\Mail;

class InvitationService
{
    private const CODE_ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789'; // sin 0,O,1,I,L

    public function __construct(
        private readonly InvitationRepository $invitations,
        private readonly AuditService $audit,
        private readonly UserRepository $users,
        private readonly NotificationService $notifications,
        private readonly WorkspaceRepository $workspaces,
        private readonly NotificationDispatcher $dispatcher,
    ) {}

    public function create(WorkspaceMembershipDto $membership, array $data): InvitationDto
    {
        if ($membership->workspaceType === 'individual') {
            throw new InvalidStateException(
                'Un workspace individual no admite invitaciones. Cambiá el tipo en Configuración.',
            );
        }

        $email = $data['email'] ?? null;
        $expiresInDays = (int) ($data['expires_in_days'] ?? config('cuentas.invitation_ttl_days'));
        $expiresAt = now()->addDays($expiresInDays)->format('Y-m-d H:i:s');

        $row = null;

        for ($attempt = 0; $attempt < 3 && $row === null; $attempt++) {
            $code = $this->generateCode();

            try {
                $row = $this->invitations->create(
                    $membership->workspaceId,
                    $code,
                    $email,
                    $expiresAt,
                    $membership->userId,
                );
            } catch (StoredProcedureException $e) {
                if ($e->spCode !== 'DUPLICATE') {
                    throw SpErrorMapper::map($e);
                }
            }
        }

        if ($row === null) {
            throw new ConflictException('No se pudo generar un código de invitación único. Probá de nuevo.');
        }

        $dto = InvitationDto::fromArray($row, $this->link($row['code']));

        if ($email !== null) {
            Mail::to($email)->queue(
                new WorkspaceInvitationMail($membership->workspaceName, $row['created_by_name'], $dto->link),
            );

            // Si el email invitado ya es un usuario del sistema, no dependemos
            // de que el mail llegue (SMTP sin configurar, va a spam, etc.):
            // le queda un aviso in-app que puede ver y aceptar desde el home
            // sin salir de la app (ver InvitationController::mine). Queda
            // como registro permanente aunque la invitacion despues se acepte,
            // expire o se revoque - workspace_id va NULL a proposito, todavia
            // no es miembro de ese workspace.
            $invitedUser = $this->users->findByEmail($email);
            if ($invitedUser !== null) {
                $this->notifications->notify(
                    userId: (int) $invitedUser['id'],
                    type: 'workspace_invitation',
                    title: "Invitación a {$membership->workspaceName}",
                    body: "{$row['created_by_name']} te invitó a unirte a {$membership->workspaceName}",
                    route: '/',
                    payload: [
                        'code' => $dto->code,
                        'workspace_name' => $membership->workspaceName,
                        'invited_by_name' => $row['created_by_name'],
                    ],
                );
            }
        }

        $this->audit->log(new AuditEntryDto(
            workspaceId: $membership->workspaceId,
            userId: $membership->userId,
            entityType: 'workspace_invitation',
            entityId: $dto->id,
            action: 'created',
            summary: AuditSummaries::for('workspace_invitation', 'created', ['email' => $email]),
            oldValue: null,
            newValue: ['code' => $dto->code, 'email' => $dto->email, 'expires_at' => $dto->expiresAt],
            ipAddress: request()->ip(),
            userAgent: request()->userAgent() !== null ? substr(request()->userAgent(), 0, 255) : null,
        ));

        return $dto;
    }

    /** @return InvitationDto[] */
    public function list(WorkspaceMembershipDto $membership): array
    {
        return array_map(
            fn (array $row) => InvitationDto::fromArray($row, $this->link($row['code'])),
            $this->invitations->listByWorkspace($membership->workspaceId),
        );
    }

    public function preview(string $code): InvitationPreviewDto
    {
        $code = strtoupper(trim($code));
        $row = $this->findValidOrFail($code);

        return InvitationPreviewDto::fromArray($row, $code);
    }

    /**
     * Invitaciones pendientes para el email del usuario logueado - le permiten
     * aceptar directo desde la app sin depender de que el mail haya llegado.
     *
     * @return InvitationPreviewDto[]
     */
    public function listPendingForEmail(string $email): array
    {
        return array_map(
            fn (array $row) => InvitationPreviewDto::fromArray($row, (string) $row['code']),
            $this->invitations->listPendingByEmail($email),
        );
    }

    public function accept(string $code, int $userId, string $userEmail): WorkspaceDto
    {
        $code = strtoupper(trim($code));
        $row = $this->findValidOrFail($code);

        if ($row['email'] !== null && strtolower((string) $row['email']) !== strtolower($userEmail)) {
            throw new ForbiddenException('Esta invitación es para otro email.');
        }

        // El SP hace un INSERT idempotente (no duplica la fila si ya sos
        // miembro), pero eso dejaba la invitación marcada 'accepted' sin
        // sumar a nadie - un link pensado para una persona nueva quedaba
        // "gastado" si quien lo creó (u otro miembro) lo abría para probarlo.
        // Se corta ACÁ, antes de tocar el SP, para que la invitación siga
        // pendiente y utilizable por la persona a la que estaba destinada.
        if ($this->workspaces->getMembership((int) $row['workspace_id'], $userId) !== null) {
            throw new ConflictException('Ya sos miembro de este workspace.');
        }

        try {
            $result = $this->invitations->accept($code, $userId, now()->format('Y-m-d H:i:s'));
        } catch (StoredProcedureException $e) {
            throw SpErrorMapper::map($e, [
                'INVALID_STATE' => new InvitationInvalidException(),
            ]);
        }

        $dto = WorkspaceDto::fromArray($result);

        $this->audit->log(new AuditEntryDto(
            workspaceId: $dto->id,
            userId: $userId,
            entityType: 'workspace_member',
            entityId: $userId,
            action: 'created',
            summary: AuditSummaries::for('workspace_member', 'created', ['workspace_name' => $dto->name]),
            oldValue: null,
            newValue: ['user_id' => $userId, 'role' => 'member'],
            ipAddress: request()->ip(),
            userAgent: request()->userAgent() !== null ? substr(request()->userAgent(), 0, 255) : null,
        ));

        $this->notifyOwnerOfNewMember($dto, $userId);

        return $dto;
    }

    /** M-17 (2026-09-23): avisa al owner del workspace que alguien se sumó vía invitación. */
    private function notifyOwnerOfNewMember(WorkspaceDto $dto, int $newMemberId): void
    {
        $owner = collect($this->workspaces->listMembers($dto->id))->firstWhere('role', 'owner');
        if ($owner === null || (int) $owner['user_id'] === $newMemberId) {
            return;
        }

        $newMember = $this->users->findById($newMemberId);
        $newMemberName = $newMember['name'] ?? 'Un nuevo miembro';

        $this->dispatcher->notifyUser(
            (int) $owner['user_id'],
            'workspace_member_joined',
            'Nuevo miembro en '.$dto->name,
            "{$newMemberName} se unió a {$dto->name}.",
            '/w/'.$dto->id.'/members',
            $dto->id,
            ['user_id' => $newMemberId, 'user_name' => $newMemberName],
        );
    }

    public function revoke(WorkspaceMembershipDto $membership, int $invitationId): void
    {
        try {
            $this->invitations->revoke($membership->workspaceId, $invitationId);
        } catch (StoredProcedureException $e) {
            throw SpErrorMapper::map($e, [
                'INVALID_STATE' => new InvalidStateException('La invitación ya no está pendiente.'),
            ]);
        }

        $this->audit->log(new AuditEntryDto(
            workspaceId: $membership->workspaceId,
            userId: $membership->userId,
            entityType: 'workspace_invitation',
            entityId: $invitationId,
            action: 'deleted',
            summary: AuditSummaries::for('workspace_invitation', 'deleted'),
            oldValue: ['status' => 'pending'],
            newValue: null,
            ipAddress: request()->ip(),
            userAgent: request()->userAgent() !== null ? substr(request()->userAgent(), 0, 255) : null,
        ));
    }

    private function findValidOrFail(string $code): array
    {
        $row = $this->invitations->findByCode($code);

        if ($row === null || $row['status'] !== 'pending' || strtotime((string) $row['expires_at']) < time()) {
            throw new InvitationInvalidException();
        }

        return $row;
    }

    private function generateCode(): string
    {
        $length = (int) config('cuentas.invitation_code_length');
        $alphabet = self::CODE_ALPHABET;
        $code = '';

        for ($i = 0; $i < $length; $i++) {
            $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        return $code;
    }

    private function link(string $code): string
    {
        return rtrim((string) config('cuentas.frontend_url'), '/')."/invite/{$code}";
    }
}
