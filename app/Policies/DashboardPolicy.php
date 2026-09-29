<?php

namespace App\Policies;

use App\Models\Dashboard;
use App\Models\User;
use App\Support\RecordAccess;

class DashboardPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return RecordAccess::before($user);
    }

    public function viewAny(User $user): bool
    {
        return match ($user->role?->slug) {
            'sales-manager', 'sales-rep', 'service-rep', 'read-only' => true,
            default => false,
        };
    }

    public function view(User $user, Dashboard $dashboard): bool
    {
        if ((int) $dashboard->owner_id === (int) $user->id) {
            return true;
        }

        // Shared dashboards are read-only for everyone who may list them; their
        // widgets still run under the viewer's own report permissions.
        return $dashboard->folder === Dashboard::FOLDER_SHARED && $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return match ($user->role?->slug) {
            'sales-manager', 'sales-rep', 'service-rep' => true,
            default => false,
        };
    }

    public function update(User $user, Dashboard $dashboard): bool
    {
        return (int) $dashboard->owner_id === (int) $user->id;
    }

    public function delete(User $user, Dashboard $dashboard): bool
    {
        return $this->update($user, $dashboard);
    }

    public function clone(User $user, Dashboard $dashboard): bool
    {
        return $this->view($user, $dashboard) && $this->create($user);
    }
}
