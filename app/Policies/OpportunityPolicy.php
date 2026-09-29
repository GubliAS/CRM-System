<?php

namespace App\Policies;

use App\Models\Opportunity;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class OpportunityPolicy extends CrmRecordPolicy
{
    protected function modelClass(): string
    {
        return Opportunity::class;
    }

    public function before(User $user, string $ability, mixed ...$arguments): ?bool
    {
        $record = $arguments[0] ?? null;

        if (
            $record instanceof Opportunity
            && $record->archived_at !== null
            && in_array($ability, ['update', 'delete', 'changeOwner'], true)
        ) {
            return false;
        }

        return parent::before($user, $ability, ...$arguments);
    }

    public function update(User $user, Model $record): bool
    {
        if ($record instanceof Opportunity && $record->archived_at !== null) {
            return false;
        }

        return parent::update($user, $record);
    }

    public function delete(User $user, Model $record): bool
    {
        if ($record instanceof Opportunity && $record->archived_at !== null) {
            return false;
        }

        return parent::delete($user, $record);
    }

    public function changeOwner(User $user, Opportunity $opportunity): bool
    {
        return $this->update($user, $opportunity) && $user->mayReassignOwner();
    }

    public function clone(User $user, Opportunity $opportunity): bool
    {
        return $this->create($user) && $this->view($user, $opportunity);
    }
}
