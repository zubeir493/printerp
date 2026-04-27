<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceGenerated extends Mailable
{
    use Queueable, SerializesModels;

    public array $invoiceData;
    public array $options;

    public function __construct(array $invoiceData, array $options = [])
    {
        $this->invoiceData = $invoiceData;
        $this->options = array_merge([
            'subject_prefix' => 'Document',
            'include_terms' => true,
        ], $options);
    }

    public function envelope(): Envelope
    {
        $invoiceNumber = $this->invoiceData['invoice_data']['invoice_number'] ?? 
                        $this->invoiceData['receipt_data']['receipt_number'] ?? 
                        'Document';
        
        $subject = $this->options['subject_prefix'] . " #{$invoiceNumber} from " . config('app.name');

        return new Envelope(
            subject: $subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.invoice-generated',
            with: [
                'invoiceData' => $this->invoiceData,
                'companyInfo' => $this->invoiceData['invoice_data']['company_info'] ?? 
                               $this->invoiceData['receipt_data']['company_info'] ?? [],
                'options' => $this->options,
            ]
        );
    }

    public function attachments(): array
    {
        $filename = $this->invoiceData['filename'];
        $path = storage_path("app/public/{$this->invoiceData['path']}");

        return [
            Attachment::fromPath($path)
                ->as($filename)
                ->withMime('application/pdf'),
        ];
    }
}
