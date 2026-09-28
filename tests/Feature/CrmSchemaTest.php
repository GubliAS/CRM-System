<?php

use App\Models\Account;
use App\Models\ActivityLog;
use App\Models\Contact;
use App\Models\Event;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\Role;
use App\Models\SupportCase;
use App\Models\Task;
use App\Models\User;
use App\Support\OpportunityStage;
use Illuminate\Support\Facades\DB;

it('connects an account to its contacts', function () {
    $account = Account::factory()->create(['name' => 'Acme Industries']);
    $contact = Contact::factory()->create([
        'account_id' => $account->id,
        'last_name' => 'Demo',
    ]);

    expect($account->contacts)->toHaveCount(1)
        ->and($account->contacts->first()->is($contact))->toBeTrue()
        ->and($contact->account->is($account))->toBeTrue();
});

it('connects a parent account to its children', function () {
    $parent = Account::factory()->create(['name' => 'Acme Industries']);
    $child = Account::factory()->create([
        'name' => 'Northwind Supplies',
        'parent_account_id' => $parent->id,
    ]);

    expect($child->parent->is($parent))->toBeTrue()
        ->and($parent->children)->toHaveCount(1)
        ->and($parent->children->first()->is($child))->toBeTrue();
});

it('connects a contact to the contact they report to', function () {
    $manager = Contact::factory()->create(['last_name' => 'Demo']);
    $report = Contact::factory()->create([
        'account_id' => $manager->account_id,
        'last_name' => 'Sample',
        'reports_to_id' => $manager->id,
    ]);

    expect($report->reportsTo->is($manager))->toBeTrue()
        ->and($manager->directReports)->toHaveCount(1)
        ->and($manager->directReports->first()->is($report))->toBeTrue();
});

it('belongs a lead to its owner and stores converted foreign keys', function () {
    $owner = User::factory()->create();
    $account = Account::factory()->create(['name' => 'Acme Industries']);
    $contact = Contact::factory()->create(['account_id' => $account->id]);
    $opportunity = Opportunity::factory()->create(['account_id' => $account->id]);

    $lead = Lead::factory()->create([
        'owner_id' => $owner->id,
        'company' => 'Wide World Importers',
        'converted' => true,
        'converted_account_id' => $account->id,
        'converted_contact_id' => $contact->id,
        'converted_opportunity_id' => $opportunity->id,
    ]);

    expect($lead->owner->is($owner))->toBeTrue()
        ->and($lead->convertedAccount->is($account))->toBeTrue()
        ->and($lead->convertedContact->is($contact))->toBeTrue()
        ->and($lead->convertedOpportunity->is($opportunity))->toBeTrue();
});

it('sets qualification probability and open flags', function () {
    $opportunity = Opportunity::factory()->create([
        'stage' => OpportunityStage::QUALIFICATION,
        'probability' => 90,
        'is_closed' => true,
        'is_won' => true,
    ]);

    expect($opportunity->probability)->toBe(10)
        ->and($opportunity->is_closed)->toBeFalse()
        ->and($opportunity->is_won)->toBeFalse();
});

it('sets closed won flags and expected revenue equal to the amount', function () {
    $opportunity = Opportunity::factory()->create([
        'stage' => OpportunityStage::CLOSED_WON,
        'amount' => 1500,
    ]);

    expect($opportunity->probability)->toBe(100)
        ->and($opportunity->is_closed)->toBeTrue()
        ->and($opportunity->is_won)->toBeTrue()
        ->and($opportunity->expected_revenue)->toBe($opportunity->amount);
});

it('sets closed lost flags and zero expected revenue when amount is set', function () {
    $opportunity = Opportunity::factory()->create([
        'stage' => OpportunityStage::CLOSED_LOST,
        'amount' => 1500,
    ]);

    expect($opportunity->probability)->toBe(0)
        ->and($opportunity->is_closed)->toBeTrue()
        ->and($opportunity->is_won)->toBeFalse()
        ->and((float) $opportunity->expected_revenue)->toEqual(0.0);
});

