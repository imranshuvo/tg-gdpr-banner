<?php

namespace App\Mail;

use App\Models\AlertLog;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SystemAlert extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public AlertLog $alert
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $prefix = match($this->alert->type) {
            AlertLog::TYPE_CRITICAL => '🚨 CRITICAL',
            AlertLog::TYPE_ERROR => '❌ ERROR',
            AlertLog::TYPE_WARNING => '⚠️ WARNING',
            default => 'ℹ️ INFO',
        };

        return new Envelope(
            subject: "{$prefix}: {$this->alert->title}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.system-alert',
            with: [
                'alert' => $this->alert,
                'url' => url('/admin/alerts/' . $this->alert->id),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}

