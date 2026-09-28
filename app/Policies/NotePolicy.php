<?php

namespace App\Policies;

use App\Models\Note;

class NotePolicy extends CrmRecordPolicy
{
    protected function modelClass(): string
    {
        return Note::class;
    }
}