it('updates probability and flags when the stage changes', function () {
    $opportunity = Opportunity::factory()->create([
        'stage' => OpportunityStage::QUALIFICATION,
        'amount' => 800,
    ]);

    $opportunity->update(['stage' => OpportunityStage::MEETING_SCHEDULED]);

    expect($opportunity->probability)->toBe(20)
        ->and($opportunity->is_closed)->toBeFalse()
        ->and($opportunity->is_won)->toBeFalse();

    $opportunity->update(['stage' => OpportunityStage::CLOSED_WON]);

    expect($opportunity->probability)->toBe(100)
        ->and($opportunity->is_closed)->toBeTrue()
        ->and($opportunity->is_won)->toBeTrue()
        ->and($opportunity->expected_revenue)->toBe($opportunity->amount);
});

it('returns null expected revenue when the amount is null', function () {
    $opportunity = Opportunity::factory()->create([
        'stage' => OpportunityStage::CLOSED_WON,
        'amount' => null,
    ]);

    expect($opportunity->expected_revenue)->toBeNull();
});

it('sets and clears case closure fields from the status', function () {
    $case = SupportCase::factory()->create(['status' => 'Working']);

    expect($case->is_closed)->toBeFalse()
        ->and($case->closed_at)->toBeNull();

    $case->update(['status' => 'Closed']);
    $case->refresh();

    expect($case->is_closed)->toBeTrue()
        ->and($case->closed_at)->not->toBeNull();

    $case->update(['status' => 'Escalated']);
    $case->refresh();

    expect($case->is_closed)->toBeFalse()
        ->and($case->closed_at)->toBeNull();
});

it('rejects an event related to a case', function () {
    $case = SupportCase::factory()->create();

    expect(fn () => Event::factory()->create([
        'related_type' => SupportCase::class,
        'related_id' => $case->id,
    ]))->toThrow(InvalidArgumentException::class);
});

it('relates a task to a lead', function () {
    $lead = Lead::factory()->create(['company' => 'Fabrikam Retail']);

    $task = Task::factory()->create([
        'related_type' => Lead::class,
        'related_id' => $lead->id,
    ]);

    expect($task->related)->toBeInstanceOf(Lead::class)
        ->and($task->related->is($lead))->toBeTrue();
});

it('generates a unique case number', function () {
    $numbers = [];

    DB::transaction(function () use (&$numbers) {
        $numbers[] = SupportCase::factory()->create()->case_number;
        $numbers[] = SupportCase::factory()->create()->case_number;
    });

    $first = SupportCase::query()->where('case_number', $numbers[0])->first();
    $second = SupportCase::query()->where('case_number', $numbers[1])->first();

    expect($numbers[0])->not->toBeEmpty()
        ->and($numbers[1])->not->toBeEmpty()
        ->and($numbers[0])->not->toBe($numbers[1])
        ->and($first->case_number)->toBe(SupportCase::numberFromId($first->id))
        ->and($second->case_number)->toBe(SupportCase::numberFromId($second->id));
});

it('seeds roles and fictional rows without deleting existing users', function () {
    $existing = User::factory()->create(['email' => 'kept.user@example.com']);

    $this->seed();
    $this->seed();

    $child = Account::query()->where('name', 'Northwind Supplies')->first();
    $report = Contact::query()->where('last_name', 'Sample')->first();

    expect(Role::query()->count())->toBe(5)
        ->and(Role::query()->where('slug', 'sales-rep')->value('name'))->toBe('Sales Representative')
        ->and(User::query()->whereKey($existing->id)->exists())->toBeTrue()
        ->and(User::query()->where('email', 'avery.admin@example.com')->count())->toBe(1)
        ->and($child->parent->name)->toBe('Acme Industries')
        ->and($report->reportsTo->last_name)->toBe('Demo')
        ->and(Opportunity::query()->whereNotNull('archived_at')->exists())->toBeTrue()
        ->and(SupportCase::query()->whereNotNull('case_number')->count())->toBeGreaterThan(0)
        ->and(ActivityLog::query()->count())->toBe(2)
        ->and(Account::query()->where('name', 'Acme Industries')->count())->toBe(2);
});
