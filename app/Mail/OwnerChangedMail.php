<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OwnerChangedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Model $record,
        public User $newOwner,
        public User $actor,
        public string $recordLabel,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'You now own '.$this->recordLabel,
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'mail.owner-changed',
        );
    }
}
