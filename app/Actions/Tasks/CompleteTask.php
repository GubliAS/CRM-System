<?php

namespace App\Actions\Tasks;

use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CompleteTask
{
    public const STATUS_COMPLETED = 'Completed';

    public function handle(User $actor, Task $task): Task
    {
        return DB::transaction(function () use ($actor, $task): Task {
            if ($task->status === self::STATUS_COMPLETED) {
                return $task;
            }

            $task->status = self::STATUS_COMPLETED;
            $task->updated_by = $actor->id;
            $task->save();

            return $task;
        });
    }
}
