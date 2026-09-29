<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Contact;
use App\Models\Event;
use App\Models\Lead;
use App\Models\Note;
use App\Models\Opportunity;
use App\Models\RecentlyViewedRecord;
use App\Models\Role;
use App\Models\SupportCase;
use App\Models\Task;
use App\Models\User;
use App\Support\OpportunityStage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Seeds owned CRM sample data for a developer login (default: richgetrich7@gmail.com).
 */
class DevUserDataSeeder extends Seeder
{
    public function run(): void
    {
        $email = 'richgetrich7@gmail.com';

        $user = User::query()->where('email', $email)->first();

        if (! $user instanceof User) {
            throw new RuntimeException("No user found for [{$email}]. Register or create the account first.");
        }

        $adminRole = Role::query()->where('slug', 'admin')->first();

        if ($adminRole instanceof Role && (int) $user->role_id !== (int) $adminRole->id) {
            $user->forceFill(['role_id' => $adminRole->id])->save();
            $user->refresh();
        }

        DB::transaction(function () use ($user): void {
            $owner = [
                'owner_id' => $user->id,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ];

            $acme = Account::factory()->create([
                'name' => 'Horizon Manufacturing',
                'type' => 'Customer',
                'industry' => 'Manufacturing',
                ...$owner,
            ]);

            $orbit = Account::factory()->create([
                'name' => 'Orbit Retail Group',
                'type' => 'Prospect',
                'industry' => 'Retail',
                ...$owner,
            ]);

            $priya = Contact::factory()->create([
                'account_id' => $acme->id,
                'first_name' => 'Priya',
                'last_name' => 'Nair',
                'title' => 'VP Operations',
                'email' => 'priya.nair@example.com',
                ...$owner,
            ]);

            Contact::factory()->create([
                'account_id' => $acme->id,
                'first_name' => 'Sam',
                'last_name' => 'Okoye',
                'title' => 'Buyer',
                'email' => 'sam.okoye@example.com',
                'reports_to_id' => $priya->id,
                ...$owner,
            ]);

            Contact::factory()->create([
                'account_id' => $orbit->id,
                'first_name' => 'Elena',
                'last_name' => 'Ruiz',
                'title' => 'Procurement Lead',
                'email' => 'elena.ruiz@example.com',
                ...$owner,
            ]);

            $openLead = Lead::factory()->create([
                'first_name' => 'Chris',
                'last_name' => 'Patel',
                'company' => 'Brightline Logistics',
                'email' => 'chris.patel@example.com',
                'lead_status' => 'Working',
                'lead_source' => 'Web',
                ...$owner,
            ]);

            $won = null;

            foreach (array_keys(OpportunityStage::PROBABILITIES) as $stage) {
                $opportunity = Opportunity::factory()->create([
                    'name' => "{$stage} — Horizon Manufacturing",
                    'account_id' => $acme->id,
                    'stage' => $stage,
                    'amount' => match ($stage) {
                        OpportunityStage::QUALIFICATION => 18000,
                        OpportunityStage::MEETING_SCHEDULED => 32000,
                        OpportunityStage::PROPOSAL => 45000,
                        OpportunityStage::NEGOTIATION => 52000,
                        OpportunityStage::CLOSED_WON => 61000,
                        OpportunityStage::CLOSED_LOST => 12000,
                        default => 25000,
                    },
                    'close_date' => match (true) {
                        OpportunityStage::isClosed($stage) => now()->subDays(10)->toDateString(),
                        $stage === OpportunityStage::NEGOTIATION => now()->addDays(7)->toDateString(),
                        default => now()->addMonth()->toDateString(),
                    },
                    'lead_source' => 'Partner',
                    ...$owner,
                ]);

                if ($stage === OpportunityStage::CLOSED_WON) {
                    $won = $opportunity;
                }
            }

            Opportunity::factory()->create([
                'name' => 'Orbit Retail expansion',
                'account_id' => $orbit->id,
                'stage' => OpportunityStage::PROPOSAL,
                'amount' => 28000,
                'close_date' => now()->addDays(21)->toDateString(),
                'lead_source' => 'Web',
                ...$owner,
            ]);

            Lead::factory()->create([
                'first_name' => 'Morgan',
                'last_name' => 'Lee',
                'company' => 'Horizon Manufacturing',
                'email' => 'morgan.lee@example.com',
                'lead_status' => 'Converted',
                'converted' => true,
                'converted_account_id' => $acme->id,
                'converted_contact_id' => $priya->id,
                'converted_opportunity_id' => $won?->id,
                ...$owner,
            ]);

            SupportCase::factory()->create([
                'account_id' => $acme->id,
                'contact_id' => $priya->id,
                'subject' => 'Invoice discrepancy for Horizon Manufacturing',
                'status' => 'New',
                'origin' => 'Email',
                'priority' => 'Medium',
                ...$owner,
            ]);

            SupportCase::factory()->create([
                'account_id' => $orbit->id,
                'subject' => 'Onboarding checklist for Orbit Retail',
                'status' => 'Working',
                'origin' => 'Phone',
                'priority' => 'High',
                ...$owner,
            ]);

            Task::factory()->create([
                'subject' => 'Call Brightline Logistics lead',
                'due_on' => now()->toDateString(),
                'status' => 'Not Started',
                'priority' => 'High',
                'related_type' => Lead::class,
                'related_id' => $openLead->id,
                'assigned_to_id' => $user->id,
                ...$owner,
            ]);

            Task::factory()->create([
                'subject' => 'Send Horizon proposal follow-up',
                'due_on' => now()->addDay()->toDateString(),
                'status' => 'In Progress',
                'related_type' => Account::class,
                'related_id' => $acme->id,
                'contact_id' => $priya->id,
                'assigned_to_id' => $user->id,
                ...$owner,
            ]);

            Event::factory()->create([
                'subject' => 'Horizon Manufacturing discovery call',
                'starts_at' => now()->setTime(14, 0),
                'ends_at' => now()->setTime(15, 0),
                'related_type' => Account::class,
                'related_id' => $acme->id,
                'contact_id' => $priya->id,
                'assigned_to_id' => $user->id,
                ...$owner,
            ]);

            Event::factory()->create([
                'subject' => 'Orbit Retail proposal review',
                'starts_at' => now()->addDay()->setTime(11, 0),
                'ends_at' => now()->addDay()->setTime(12, 0),
                'related_type' => Account::class,
                'related_id' => $orbit->id,
                'assigned_to_id' => $user->id,
                ...$owner,
            ]);

            Note::factory()->create([
                'notable_type' => Account::class,
                'notable_id' => $acme->id,
                'body' => 'Dev seed note: Horizon Manufacturing intro complete.',
                ...$owner,
            ]);

            foreach ([$acme, $orbit] as $account) {
                $this->touchRecentlyViewed($user, Account::class, $account->id);
            }

            if ($won instanceof Opportunity) {
                $this->touchRecentlyViewed($user, Opportunity::class, $won->id);
            }

            $this->touchRecentlyViewed($user, Lead::class, $openLead->id);
            $this->touchRecentlyViewed($user, Contact::class, $priya->id);
        });

        $this->command?->info("Seeded CRM sample data for {$user->email} (id {$user->id}, role admin).");
    }

    private function touchRecentlyViewed(User $user, string $type, int $id): void
    {
        if (! class_exists(RecentlyViewedRecord::class)) {
            return;
        }

        RecentlyViewedRecord::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'viewable_type' => $type,
                'viewable_id' => $id,
            ],
            [
                'viewed_at' => now(),
            ],
        );
    }
}
