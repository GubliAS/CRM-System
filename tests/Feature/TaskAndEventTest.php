<?php

use App\Models\Event;
use App\Models\Lead;
use App\Models\Role;
use App\Models\SupportCase;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;

function activityUser(string $slug): User
{
    $role = Role::query()->firstOrCreate(
        ['slug' => $slug],
        ['name' => $slug],
    );

    return User::factory()->create([
        'role_id' => $role->id,
    ]);
}

test('marking a task complete from the list sets status to completed', function () {
    $rep = activityUser('sales-rep');
    $other = activityUser('sales-rep');

    $task = Task::factory()->create([
        'owner_id' => $rep->id,
        'assigned_to_id' => $rep->id,
        'status' => 'In Progress',
        'subject' => 'Send the demo notes',
    ]);

    $foreign = Task::factory()->create([
        'owner_id' => $other->id,
        'assigned_to_id' => $other->id,
        'status' => 'Not Started',
        'subject' => 'Someone else follow-up',
    ]);

    $this->actingAs($rep)
        ->from(route('tasks.index'))
        ->post(route('tasks.complete', $task))
        ->assertRedirect(route('tasks.index'));

    expect($task->fresh()->status)->toBe('Completed');

    $this->actingAs($rep)
        ->post(route('tasks.complete', $foreign))
        ->assertForbidden();

    expect($foreign->fresh()->status)->toBe('Not Started');
});

test('a task the user cannot view returns forbidden', function () {
    $rep = activityUser('sales-rep');
    $other = activityUser('sales-rep');

    $task = Task::factory()->create([
        'owner_id' => $other->id,
        'assigned_to_id' => $other->id,
        'subject' => 'Hidden task',
    ]);

    $this->actingAs($rep)
        ->get(route('tasks.show', $task))
        ->assertForbidden();
});

