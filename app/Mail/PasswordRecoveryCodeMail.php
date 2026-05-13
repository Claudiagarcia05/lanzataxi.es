<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PasswordRecoveryCodeMail extends Mailable {
    use Queueable, SerializesModels;

    public function __construct(
        public string $code,
        public int $expiresInMinutes,
    ) {
    }

    public function envelope(): Envelope {
        return new Envelope(
            subject: 'Código de recuperación de contraseña',
        );
    }

    public function content(): Content {
        return new Content(
            view: 'emails.password-recovery-code',
            with: [
                'code' => $this->code,
                'expiresInMinutes' => $this->expiresInMinutes,
            ],
        );
    }
}