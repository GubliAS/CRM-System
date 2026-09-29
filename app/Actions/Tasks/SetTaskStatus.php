<?php

namespace App\Actions\Tasks;

use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SetTaskStatus
{
    public function handle(User $actor, Task $task, string $status): Task
    {
        return DB::transaction(function () use ($actor, $task, $status): Task {
            if ($task->status === $status) {
                return $task;
            }

            $task->status = $status;
            $task->updated_by = $actor->id;
            $task->save();

            return $task;
        });
    }
}
