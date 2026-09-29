<?php

namespace App\Actions\Reports;

use App\Models\Report;
use App\Models\User;

class UpdateReport
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(User $actor, Report $report, array $attributes): Report
    {
        $report->fill([
            'name' => $attributes['name'],
            'description' => $attributes['description'] ?? null,
            'folder' => $attributes['folder'],
            'object_type' => $attributes['object_type'],
            'columns' => $attributes['columns'],
            'filters' => $attributes['filters'] ?? [],
            'group_by' => $attributes['group_by'] ?? [],
            'chart' => $attributes['chart'] ?? null,
            'updated_by' => $actor->id,
        ]);
        $report->save();

        return $report->refresh();
    }
}
