<?php

namespace App\Policies;

use App\Models\Task;

class TaskPolicy extends CrmRecordPolicy
{
    protected function modelClass(): string
    {
        return Task::class;
    }
}
