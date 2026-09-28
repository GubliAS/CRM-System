<?php

namespace App\Actions\Tasks;

use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CompleteTask
{
    public function handle(User $actor, Task $task): Task
    {
        return DB::transaction(function () use ($actor, $task): Task {
            if ($task->status === 'Completed') {
                return $task;
            }

            $task->status = 'Completed';
            $task->updated_by = $actor->id;
            $task->save();

            return $task;
        });
    }
}
