<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

/**
 * Mailable genérico para el canal 'mail' del NotificationDispatcher (M-17) -
 * una plantilla, título/cuerpo/link dinámicos por tipo de notificación.
 *
 * Sin ShouldQueue a propósito (2026-09-23): el dispatcher lo manda sincrónico
 * con Mail::send(), igual criterio que el push (FcmChannel) - ninguno de los
 * dos canales best-effort depende de un worker de colas corriendo. Si algún
 * día esto se vuelve un cuello de botella real (muchos destinatarios a la
 * vez), reconsiderar encolar, pero entonces con un worker garantizado en
 * todos los entornos, no solo en producción.
 */
class NotificationMail extends Mailable
{
    public function __construct(
        public readonly string $title,
        public readonly string $body,
        public readonly ?string $link,
    ) {}

    public function build(): self
    {
        return $this->subject("{$this->title} — Cuentas Claras")->view('mail.notification', [
            'title' => $this->title,
            'body' => $this->body,
            'link' => $this->link,
        ]);
    }
}
