<?php

namespace App\Actions\Dashboards;

use App\Models\Dashboard;
use App\Models\User;

class CloneDashboard
{
    public function handle(User $actor, Dashboard $dashboard): Dashboard
    {
        return Dashboard::query()->create([
            'name' => 'Copy of '.$dashboard->name,
            'description' => $dashboard->description,
            'folder' => Dashboard::FOLDER_PRIVATE,
            'widgets' => $dashboard->widgets ?? [],
            'owner_id' => $actor->id,
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);
    }
}
