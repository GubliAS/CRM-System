<?php

namespace App\Policies;

use App\Models\User;
use App\Support\RecordAccess;
use Illuminate\Database\Eloquent\Model;

abstract class CrmRecordPolicy
{
    /**
     * @return class-string<Model>
     */
    abstract protected function modelClass(): string;

    public function before(User $user, string $ability, mixed ...$arguments): ?bool
    {
        return RecordAccess::before($user);
    }

    public function viewAny(User $user): bool
    {
        return RecordAccess::viewAny($user, $this->modelClass());
    }

    public function view(User $user, Model $record): bool
    {
        return RecordAccess::view($user, $record);
    }

    public function create(User $user): bool
    {
        return RecordAccess::create($user, $this->modelClass());
    }

    public function update(User $user, Model $record): bool
    {
        return RecordAccess::update($user, $record);
    }

    public function delete(User $user, Model $record): bool
    {
        return RecordAccess::delete($user, $record);
    }
}
