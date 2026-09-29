<?php

namespace Database\Factories;

use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Report>
 */
class ReportFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $owner = User::factory();

        return [
            'name' => 'Custom lead report',
            'description' => 'Demo custom report.',
            'folder' => Report::FOLDER_PRIVATE,
            'object_type' => Report::OBJECT_LEAD,
            'columns' => ['last_name', 'company', 'lead_source', 'lead_status'],
            'filters' => [],
            'group_by' => [],
            'chart' => null,
            'is_system' => false,
            'system_key' => null,
            'owner_id' => $owner,
            'created_by' => $owner,
            'updated_by' => $owner,
        ];
    }

    public function public(): static
    {
        return $this->state(fn (): array => [
            'folder' => Report::FOLDER_PUBLIC,
        ]);
    }

    public function private(): static
    {
        return $this->state(fn (): array => [
            'folder' => Report::FOLDER_PRIVATE,
        ]);
    }
}
