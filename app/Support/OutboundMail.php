<?php

namespace App\Support;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;

class OutboundMail
{
    /**
     * @param  class-string  $mailableClass
     * @param  array<int, mixed>  $mailableArgs
     */
    public static function queueAndLog(
        User $actor,
        ?User $recipient,
        Model $subject,
        string $description,
        string $mailableClass,
        array $mailableArgs,
    ): void {
        if ($recipient === null || blank($recipient->email)) {
            return;
        }

        Mail::to($recipient->email)->queue(new $mailableClass(...$mailableArgs));

        ActivityLog::query()->create([
            'subject_type' => $subject::class,
            'subject_id' => $subject->getKey(),
            'user_id' => $actor->id,
            'action' => 'email_sent',
            'description' => $description,
            'properties' => [
                'to' => $recipient->email,
                'mailable' => class_basename($mailableClass),
            ],
        ]);
    }
}
