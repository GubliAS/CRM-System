<?php

namespace App\Actions\Tasks;

use App\Models\Task;
use Illuminate\Support\Facades\DB;

class DeleteTask
{
    public function handle(Task $task): void
    {
        DB::transaction(function () use ($task): void {
            $task->delete();
        });
    }
}
