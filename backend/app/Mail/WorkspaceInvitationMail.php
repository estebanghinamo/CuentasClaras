<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WorkspaceInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $workspaceName,
        public readonly string $invitedByName,
        public readonly string $link,
    ) {}

    public function build(): self
    {
        return $this
            ->subject("{$this->invitedByName} te invitó a {$this->workspaceName} — Cuentas Claras")
            ->view('mail.workspace-invitation', [
                'workspaceName' => $this->workspaceName,
                'invitedByName' => $this->invitedByName,
                'link' => $this->link,
            ]);
    }
}
