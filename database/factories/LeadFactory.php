<?php

namespace Database\Factories;

use App\Models\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lead>
 */
class LeadFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'salutation' => 'Ms.',
            'first_name' => fake()->randomElement(['Alex', 'Jordan', 'Taylor', 'Riley', 'Morgan']),
            'last_name' => fake()->randomElement(['Demo', 'Sample', 'Placeholder', 'Example']),
            'company' => fake()->randomElement([
                'Acme Industries',
                'Northwind Supplies',
                'Contoso Logistics',
                'Fabrikam Retail',
                'Adventure Works',
                'Wide World Importers',
            ]),
            'title' => 'Director',
            'email' => fake()->unique()->numerify('lead###@example.com'),
            'phone' => fake()->numerify('555-010-####'),
            'mobile' => fake()->numerify('555-012-####'),
            'lead_status' => 'New',
            'lead_source' => 'Web',
            'rating' => 'Warm',
            'industry' => 'Wholesale',
            'annual_revenue' => 250000,
            'number_of_employees' => 40,
            'website' => 'https://example.com',
            'street' => '200 Demo Avenue',
            'city' => 'Springfield',
            'state' => 'IL',
            'postal_code' => '62701',
            'country' => 'United States',
            'description' => 'Demo lead.',
            'converted' => false,
        ];
    }
}
