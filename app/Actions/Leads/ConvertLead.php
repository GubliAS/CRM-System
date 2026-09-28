<?php

namespace App\Actions\Leads;

use App\Models\Account;
use App\Models\Contact;
use App\Models\Event;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use LogicException;

class ConvertLead
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(User $actor, Lead $lead, array $attributes): Lead
    {
        if ($lead->converted) {
            throw new LogicException('Lead is already converted.');
        }

        return DB::transaction(function () use ($actor, $lead, $attributes): Lead {
            $account = $this->resolveAccount($actor, $lead, $attributes);
            $contact = $this->createContact($actor, $lead, $account);
            $opportunity = null;

            if ((bool) ($attributes['create_opportunity'] ?? false)) {
                $opportunity = $this->createOpportunity($actor, $lead, $account, $attributes);
            }

            if ((bool) ($attributes['transfer_activities'] ?? false)) {
                $this->transferActivities($actor, $lead, $account, $contact, $opportunity);
            }

            $lead->update([
                'converted' => true,
                'lead_status' => 'Converted',
                'converted_account_id' => $account->id,
                'converted_contact_id' => $contact->id,
                'converted_opportunity_id' => $opportunity?->id,
                'updated_by' => $actor->id,
            ]);

            return $lead->fresh([
                'convertedAccount',
                'convertedContact',
                'convertedOpportunity',
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function resolveAccount(User $actor, Lead $lead, array $attributes): Account
    {
        $mode = $attributes['account_mode'] ?? 'new';

        if ($mode === 'existing') {
            $account = Account::query()
                ->visibleTo($actor)
                ->whereKey($attributes['account_id'])
                ->first();

            if (! $account) {
                throw ValidationException::withMessages([
                    'account_id' => 'The selected account is invalid.',
                ]);
            }

            return $account;
        }

        return Account::query()->create([
            'name' => $lead->company,
            'phone' => $lead->phone,
            'website' => $lead->website,
            'industry' => $lead->industry,
            'annual_revenue' => $lead->annual_revenue,
            'employees' => $lead->number_of_employees,
            'billing_street' => $lead->street,
            'billing_city' => $lead->city,
            'billing_state' => $lead->state,
            'billing_postal_code' => $lead->postal_code,
            'billing_country' => $lead->country,
            'description' => $lead->description,
            'type' => 'Prospect',
            'owner_id' => $lead->owner_id ?? $actor->id,
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);
    }

    private function createContact(User $actor, Lead $lead, Account $account): Contact
    {
        return Contact::query()->create([
            'account_id' => $account->id,
            'salutation' => $lead->salutation,
            'first_name' => $lead->first_name,
            'last_name' => $lead->last_name,
            'title' => $lead->title,
            'email' => $lead->email,
            'phone' => $lead->phone,
            'mobile' => $lead->mobile,
            'mailing_street' => $lead->street,
            'mailing_city' => $lead->city,
            'mailing_state' => $lead->state,
            'mailing_postal_code' => $lead->postal_code,
            'mailing_country' => $lead->country,
            'lead_source' => $lead->lead_source,
            'description' => $lead->description,
            'owner_id' => $lead->owner_id ?? $actor->id,
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createOpportunity(User $actor, Lead $lead, Account $account, array $attributes): Opportunity
    {
        $name = trim((string) ($attributes['opportunity_name'] ?? ''));

        if ($name === '') {
            throw ValidationException::withMessages([
                'opportunity_name' => 'The opportunity name field is required when creating an opportunity.',
            ]);
        }

        return Opportunity::query()->create([
            'name' => $name,
            'account_id' => $account->id,
            'amount' => $attributes['opportunity_amount'] ?? null,
            'close_date' => $attributes['opportunity_close_date'],
            'stage' => $attributes['opportunity_stage'],
            'lead_source' => $lead->lead_source,
            'description' => $lead->description,
            'owner_id' => $lead->owner_id ?? $actor->id,
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);
    }

    private function transferActivities(
        User $actor,
        Lead $lead,
        Account $account,
        Contact $contact,
        ?Opportunity $opportunity,
    ): void {
        $relatedType = $opportunity ? Opportunity::class : Account::class;
        $relatedId = $opportunity ? $opportunity->id : $account->id;

        Task::query()
            ->where('related_type', Lead::class)
            ->where('related_id', $lead->id)
            ->where('status', '!=', 'Completed')
            ->update([
                'related_type' => $relatedType,
                'related_id' => $relatedId,
                'contact_id' => $contact->id,
                'updated_by' => $actor->id,
            ]);

        Event::query()
            ->where('related_type', Lead::class)
            ->where('related_id', $lead->id)
            ->where('ends_at', '>=', now())
            ->update([
                'related_type' => $relatedType,
                'related_id' => $relatedId,
                'contact_id' => $contact->id,
                'updated_by' => $actor->id,
            ]);
    }
}
