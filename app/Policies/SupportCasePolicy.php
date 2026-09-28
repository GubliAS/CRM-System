<?php

namespace App\Policies;

use App\Models\SupportCase;
use App\Models\User;
use App\Support\RecordAccess;
use Illuminate\Database\Eloquent\Model;

class SupportCasePolicy extends CrmRecordPolicy
{
    protected function modelClass(): string
    {
        return SupportCase::class;
    }

    public function before(User $user, string $ability, mixed ...$arguments): ?bool
    {
        $record = $arguments[0] ?? null;

        if (
            $record instanceof SupportCase
            && $record->is_closed
            && in_array($ability, ['update', 'delete', 'changeOwner', 'changeStatus', 'close'], true)
        ) {
            return false;
        }

        return parent::before($user, $ability, ...$arguments);
    }

    public function update(User $user, Model $record): bool
    {
        if ($record instanceof SupportCase && $record->is_closed) {
            return false;
        }

        return parent::update($user, $record);
    }

    public function delete(User $user, Model $record): bool
    {
        if ($record instanceof SupportCase && $record->is_closed) {
            return false;
        }

        return parent::delete($user, $record);
    }

    public function changeOwner(User $user, SupportCase $case): bool
    {
        return $this->update($user, $case) && $user->mayReassignOwner();
    }

    public function changeStatus(User $user, SupportCase $case): bool
    {
        return $this->update($user, $case);
    }

    public function close(User $user, SupportCase $case): bool
    {
        return $this->update($user, $case);
    }

    public function reopen(User $user, SupportCase $case): bool
    {
        if (! $case->is_closed) {
            return false;
        }

        if (RecordAccess::before($user) === true) {
            return true;
        }

        return RecordAccess::update($user, $case);
    }
}
