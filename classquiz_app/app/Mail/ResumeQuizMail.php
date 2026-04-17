<?php

namespace App\Mail;

use App\Models\QuizSession;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ResumeQuizMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly QuizSession $session,
        public readonly string $resumeUrl,
        public readonly string $quizTitle,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Resume Your Quiz – ' . $this->quizTitle,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.resume-quiz',
        );
    }
}
