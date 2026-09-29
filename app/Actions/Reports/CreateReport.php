<?php

namespace App\Actions\Reports;

use App\Models\Report;
use App\Models\User;

class CreateReport
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(User $actor, array $attributes): Report
    {
        return Report::query()->create([
            'name' => $attributes['name'],
            'description' => $attributes['description'] ?? null,
            'folder' => $attributes['folder'],
            'object_type' => $attributes['object_type'],
            'columns' => $attributes['columns'],
            'filters' => $attributes['filters'] ?? [],
            'group_by' => $attributes['group_by'] ?? [],
            'chart' => $attributes['chart'] ?? null,
            'is_system' => false,
            'system_key' => null,
            'owner_id' => $actor->id,
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);
    }
}
