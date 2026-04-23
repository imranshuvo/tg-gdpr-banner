<?php

namespace App\Mail;

use App\Models\DsarRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DsarVerificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public DsarRequest $dsarRequest,
        public string $verificationUrl
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Verify your data request'
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.dsar.verification',
            with: [
                'dsarRequest' => $this->dsarRequest,
                'verificationUrl' => $this->verificationUrl,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}