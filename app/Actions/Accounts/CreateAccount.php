<?php

namespace App\Actions\Accounts;

use App\Models\Account;
use App\Models\User;

class CreateAccount
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(User $actor, array $attributes): Account
    {
        return Account::query()->create([
            ...$this->fields($attributes),
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
            'name' => $attributes['name'],
            'parent_account_id' => $attributes['parent_account_id'] ?? null,
            'phone' => $attributes['phone'] ?? null,
            'fax' => $attributes['fax'] ?? null,
            'website' => $attributes['website'] ?? null,
            'type' => $attributes['type'] ?? null,
            'industry' => $attributes['industry'] ?? null,
            'employees' => $attributes['employees'] ?? null,
            'annual_revenue' => $attributes['annual_revenue'] ?? null,
            'billing_street' => $attributes['billing_street'] ?? null,
            'billing_city' => $attributes['billing_city'] ?? null,
            'billing_state' => $attributes['billing_state'] ?? null,
            'billing_postal_code' => $attributes['billing_postal_code'] ?? null,
            'billing_country' => $attributes['billing_country'] ?? null,
            'shipping_street' => $attributes['shipping_street'] ?? null,
            'shipping_city' => $attributes['shipping_city'] ?? null,
            'shipping_state' => $attributes['shipping_state'] ?? null,
            'shipping_postal_code' => $attributes['shipping_postal_code'] ?? null,
            'shipping_country' => $attributes['shipping_country'] ?? null,
            'description' => $attributes['description'] ?? null,
        ];
    }
}
