<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\ActivityLog;
use App\Models\Contact;
use App\Models\Event;
use App\Models\Lead;
use App\Models\Note;
use App\Models\Opportunity;
use App\Models\Role;
use App\Models\SupportCase;
use App\Models\Task;
use App\Models\User;
use App\Support\OpportunityStage;
use Illuminate\Database\Seeder;

class CrmSeeder extends Seeder
{
    /**
     * Seed reference roles, demo users, and fictional CRM rows.
     */
    public function run(): void
    {
        $roles = $this->seedRoles();
        $users = $this->seedUsers($roles);

        $salesRep = $users['sales-rep'];
        $serviceRep = $users['service-rep'];

        $acme = Account::factory()->create([
            'name' => 'Acme Industries',
            'type' => 'Customer',
            'owner_id' => $salesRep->id,
            'created_by' => $salesRep->id,
            'updated_by' => $salesRep->id,
        ]);

        $northwind = Account::factory()->create([
            'name' => 'Northwind Supplies',
            'parent_account_id' => $acme->id,
            'type' => 'Customer',
            'owner_id' => $salesRep->id,
            'created_by' => $salesRep->id,
            'updated_by' => $salesRep->id,
        ]);

        Account::factory()->create([
            'name' => 'Contoso Logistics',
            'type' => 'Prospect',
            'owner_id' => $users['sales-manager']->id,
            'created_by' => $users['sales-manager']->id,
            'updated_by' => $users['sales-manager']->id,
        ]);

        $jordan = Contact::factory()->create([
            'account_id' => $acme->id,
            'first_name' => 'Jordan',
            'last_name' => 'Demo',
            'title' => 'Purchasing Manager',
            'owner_id' => $salesRep->id,
            'created_by' => $salesRep->id,
            'updated_by' => $salesRep->id,
        ]);

        Contact::factory()->create([
            'account_id' => $acme->id,
            'first_name' => 'Alex',
            'last_name' => 'Sample',
            'title' => 'Buyer',
            'reports_to_id' => $jordan->id,
            'owner_id' => $salesRep->id,
            'created_by' => $salesRep->id,
            'updated_by' => $salesRep->id,
        ]);

        Contact::factory()->create([
            'account_id' => $northwind->id,
            'first_name' => 'Taylor',
            'last_name' => 'Placeholder',
            'owner_id' => $salesRep->id,
            'created_by' => $salesRep->id,
            'updated_by' => $salesRep->id,
        ]);

        $lead = Lead::factory()->create([
            'first_name' => 'Riley',
            'last_name' => 'Example',
            'company' => 'Wide World Importers',
            'lead_status' => 'Working',
            'owner_id' => $salesRep->id,
            'created_by' => $salesRep->id,
            'updated_by' => $salesRep->id,
        ]);

        $won = null;

        foreach (array_keys(OpportunityStage::PROBABILITIES) as $stage) {
            $opportunity = Opportunity::factory()->create([
                'name' => $stage.' for Acme Industries',
                'account_id' => $acme->id,
                'stage' => $stage,
                'amount' => 25000,
                'owner_id' => $salesRep->id,
                'created_by' => $salesRep->id,
                'updated_by' => $salesRep->id,
            ]);

            if ($stage === OpportunityStage::CLOSED_WON) {
                $won = $opportunity;
            }
        }

        Opportunity::factory()->create([
            'name' => 'Archived Northwind Supplies renewal',
            'account_id' => $northwind->id,
            'stage' => OpportunityStage::CLOSED_LOST,
            'amount' => 8000,
            'archived_at' => now(),
            'owner_id' => $salesRep->id,
            'created_by' => $salesRep->id,
            'updated_by' => $salesRep->id,
        ]);

        Lead::factory()->create([
            'first_name' => 'Morgan',
            'last_name' => 'Demo',
            'company' => 'Adventure Works',
            'lead_status' => 'Converted',
            'converted' => true,
            'converted_account_id' => $acme->id,
            'converted_contact_id' => $jordan->id,
            'converted_opportunity_id' => $won?->id,
            'owner_id' => $salesRep->id,
            'created_by' => $salesRep->id,
            'updated_by' => $salesRep->id,
        ]);

        SupportCase::factory()->create([
            'account_id' => $acme->id,
            'contact_id' => $jordan->id,
            'subject' => 'Billing question for Acme Industries',
            'status' => 'New',
            'origin' => 'Phone',
            'owner_id' => $serviceRep->id,
            'created_by' => $serviceRep->id,
            'updated_by' => $serviceRep->id,
        ]);

        SupportCase::factory()->create([
            'account_id' => $northwind->id,
            'subject' => 'Shipment problem for Northwind Supplies',
            'status' => 'Closed',
            'origin' => 'Email',
            'priority' => 'High',
            'type' => 'Problem',
            'owner_id' => $serviceRep->id,
            'created_by' => $serviceRep->id,
            'updated_by' => $serviceRep->id,
        ]);

        Task::factory()->create([
            'subject' => 'Call the Wide World Importers lead',
            'related_type' => Lead::class,
            'related_id' => $lead->id,
            'assigned_to_id' => $salesRep->id,
            'owner_id' => $salesRep->id,
            'created_by' => $salesRep->id,
            'updated_by' => $salesRep->id,
        ]);

        Task::factory()->create([
            'subject' => 'Send the Acme Industries quote',
            'related_type' => Account::class,
            'related_id' => $acme->id,
            'contact_id' => $jordan->id,
            'status' => 'In Progress',
            'assigned_to_id' => $salesRep->id,
            'owner_id' => $salesRep->id,
            'created_by' => $salesRep->id,
            'updated_by' => $salesRep->id,
        ]);

        Event::factory()->create([
            'subject' => 'Acme Industries kickoff',
            'related_type' => Account::class,
            'related_id' => $acme->id,
            'contact_id' => $jordan->id,
            'assigned_to_id' => $salesRep->id,
            'owner_id' => $salesRep->id,
            'created_by' => $salesRep->id,
            'updated_by' => $salesRep->id,
        ]);

        Event::factory()->create([
            'subject' => 'Northwind Supplies check-in',
            'related_type' => Opportunity::class,
            'related_id' => $won?->id,
            'all_day' => true,
            'show_time_as' => 'Free',
            'assigned_to_id' => $salesRep->id,
            'owner_id' => $salesRep->id,
            'created_by' => $salesRep->id,
            'updated_by' => $salesRep->id,
        ]);

        Note::factory()->create([
            'notable_type' => Account::class,
            'notable_id' => $acme->id,
            'body' => 'Intro call with Acme Industries. Fictional seed note.',
            'owner_id' => $salesRep->id,
            'created_by' => $salesRep->id,
            'updated_by' => $salesRep->id,
        ]);

        ActivityLog::factory()->create([
            'subject_type' => Account::class,
            'subject_id' => $acme->id,
            'user_id' => $users['admin']->id,
            'action' => 'created',
            'description' => 'Seeded Acme Industries.',
            'properties' => ['name' => 'Acme Industries'],
        ]);
    }

