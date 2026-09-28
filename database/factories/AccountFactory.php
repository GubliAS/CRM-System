<?php

namespace Database\Factories;

use App\Models\Account;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Account>
 */
class AccountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->randomElement([
            'Acme Industries',
            'Northwind Supplies',
            'Contoso Logistics',
            'Fabrikam Retail',
            'Adventure Works',
            'Wide World Importers',
        ]);

        return [
            'name' => $name,
            'phone' => fake()->numerify('555-010-####'),
            'fax' => fake()->numerify('555-011-####'),
            'website' => 'https://example.com',
            'type' => fake()->randomElement(['Customer', 'Prospect', 'Partner', 'Other']),
            'industry' => fake()->randomElement(['Manufacturing', 'Wholesale', 'Retail', 'Logistics']),
            'employees' => fake()->numberBetween(10, 500),
            'annual_revenue' => fake()->randomFloat(2, 100000, 5000000),
            'billing_street' => '100 Demo Street',
            'billing_city' => 'Springfield',
            'billing_state' => 'IL',
            'billing_postal_code' => '62701',
            'billing_country' => 'United States',
            'shipping_street' => '100 Demo Street',
            'shipping_city' => 'Springfield',
            'shipping_state' => 'IL',
            'shipping_postal_code' => '62701',
            'shipping_country' => 'United States',
            'description' => 'Demo account for '.$name.'.',
        ];
    }
}
