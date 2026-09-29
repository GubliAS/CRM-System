<?php

namespace App\Actions\Accounts;

use App\Mail\OwnerChangedMail;
use App\Models\Account;
use App\Models\User;
use App\Support\OutboundMail;

class UpdateAccount
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(User $actor, Account $account, array $attributes): Account
    {
        $previousOwnerId = $account->owner_id;
        $values = ['updated_by' => $actor->id];

        foreach ([
            'name',
            'parent_account_id',
            'phone',
            'fax',
            'website',
            'type',
            'industry',
            'employees',
            'annual_revenue',
            'billing_street',
            'billing_city',
            'billing_state',
            'billing_postal_code',
            'billing_country',
            'shipping_street',
            'shipping_city',
            'shipping_state',
            'shipping_postal_code',
            'shipping_country',
            'description',
        ] as $key) {
            if (array_key_exists($key, $attributes)) {
                $values[$key] = $attributes[$key];
            }
        }

        if (array_key_exists('owner_id', $attributes) && $actor->mayReassignOwner() && filled($attributes['owner_id'])) {
            $values['owner_id'] = $attributes['owner_id'];
        }

        $account->update($values);
        $account = $account->fresh(['owner']);

        $newOwnerId = (int) ($account->owner_id ?? 0);
        if ($newOwnerId > 0 && $newOwnerId !== (int) $previousOwnerId && $account->owner) {
            OutboundMail::queueAndLog(
                $actor,
                $account->owner,
                $account,
                'Owner change notification sent.',
                OwnerChangedMail::class,
                [$account, $account->owner, $actor, 'account '.$account->name],
            );
        }

        return $account;
    }
}
