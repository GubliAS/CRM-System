<?php

namespace App\Http\Controllers;

use App\Actions\Search\RecordRecentlyViewed;
use App\Actions\Tasks\AssignTask;
use App\Actions\Tasks\CompleteTask;
use App\Actions\Tasks\CreateTask;
use App\Actions\Tasks\DeleteTask;
use App\Actions\Tasks\UpdateTask;
use App\Http\Requests\AssignTaskRequest;
use App\Http\Requests\CompleteTaskRequest;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Models\Task;
use App\Models\User;
use App\Support\Picklists;
use App\Support\RelatedRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TaskController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Task::class);

        $user = $request->user();
        $user->loadMissing('role');
        $view = $request->string('view')->toString();
        $sort = $request->string('sort')->toString();
        $direction = $request->string('direction')->toString() === 'desc' ? 'desc' : 'asc';
        $perPage = $this->perPage($request);

        if (! in_array($view, ['open', 'completed', 'today', 'overdue'], true)) {
            $view = 'open';
        }

        $sortable = [
            'subject' => 'subject',
            'due_on' => 'due_on',
            'status' => 'status',
            'priority' => 'priority',
        ];

        if (! array_key_exists($sort, $sortable)) {
            $sort = 'due_on';
        }

        $tasks = Task::query()
            ->visibleTo($user)
            ->with([
                'related',
                'contact:id,first_name,last_name',
            ]);

        $this->applyView($tasks, $view);

        if ($sort === 'due_on') {
            $tasks->orderByRaw('due_on is null')->orderBy('due_on', $direction);
        } else {
            $tasks->orderBy($sortable[$sort], $direction);
        }

        $tasks = $tasks->orderBy('id')->paginate($perPage)->withQueryString();
        $tasks->setCollection($tasks->getCollection()->map(
            fn (Task $task): array => $this->presentTask($task, $user),
        ));

        return Inertia::render('Tasks/Index', [
            'tasks' => $tasks,
            'filters' => [
                'view' => $view,
                'sort' => $sort,
                'direction' => $direction,
                'per_page' => $perPage,
            ],
            'can' => [
                'create' => $user->can('create', Task::class),
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Task::class);

        return Inertia::render('Tasks/Create', [
            'currentUserId' => $request->user()->id,
            'statuses' => Picklists::TASK_STATUSES,
            'priorities' => Picklists::TASK_PRIORITIES,
            ...RelatedRecords::formPayload($request->user(), RelatedRecords::TASK_TYPES),
        ]);
    }

    public function store(StoreTaskRequest $request, CreateTask $create): RedirectResponse
    {
        $this->authorize('create', Task::class);

        $task = $create->handle($request->user(), $request->validated());

        $redirect = $request->boolean('save_and_new')
            ? redirect()->route('tasks.create')
            : redirect()->route('tasks.show', $task);

        return $redirect->with('success', 'Task saved.');
    }

    public function show(Request $request, Task $task, RecordRecentlyViewed $recordRecentlyViewed): Response
    {
        $this->authorize('view', $task);

        $user = $request->user();
        $recordRecentlyViewed->handle($user, $task);
        $this->loadTask($task);

        return Inertia::render('Tasks/Show', [
            'task' => $this->decorate($task),
            'can' => [
                'update' => $user->can('update', $task),
                'delete' => $user->can('delete', $task),
            ],
        ]);
    }

    public function edit(Request $request, Task $task): Response
    {
        $this->authorize('update', $task);

        $this->loadTask($task);

        return Inertia::render('Tasks/Edit', [
            'task' => $this->decorate($task),
            'statuses' => Picklists::TASK_STATUSES,
            'priorities' => Picklists::TASK_PRIORITIES,
            ...RelatedRecords::formPayload($request->user(), RelatedRecords::TASK_TYPES),
        ]);
    }

    public function update(UpdateTaskRequest $request, Task $task, UpdateTask $update): RedirectResponse
    {
        $this->authorize('update', $task);

        $update->handle($request->user(), $task, $request->validated());

        $redirect = $request->boolean('save_and_new')
            ? redirect()->route('tasks.create')
            : redirect()->route('tasks.show', $task);

        return $redirect->with('success', 'Task saved.');
    }

    public function destroy(Task $task, DeleteTask $delete): RedirectResponse
    {
        $this->authorize('delete', $task);

        $delete->handle($task);

        return redirect()
            ->route('tasks.index')
            ->with('success', 'Task deleted.');
    }

    public function complete(CompleteTaskRequest $request, Task $task, CompleteTask $complete): RedirectResponse
    {
        $this->authorize('update', $task);

        $complete->handle($request->user(), $task);

        return redirect()
            ->back()
            ->with('success', 'Task completed.');
    }

    public function assign(AssignTaskRequest $request, Task $task, AssignTask $assign): RedirectResponse
    {
        $assign->handle($request->user(), $task, $request->validated());

        return redirect()
            ->back()
            ->with('success', 'Task assigned.');
    }

    private function loadTask(Task $task): void
    {
        $task->load([
            'related',
            'contact:id,first_name,last_name',
            'assignedTo:id,name',
            'owner:id,name',
            'createdBy:id,name',
            'updatedBy:id,name',
        ]);
    }

    private function decorate(Task $task): Task
    {
        $task->setAttribute('related_key', RelatedRecords::keyFor($task->related_type, RelatedRecords::TASK_TYPES));
        $task->setAttribute('related_label', RelatedRecords::label($task->related));
        $task->setAttribute('related_href', RelatedRecords::showRoute($task->related));
        $task->setAttribute('contact_label', RelatedRecords::label($task->contact));
        $task->setAttribute('reminder_date', $task->reminder_at?->toDateString() ?? '');
        $task->setAttribute('reminder_time', $task->reminder_at?->format('H:i') ?? '');
        $task->unsetRelation('related');
        $task->unsetRelation('contact');

        return $task;
    }

    /**
     * @param  Builder<Task>  $query
     */
    private function applyView(Builder $query, string $view): void
    {
        $today = now()->toDateString();

        match ($view) {
            'completed' => $query->where('status', 'Completed'),
            'today' => $query->whereDate('due_on', $today),
            'overdue' => $query->whereDate('due_on', '<', $today)->where('status', '!=', 'Completed'),
            default => $query->where('status', '!=', 'Completed'),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function presentTask(Task $task, User $user): array
    {
        $dueOn = $task->due_on?->toDateString();

        return [
            'id' => $task->id,
            'subject' => $task->subject,
            'due_on' => $dueOn,
            'status' => $task->status,
            'priority' => $task->priority,
            'related_label' => RelatedRecords::label($task->related),
            'related_href' => RelatedRecords::showRoute($task->related),
            'contact_name' => RelatedRecords::label($task->contact),
            'completed' => $task->status === 'Completed',
            'can_complete' => $task->status !== 'Completed' && $user->can('update', $task),
            'overdue' => $dueOn !== null && $dueOn < now()->toDateString() && $task->status !== 'Completed',
        ];
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
