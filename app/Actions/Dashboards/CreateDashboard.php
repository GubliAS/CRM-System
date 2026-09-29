<?php

namespace App\Actions\Dashboards;

use App\Models\Dashboard;
use App\Models\User;

class CreateDashboard
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(User $actor, array $attributes): Dashboard
    {
        return Dashboard::query()->create([
            'name' => $attributes['name'],
            'description' => $attributes['description'] ?? null,
            'folder' => $attributes['folder'] ?? Dashboard::FOLDER_PRIVATE,
            'widgets' => $attributes['widgets'] ?? [],
            'owner_id' => $actor->id,
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);
    }
}
