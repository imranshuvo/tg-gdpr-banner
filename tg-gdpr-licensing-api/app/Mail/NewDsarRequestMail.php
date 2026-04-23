<?php

namespace App\Mail;

use App\Models\DsarRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewDsarRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public DsarRequest $dsarRequest,
        public string $adminUrl
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New verified DSAR request'
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.dsar.new-request',
            with: [
                'dsarRequest' => $this->dsarRequest,
                'adminUrl' => $this->adminUrl,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}