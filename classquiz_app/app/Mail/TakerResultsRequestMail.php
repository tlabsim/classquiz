<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TakerResultsRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Collection $sessions,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your ClassQuiz Results',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.taker-results',
        );
    }
}
