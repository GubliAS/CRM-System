<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Contact;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contact>
 */
class ContactFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'account_id' => Account::factory(),
            'salutation' => fake()->randomElement(['Mr.', 'Ms.', 'Dr.']),
            'first_name' => fake()->randomElement(['Alex', 'Jordan', 'Taylor', 'Riley', 'Morgan']),
            'last_name' => fake()->randomElement(['Demo', 'Sample', 'Placeholder', 'Example']),
            'title' => 'Buyer',
            'department' => 'Purchasing',
            'phone' => fake()->numerify('555-010-####'),
            'mobile' => fake()->numerify('555-012-####'),
            'email' => fake()->unique()->numerify('contact###@example.com'),
            'lead_source' => 'Web',
            'mailing_street' => '100 Demo Street',
            'mailing_city' => 'Springfield',
            'mailing_state' => 'IL',
            'mailing_postal_code' => '62701',
            'mailing_country' => 'United States',
            'description' => 'Demo contact.',
        ];
    }
}
