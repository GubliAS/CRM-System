<?php

namespace App\Http\Controllers;

use App\Actions\Events\CreateEvent;
use App\Actions\Events\DeleteEvent;
use App\Actions\Events\RescheduleEvent;
use App\Actions\Events\UpdateEvent;
use App\Actions\Search\RecordRecentlyViewed;
use App\Http\Requests\RescheduleEventRequest;
use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Models\Event;
use App\Models\User;
use App\Support\Picklists;
use App\Support\RelatedRecords;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class EventController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Event::class);

        $user = $request->user();
        $user->loadMissing('role');
        $view = $request->string('view')->toString();
        $perPage = $this->perPage($request);

        if (! in_array($view, ['day', 'week', 'month', 'list'], true)) {
            $view = 'month';
        }

        $anchor = $this->anchor($request);
        [$rangeStart, $rangeEnd] = $this->range($view, $anchor);

        $events = Event::query()
            ->visibleTo($user)
            ->with([
                'related',
                'contact:id,first_name,last_name',
                'assignedTo:id,name',
            ])
            ->where('starts_at', '<', $rangeEnd)
            ->where('ends_at', '>', $rangeStart)
            ->orderByDesc('all_day')
            ->orderBy('starts_at')
            ->orderBy('id');

        $list = null;

        if ($view === 'list') {
            $list = $events->paginate($perPage)->withQueryString();
            $list->setCollection($list->getCollection()->map(
                fn (Event $event): array => $this->presentEvent($event, $user),
            ));
            $payload = [];
        } else {
            $payload = $events->get()->map(
                fn (Event $event): array => $this->presentEvent($event, $user),
            )->values();
        }

        return Inertia::render('Events/Calendar', [
            'events' => $payload,
            'list' => $list,
            'filters' => [
                'view' => $view,
                'date' => $anchor->toDateString(),
                'per_page' => $perPage,
            ],
            'title' => $this->title($view, $anchor, $rangeStart, $rangeEnd),
            'today' => now()->toDateString(),
            'days' => $this->days($view, $anchor, $rangeStart, $rangeEnd),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Event::class);

        return Inertia::render('Events/Create', [
            'currentUserId' => $request->user()->id,
            'defaults' => $this->createDefaults($request),
            'showTimeAs' => Picklists::EVENT_SHOW_TIME_AS,
            ...RelatedRecords::formPayload($request->user(), RelatedRecords::EVENT_TYPES),
        ]);
    }

    public function store(StoreEventRequest $request, CreateEvent $create): RedirectResponse
    {
        $this->authorize('create', Event::class);

        $event = $create->handle($request->user(), $request->validated());

        $redirect = $request->boolean('save_and_new')
            ? redirect()->route('events.create')
            : redirect()->route('events.show', $event);

        return $redirect->with('success', 'Event saved.');
    }

    public function show(Request $request, Event $event, RecordRecentlyViewed $recordRecentlyViewed): Response
    {
        $this->authorize('view', $event);

        $user = $request->user();
        $recordRecentlyViewed->handle($user, $event);
        $this->loadEvent($event);

        return Inertia::render('Events/Show', [
            'event' => $this->decorate($event),
            'can' => [
                'update' => $user->can('update', $event),
                'delete' => $user->can('delete', $event),
            ],
        ]);
    }

    public function edit(Request $request, Event $event): Response
    {
        $this->authorize('update', $event);

        $this->loadEvent($event);

        return Inertia::render('Events/Edit', [
            'event' => $this->decorate($event),
            'showTimeAs' => Picklists::EVENT_SHOW_TIME_AS,
            ...RelatedRecords::formPayload($request->user(), RelatedRecords::EVENT_TYPES),
        ]);
    }

    public function update(UpdateEventRequest $request, Event $event, UpdateEvent $update): RedirectResponse
    {
        $this->authorize('update', $event);

        $update->handle($request->user(), $event, $request->validated());

        $redirect = $request->boolean('save_and_new')
            ? redirect()->route('events.create')
            : redirect()->route('events.show', $event);

        return $redirect->with('success', 'Event saved.');
    }

    public function destroy(Event $event, DeleteEvent $delete): RedirectResponse
    {
        $this->authorize('delete', $event);

        $delete->handle($event);

        return redirect()
            ->route('events.index')
            ->with('success', 'Event deleted.');
    }

    public function reschedule(RescheduleEventRequest $request, Event $event, RescheduleEvent $reschedule): RedirectResponse
    {
        $this->authorize('update', $event);

        $reschedule->handle($request->user(), $event, $request->validated('starts_at'));

        return redirect()
            ->back()
            ->with('success', 'Event updated.');
    }

    private function loadEvent(Event $event): void
    {
        $event->load([
            'related',
            'contact:id,first_name,last_name',
            'assignedTo:id,name',
            'owner:id,name',
            'createdBy:id,name',
            'updatedBy:id,name',
        ]);
    }

    private function decorate(Event $event): Event
    {
        $event->setAttribute('related_key', RelatedRecords::keyFor($event->related_type, RelatedRecords::EVENT_TYPES));
        $event->setAttribute('related_label', RelatedRecords::label($event->related));
        $event->setAttribute('related_href', RelatedRecords::showRoute($event->related));
        $event->setAttribute('contact_label', RelatedRecords::label($event->contact));
        $event->setAttribute('starts_input', $event->all_day
            ? $event->starts_at->toDateString()
            : $event->starts_at->format('Y-m-d\TH:i'));
        $event->setAttribute('ends_input', $event->all_day
            ? $event->ends_at->toDateString()
            : $event->ends_at->format('Y-m-d\TH:i'));
        $event->unsetRelation('related');
        $event->unsetRelation('contact');

        return $event;
    }

    /**
     * @return array<string, mixed>
     */
    private function presentEvent(Event $event, User $user): array
    {
        return [
            'id' => $event->id,
            'subject' => $event->subject,
            'starts_at' => $event->starts_at->format('Y-m-d H:i:s'),
            'ends_at' => $event->ends_at->format('Y-m-d H:i:s'),
            'all_day' => $event->all_day,
            'location' => $event->location,
            'show_time_as' => $event->show_time_as,
            'is_private' => $event->is_private,
            'description' => $event->description,
            'related_label' => RelatedRecords::label($event->related),
            'related_href' => RelatedRecords::showRoute($event->related),
            'contact_name' => RelatedRecords::label($event->contact),
            'assignee' => $event->assignedTo?->name,
            'can_update' => $user->can('update', $event),
            'can_delete' => $user->can('delete', $event),
        ];
    }

    /**
     * @return array{all_day: bool, starts_at: string, ends_at: string}
     */
    private function createDefaults(Request $request): array
    {
        $allDay = $request->boolean('all_day');
        $raw = trim($request->string('starts_at')->toString());

        try {
            $start = $raw !== '' ? Carbon::parse($raw) : now()->addHour()->minute(0)->second(0);
        } catch (Throwable) {
            $start = now()->addHour()->minute(0)->second(0);
            $allDay = false;
        }

        if ($allDay) {
            return [
                'all_day' => true,
                'starts_at' => $start->toDateString(),
                'ends_at' => $start->toDateString(),
            ];
        }

        return [
            'all_day' => false,
            'starts_at' => $start->format('Y-m-d\TH:i'),
            'ends_at' => $start->copy()->addHour()->format('Y-m-d\TH:i'),
        ];
    }

    private function anchor(Request $request): Carbon
    {
        $value = trim($request->string('date')->toString());

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            return now()->startOfDay();
        }

        try {
            return Carbon::parse($value)->startOfDay();
        } catch (Throwable) {
            return now()->startOfDay();
        }
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function range(string $view, Carbon $anchor): array
    {
        return match ($view) {
            'day' => [$anchor->copy()->startOfDay(), $anchor->copy()->endOfDay()],
            'week' => [
                $anchor->copy()->startOfWeek(Carbon::SUNDAY),
                $anchor->copy()->endOfWeek(Carbon::SUNDAY),
            ],
            'list' => [$anchor->copy()->startOfMonth(), $anchor->copy()->endOfMonth()],
            default => [
                $anchor->copy()->startOfMonth()->startOfWeek(Carbon::SUNDAY),
                $anchor->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY),
            ],
        };
    }

    /**
     * @return list<array{date: string, in_month: bool, day: string, weekday: string}>
     */
    private function days(string $view, Carbon $anchor, Carbon $start, Carbon $end): array
    {
        if ($view === 'list') {
            return [];
        }

        $cursor = $start->copy()->startOfDay();
        $last = $end->copy()->startOfDay();
        $days = [];

        while ($cursor->lte($last)) {
            $days[] = [
                'date' => $cursor->toDateString(),
                'in_month' => $view !== 'month' || $cursor->month === $anchor->month,
                'day' => $cursor->format('j'),
                'weekday' => $cursor->format('D'),
            ];
            $cursor->addDay();
        }

        return $days;
    }

    private function title(string $view, Carbon $anchor, Carbon $start, Carbon $end): string
    {
        return match ($view) {
            'day' => $anchor->format('F j, Y'),
            'week' => $start->format('M j, Y').' – '.$end->format('M j, Y'),
            'list' => $anchor->format('F Y'),
            default => $anchor->format('F Y'),
        };
    }

    private function perPage(Request $request): int
    {
        $value = $request->query('per_page', 25);

        if (! is_numeric($value)) {
            return 25;
        }

        $perPage = (int) $value;

        if ($perPage < 1) {
            return 25;
        }

        return min($perPage, 200);
    }
}
