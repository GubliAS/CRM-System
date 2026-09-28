<?php

namespace App\Actions\Leads;

use App\Models\Lead;
use App\Models\User;

class CreateLead
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(User $actor, array $attributes): Lead
    {
        return Lead::query()->create([
            ...$this->fields($attributes),
            'converted' => false,
            'owner_id' => $actor->id,
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function fields(array $attributes): array
    {
        return [
            'salutation' => $attributes['salutation'] ?? null,
            'first_name' => $attributes['first_name'] ?? null,
            'last_name' => $attributes['last_name'],
            'company' => $attributes['company'],
            'title' => $attributes['title'] ?? null,
            'email' => $attributes['email'] ?? null,
            'phone' => $attributes['phone'] ?? null,
            'mobile' => $attributes['mobile'] ?? null,
            'lead_status' => $attributes['lead_status'] ?? 'New',
            'lead_source' => $attributes['lead_source'] ?? null,
            'rating' => $attributes['rating'] ?? null,
            'industry' => $attributes['industry'] ?? null,
            'annual_revenue' => $attributes['annual_revenue'] ?? null,
            'number_of_employees' => $attributes['number_of_employees'] ?? null,
            'website' => $attributes['website'] ?? null,
            'street' => $attributes['street'] ?? null,
            'city' => $attributes['city'] ?? null,
            'state' => $attributes['state'] ?? null,
            'postal_code' => $attributes['postal_code'] ?? null,
            'country' => $attributes['country'] ?? null,
            'description' => $attributes['description'] ?? null,
        ];
    }
}
