<?php

namespace App\Mail;

use App\Models\DsarRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DsarCompletedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public DsarRequest $dsarRequest,
        public ?string $downloadUrl = null
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your data request has been completed'
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.dsar.completed',
            with: [
                'dsarRequest' => $this->dsarRequest,
                'downloadUrl' => $this->downloadUrl,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}