<?php

namespace App\Actions\Cases;

use App\Models\SupportCase;
use App\Models\User;

class CreateCase
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(User $actor, array $attributes): SupportCase
    {
        return SupportCase::query()->create([
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
            'contact_id' => $attributes['contact_id'] ?? null,
            'account_id' => $attributes['account_id'] ?? null,
            'subject' => $attributes['subject'] ?? null,
            'description' => $attributes['description'] ?? null,
            'internal_comments' => $attributes['internal_comments'] ?? null,
            'status' => $attributes['status'] ?? 'New',
            'priority' => $attributes['priority'] ?? null,
            'type' => $attributes['type'] ?? null,
            'origin' => $attributes['origin'],
            'reason' => $attributes['reason'] ?? null,
            'web_email' => $attributes['web_email'] ?? null,
            'web_name' => $attributes['web_name'] ?? null,
            'web_company' => $attributes['web_company'] ?? null,
            'web_phone' => $attributes['web_phone'] ?? null,
        ];
    }
}
