<?php

namespace App\Mail;

use App\Models\DsarRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DsarRejectedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public DsarRequest $dsarRequest
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Update on your data request'
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.dsar.rejected',
            with: [
                'dsarRequest' => $this->dsarRequest,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}