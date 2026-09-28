<?php

namespace App\Policies;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class LeadPolicy extends CrmRecordPolicy
{
    protected function modelClass(): string
    {
        return Lead::class;
    }

    public function before(User $user, string $ability, mixed ...$arguments): ?bool
    {
        $record = $arguments[0] ?? null;

        if (
            $record instanceof Lead
            && $record->converted
            && in_array($ability, ['update', 'delete', 'convert', 'changeOwner', 'changeStatus'], true)
        ) {
            return false;
        }

        return parent::before($user, $ability, ...$arguments);
    }

    public function update(User $user, Model $record): bool
    {
        if ($record instanceof Lead && $record->converted) {
            return false;
        }

        return parent::update($user, $record);
    }

    public function delete(User $user, Model $record): bool
    {
        if ($record instanceof Lead && $record->converted) {
            return false;
        }

        return parent::delete($user, $record);
    }

    public function convert(User $user, Lead $lead): bool
    {
        return $this->update($user, $lead);
    }

    public function changeOwner(User $user, Lead $lead): bool
    {
        return $this->update($user, $lead) && $user->mayReassignOwner();
    }

    public function changeStatus(User $user, Lead $lead): bool
    {
        return $this->update($user, $lead);
    }
}
