<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ShareArtwork extends Mailable
{
    use Queueable, SerializesModels;

    public $artwork;
    public $recipientEmail;
    public $customMessage;

    /**
     * Create a new message instance.
     */
    public function __construct($artwork, $recipientEmail, $customMessage = null)
    {
        $this->artwork = $artwork->load(['jobOrder', 'uploader']);
        $this->recipientEmail = $recipientEmail;
        $this->customMessage = $customMessage;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Artwork Shared: ' . basename($this->artwork->filename),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.share-artwork',
            with: [
                'artwork' => $this->artwork,
                'customMessage' => $this->customMessage,
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
