<?php

namespace App\Mail;

use App\Models\Task;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TaskAssignedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Task $task,
        public User $assignee,
        public User $actor,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Task assigned: '.$this->task->subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'mail.task-assigned',
        );
    }
}
