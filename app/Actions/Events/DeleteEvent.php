<?php

namespace App\Actions\Events;

use App\Models\Event;
use Illuminate\Support\Facades\DB;

class DeleteEvent
{
    public function handle(Event $event): void
    {
        DB::transaction(function () use ($event): void {
            $event->delete();
        });
    }
}
