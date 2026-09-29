<?php

namespace App\Actions\Reports;

use App\Models\Report;
use App\Models\User;
use App\Support\PrebuiltReports;
use Illuminate\Support\Facades\DB;

class SeedPrebuiltReports
{
    public function handle(User $owner): void
    {
        DB::transaction(function () use ($owner): void {
            foreach (PrebuiltReports::definitions() as $definition) {
                Report::query()->updateOrCreate(
                    ['system_key' => $definition['system_key']],
                    [
                        'name' => $definition['name'],
                        'description' => $definition['description'],
                        'folder' => Report::FOLDER_PUBLIC,
                        'object_type' => $definition['object_type'],
                        'columns' => $definition['columns'],
                        'filters' => $definition['filters'],
                        'group_by' => $definition['group_by'],
                        'chart' => $definition['chart'],
                        'is_system' => true,
                        'owner_id' => $owner->id,
                        'created_by' => $owner->id,
                        'updated_by' => $owner->id,
                    ],
                );
            }
        });
    }
}
