<?php

namespace App\Mail;

use App\Models\Proposal;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProposalMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Proposal $proposal,
        public string $pdfPath,
        public string $pdfName,
        public string $companyName,
        public string $contactName,
        public string $businessName,
        public float $totalValue,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Business Proposal '.$this->proposal->id.' - '.$this->businessName,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.proposal');
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromPath($this->pdfPath)
                ->as($this->pdfName)
                ->withMime('application/pdf'),
        ];
    }
}
