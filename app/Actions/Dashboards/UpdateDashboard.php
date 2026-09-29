<?php

namespace App\Actions\Dashboards;

use App\Models\Dashboard;
use App\Models\User;

class UpdateDashboard
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(User $actor, Dashboard $dashboard, array $attributes): Dashboard
    {
        $dashboard->update([
            'name' => $attributes['name'],
            'description' => $attributes['description'] ?? null,
            'folder' => $attributes['folder'] ?? $dashboard->folder,
            'widgets' => $attributes['widgets'] ?? [],
            'updated_by' => $actor->id,
        ]);

        return $dashboard->fresh();
    }
}
