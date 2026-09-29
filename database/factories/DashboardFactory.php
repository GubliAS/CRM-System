<?php

namespace Database\Factories;

use App\Models\Dashboard;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Dashboard>
 */
class DashboardFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $owner = User::factory();

        return [
            'name' => 'Sales overview',
            'description' => 'Demo dashboard.',
            'folder' => Dashboard::FOLDER_PRIVATE,
            'widgets' => [],
            'owner_id' => $owner,
            'created_by' => $owner,
            'updated_by' => $owner,
        ];
    }
}