test('a private event is hidden from another user', function () {
    $owner = activityUser('sales-rep');
    $manager = activityUser('sales-manager');
    $starts = now()->addDay()->setTime(10, 0);

    $private = Event::factory()->create([
        'owner_id' => $owner->id,
        'assigned_to_id' => $owner->id,
        'subject' => 'Private pipeline review',
        'is_private' => true,
        'starts_at' => $starts,
        'ends_at' => $starts->copy()->addHour(),
    ]);

    $public = Event::factory()->create([
        'owner_id' => $owner->id,
        'assigned_to_id' => $owner->id,
        'subject' => 'Team standup',
        'is_private' => false,
        'starts_at' => $starts->copy()->addHours(2),
        'ends_at' => $starts->copy()->addHours(3),
    ]);

    $this->actingAs($owner)
        ->get(route('events.show', $private))
        ->assertOk();

    $this->actingAs($manager)
        ->get(route('events.show', $private))
        ->assertForbidden();

    $this->actingAs($manager)
        ->get(route('events.index', [
            'view' => 'day',
            'date' => $starts->toDateString(),
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Events/Calendar')
            ->where('events', function ($events) use ($private, $public) {
                $ids = collect($events)->pluck('id');

                return $ids->doesntContain($private->id)
                    && $ids->contains($public->id);
            }));
});

test('an end time before the start time is rejected', function () {
    $rep = activityUser('sales-rep');

    $this->actingAs($rep)
        ->post(route('events.store'), [
            'subject' => 'Backwards meeting',
            'assigned_to_id' => $rep->id,
            'starts_at' => '2026-10-02T15:00',
            'ends_at' => '2026-10-02T14:00',
            'show_time_as' => 'Busy',
        ])
        ->assertSessionHasErrors('ends_at');

    $this->actingAs($rep)
        ->post(route('events.store'), [
            'subject' => 'Backwards day',
            'assigned_to_id' => $rep->id,
            'all_day' => true,
            'starts_at' => '2026-11-04',
            'ends_at' => '2026-11-03',
            'show_time_as' => 'Busy',
        ])
        ->assertSessionHasErrors('ends_at');

    expect(Event::query()->whereIn('subject', ['Backwards meeting', 'Backwards day'])->exists())->toBeFalse();
});

test('an all-day event may end on its start date', function () {
    $rep = activityUser('sales-rep');

    $this->actingAs($rep)
        ->post(route('events.store'), [
            'subject' => 'All day offsite',
            'assigned_to_id' => $rep->id,
            'all_day' => true,
            'starts_at' => '2026-11-02',
            'ends_at' => '2026-11-02',
            'show_time_as' => 'Out of Office',
        ])
        ->assertRedirect();

    $event = Event::query()->where('subject', 'All day offsite')->first();

    expect($event)->not->toBeNull()
        ->and($event->all_day)->toBeTrue()
        ->and($event->starts_at->toDateString())->toBe('2026-11-02')
        ->and($event->ends_at->toDateString())->toBe('2026-11-02');
});

test('a task can be related to a lead the user owns', function () {
    Mail::fake();

    $rep = activityUser('sales-rep');
    $other = activityUser('sales-rep');
    $lead = Lead::factory()->create([
        'owner_id' => $rep->id,
        'first_name' => 'Alex',
        'last_name' => 'Demo',
        'company' => 'Contoso Logistics',
    ]);
    $foreign = Lead::factory()->create([
        'owner_id' => $other->id,
        'last_name' => 'Other',
    ]);

    $response = $this->actingAs($rep)
        ->post(route('tasks.store'), [
            'subject' => 'Call the owned lead',
            'assigned_to_id' => $rep->id,
            'related_type' => 'lead',
            'related_id' => $lead->id,
            'status' => 'Not Started',
            'priority' => 'High',
            'reminder_set' => true,
            'reminder_date' => '2026-10-10',
            'reminder_time' => '09:30',
        ]);

    $task = Task::query()->where('subject', 'Call the owned lead')->first();

    expect($task)->not->toBeNull()
        ->and($task->related_type)->toBe(Lead::class)
        ->and($task->related_id)->toBe($lead->id)
        ->and($task->reminder_set)->toBeTrue()
        ->and($task->reminder_at?->format('Y-m-d H:i'))->toBe('2026-10-10 09:30');

    $response->assertRedirect(route('tasks.show', $task));

    Mail::assertNothingSent();

    $this->actingAs($rep)
        ->post(route('tasks.store'), [
            'subject' => 'Call someone else lead',
            'assigned_to_id' => $rep->id,
            'related_type' => 'lead',
            'related_id' => $foreign->id,
            'status' => 'Not Started',
        ])
        ->assertSessionHasErrors('related_id');

    expect(Task::query()->where('subject', 'Call someone else lead')->exists())->toBeFalse();
});

test('an event related to a case is rejected', function () {
    $rep = activityUser('sales-rep');
    $case = SupportCase::factory()->create([
        'owner_id' => $rep->id,
        'subject' => 'A case that cannot be an event',
    ]);

    $this->actingAs($rep)
        ->post(route('events.store'), [
            'subject' => 'Case meeting',
            'assigned_to_id' => $rep->id,
            'related_type' => 'case',
            'related_id' => $case->id,
            'starts_at' => '2026-10-06T10:00',
            'ends_at' => '2026-10-06T11:00',
            'show_time_as' => 'Busy',
        ])
        ->assertSessionHasErrors('related_type');

    expect(Event::query()->where('subject', 'Case meeting')->exists())->toBeFalse();
});

test('dragging an event keeps its duration', function () {
    $rep = activityUser('sales-rep');
    $start = now()->addDays(3)->setTime(9, 0);

    $event = Event::factory()->create([
        'owner_id' => $rep->id,
        'assigned_to_id' => $rep->id,
        'starts_at' => $start,
        'ends_at' => $start->copy()->addHour(),
        'all_day' => false,
    ]);

    $newStart = $start->copy()->addDay()->setTime(14, 0);

    $this->actingAs($rep)
        ->from(route('events.index'))
        ->patch(route('events.reschedule', $event), [
            'starts_at' => $newStart->format('Y-m-d H:i:s'),
        ])
        ->assertRedirect();

    $event->refresh();

    expect($event->starts_at->format('Y-m-d H:i'))->toBe($newStart->format('Y-m-d H:i'))
        ->and($event->ends_at->format('Y-m-d H:i'))->toBe($newStart->copy()->addHour()->format('Y-m-d H:i'));
});

test('the calendar loads the neighbouring months so it can navigate without the server', function () {
    $rep = activityUser('sales-rep');
    $anchor = \Carbon\Carbon::parse('2026-10-15 10:00:00');

    $make = fn (string $subject, \Carbon\Carbon $at) => Event::factory()->create([
        'owner_id' => $rep->id,
        'assigned_to_id' => $rep->id,
        'subject' => $subject,
        'is_private' => false,
        'starts_at' => $at,
        'ends_at' => $at->copy()->addHour(),
    ]);

    $september = $make('September event', $anchor->copy()->subMonth());
    $october = $make('October event', $anchor->copy());
    $november = $make('November event', $anchor->copy()->addMonth());
    $december = $make('December event', $anchor->copy()->addMonths(2));

    $this->actingAs($rep)
        ->get(route('events.index', ['view' => 'month', 'date' => '2026-10-15']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.loaded_from', fn ($from) => $from <= '2026-09-01')
            ->where('filters.loaded_to', fn ($to) => $to >= '2026-11-30')
            ->where('events', function ($events) use ($september, $october, $november, $december) {
                $ids = collect($events)->pluck('id');

                return $ids->contains($september->id)
                    && $ids->contains($october->id)
                    && $ids->contains($november->id)
                    && $ids->doesntContain($december->id);
            }));

    // The list view stays scoped to its own month and paginated.
    $this->actingAs($rep)
        ->get(route('events.index', ['view' => 'list', 'date' => '2026-10-15']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.loaded_from', null)
            ->where('list.data', fn ($rows) => collect($rows)->pluck('id')->all() === [$october->id]));
});

test('calendar events say which calendar they belong to and carry what quick edit needs', function () {
    $rep = activityUser('sales-rep');
    $manager = activityUser('sales-manager');
    $starts = now()->addDay()->setTime(9, 0);

    $mine = Event::factory()->create([
        'owner_id' => $rep->id,
        'assigned_to_id' => $rep->id,
        'is_private' => false,
        'starts_at' => $starts,
        'ends_at' => $starts->copy()->addHour(),
    ]);

    $theirs = Event::factory()->create([
        'owner_id' => $rep->id,
        'assigned_to_id' => $rep->id,
        'is_private' => false,
        'starts_at' => $starts->copy()->addHours(3),
        'ends_at' => $starts->copy()->addHours(4),
    ]);

    $this->actingAs($manager)
        ->get(route('events.index', ['view' => 'day', 'date' => $starts->toDateString()]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('events', fn ($events) => collect($events)->every(fn ($event) => $event['is_mine'] === false
                && array_key_exists('assigned_to_id', $event)
                && array_key_exists('related_key', $event)
                && array_key_exists('contact_id', $event))));

    $this->actingAs($rep)
        ->get(route('events.index', ['view' => 'day', 'date' => $starts->toDateString()]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('events', fn ($events) => collect($events)->pluck('is_mine')->every(fn ($flag) => $flag === true)
                && collect($events)->pluck('id')->contains($mine->id)
                && collect($events)->pluck('id')->contains($theirs->id)));
});

test('a quick edit or delete from the calendar goes back to the calendar', function () {
    $rep = activityUser('sales-rep');
    $starts = now()->addDay()->setTime(9, 0);

    $event = Event::factory()->create([
        'owner_id' => $rep->id,
        'assigned_to_id' => $rep->id,
        'subject' => 'Original',
        'is_private' => false,
        'starts_at' => $starts,
        'ends_at' => $starts->copy()->addHour(),
    ]);

    $calendar = route('events.index', ['view' => 'week', 'date' => $starts->toDateString()]);

    $this->actingAs($rep)
        ->from($calendar)
        ->put(route('events.update', $event), [
            'from_calendar' => 1,
            'subject' => 'Renamed in place',
            'assigned_to_id' => $rep->id,
            'starts_at' => $starts->format('Y-m-d\TH:i'),
            'ends_at' => $starts->copy()->addHour()->format('Y-m-d\TH:i'),
            'show_time_as' => 'Busy',
        ])
        ->assertRedirect($calendar);

    expect($event->fresh()->subject)->toBe('Renamed in place');

    $this->actingAs($rep)
        ->from($calendar)
        ->delete(route('events.destroy', $event), ['from_calendar' => 1])
        ->assertRedirect($calendar);

    expect(Event::query()->whereKey($event->id)->exists())->toBeFalse();
});
