<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $email,
        public readonly string $token,
    ) {}

    public function build(): self
    {
        $link = sprintf(
            '%s/auth/reset-password?token=%s&email=%s',
            rtrim((string) config('cuentas.frontend_url'), '/'),
            $this->token,
            urlencode($this->email),
        );

        return $this
            ->subject('Recuperá tu contraseña — Cuentas Claras')
            ->view('mail.password-reset', ['link' => $link]);
    }
}
