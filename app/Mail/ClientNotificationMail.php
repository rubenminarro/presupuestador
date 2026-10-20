<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Email de un aviso al cliente. El contenido lo arma
 * ClientNotificationService::content() (independiente del canal).
 */
class ClientNotificationMail extends Mailable
{
    /**
     * @param array{subject: string, greeting: string, lines: string[], rows: array<int, array<string, string>>} $content
     */
    public function __construct(
        public readonly array $content
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->content['subject'],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.client-notification',
            with: [
                'greeting' => $this->content['greeting'],
                'lines' => $this->content['lines'],
                'rows' => $this->content['rows'],
            ],
        );
    }
}