    /**
     * @return array<string, Role>
     */
    private function seedRoles(): array
    {
        $definitions = [
            ['name' => 'Administrator', 'slug' => 'admin'],
            ['name' => 'Sales Manager', 'slug' => 'sales-manager'],
            ['name' => 'Sales Representative', 'slug' => 'sales-rep'],
            ['name' => 'Service Representative', 'slug' => 'service-rep'],
            ['name' => 'Read-only', 'slug' => 'read-only'],
        ];

        $roles = [];

        foreach ($definitions as $definition) {
            $roles[$definition['slug']] = Role::query()->updateOrCreate(
                ['slug' => $definition['slug']],
                ['name' => $definition['name']],
            );
        }

        return $roles;
    }

    /**
     * @param  array<string, Role>  $roles
     * @return array<string, User>
     */
    private function seedUsers(array $roles): array
    {
        $definitions = [
            'admin' => ['name' => 'Avery Admin', 'email' => 'avery.admin@example.com', 'slug' => 'admin'],
            'sales-manager' => ['name' => 'Morgan Manager', 'email' => 'morgan.manager@example.com', 'slug' => 'sales-manager'],
            'sales-rep' => ['name' => 'Riley Rep', 'email' => 'riley.rep@example.com', 'slug' => 'sales-rep'],
            'service-rep' => ['name' => 'Casey Service', 'email' => 'casey.service@example.com', 'slug' => 'service-rep'],
            'read-only' => ['name' => 'Quinn Reader', 'email' => 'quinn.reader@example.com', 'slug' => 'read-only'],
        ];

        $users = [];

        foreach ($definitions as $key => $definition) {
            $existing = User::query()->where('email', $definition['email'])->first();

            if ($existing instanceof User) {
                $users[$key] = $existing;

                continue;
            }

            $users[$key] = User::factory()->create([
                'name' => $definition['name'],
                'email' => $definition['email'],
                'role_id' => $roles[$definition['slug']]->id,
            ]);
        }

        return $users;
    }
}
