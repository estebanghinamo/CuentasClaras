<?php

namespace App\Exceptions;

class InvitationInvalidException extends DomainException
{
    public function __construct(string $message = 'La invitación no es válida o ya venció.', ?array $details = null)
    {
        parent::__construct('INVITATION_INVALID', $message, 410, $details);
    }
}
