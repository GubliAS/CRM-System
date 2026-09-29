<?php

namespace App\Policies;

use App\Models\Report;
use App\Models\User;
use App\Support\RecordAccess;
use App\Support\ReportObjects;

class ReportPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return RecordAccess::before($user);
    }

    public function viewAny(User $user): bool
    {
        return ReportObjects::objectsForUser($user) !== [];
    }

    public function view(User $user, Report $report): bool
    {
        if (! ReportObjects::userCanAccessObject($user, $report->object_type)) {
            return false;
        }

        if ($report->folder === Report::FOLDER_PRIVATE) {
            return (int) $report->owner_id === (int) $user->id;
        }

        return $report->folder === Report::FOLDER_PUBLIC;
    }

    public function create(User $user): bool
    {
        return match ($user->role?->slug) {
            'sales-manager', 'sales-rep', 'service-rep' => true,
            default => false,
        };
    }

    public function update(User $user, Report $report): bool
    {
        if ($report->is_system) {
            return false;
        }

        return (int) $report->owner_id === (int) $user->id;
    }

    public function delete(User $user, Report $report): bool
    {
        return $this->update($user, $report);
    }

    public function export(User $user, Report $report): bool
    {
        return $this->view($user, $report);
    }
}
