<?php

namespace App\Http\Controllers;

use App\Actions\Cases\ChangeCaseOwner;
use App\Actions\Cases\ChangeCaseStatus;
use App\Actions\Cases\CloseCase;
use App\Actions\Cases\CreateCase;
use App\Actions\Cases\DeleteCase;
use App\Actions\Cases\ReopenCase;
use App\Actions\Cases\UpdateCase;
use App\Http\Requests\ChangeCaseOwnerRequest;
use App\Http\Requests\ChangeCaseStatusRequest;
use App\Http\Requests\CloseCaseRequest;
use App\Http\Requests\ReopenCaseRequest;
use App\Http\Requests\StoreCaseRequest;
use App\Http\Requests\UpdateCaseRequest;
use App\Models\Account;
use App\Models\Contact;
use App\Models\SupportCase;
use App\Models\User;
use App\Support\Picklists;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CaseController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', SupportCase::class);

        $user = $request->user();
        $search = trim($request->string('search')->toString());
        $view = $request->string('view')->toString();
        $sort = $request->string('sort')->toString();
        $direction = $request->string('direction')->toString() === 'desc' ? 'desc' : 'asc';
        $perPage = $this->perPage($request);

        if (! in_array($view, ['my_open', 'all_open', 'recently_closed'], true)) {
            $view = 'my_open';
        }

        $sortable = [
            'case_number' => 'case_number',
            'subject' => 'subject',
            'status' => 'status',
            'priority' => 'priority',
            'opened' => 'created_at',
        ];

        if (! array_key_exists($sort, $sortable) && $sort !== 'owner') {
            $sort = 'opened';
            $direction = $request->filled('direction') ? $direction : 'desc';
        }

        $cases = SupportCase::query()
            ->visibleTo($user)
            ->select([
                'id',
                'case_number',
                'subject',
                'status',
                'priority',
                'origin',
                'owner_id',
                'is_closed',
                'closed_at',
                'created_at',
            ])
            ->with(['owner:id,name']);

        $this->applyViewFilter($cases, $view, $user);

        if ($search !== '') {
            $like = $this->like($search);
            $cases->where(function (Builder $query) use ($like): void {
                $query->where('case_number', 'like', $like)
                    ->orWhere('subject', 'like', $like)
                    ->orWhere('status', 'like', $like)
                    ->orWhere('priority', 'like', $like)
                    ->orWhere('origin', 'like', $like);
            });
        }

        if ($sort === 'owner') {
            $cases->orderBy(
                User::query()->select('name')->whereColumn('users.id', 'cases.owner_id'),
                $direction,
            );
        } else {
            $cases->orderBy($sortable[$sort], $direction);
        }

        $cases = $cases->orderBy('id')->paginate($perPage)->withQueryString();

        return Inertia::render('Cases/Index', [
            'cases' => $cases,
            'filters' => [
                'search' => $search,
                'view' => $view,
                'sort' => $sort,
                'direction' => $direction,
                'per_page' => $perPage,
            ],
            'can' => [
                'create' => $user->can('create', SupportCase::class),
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', SupportCase::class);

        $user = $request->user();

        return Inertia::render('Cases/Create', [
            ...$this->formOptions($user),
        ]);
    }

    public function store(StoreCaseRequest $request, CreateCase $create): RedirectResponse
    {
        $this->authorize('create', SupportCase::class);

        $case = $create->handle($request->user(), $request->validated());

        $redirect = $request->boolean('save_and_new')
            ? redirect()->route('cases.create')
            : redirect()->route('cases.show', $case);

        return $redirect->with('success', 'Case saved.');
    }

    public function show(Request $request, SupportCase $case): Response
    {
        $this->authorize('view', $case);

        $user = $request->user();

        $case->load([
            'owner:id,name',
            'createdBy:id,name',
            'updatedBy:id,name',
            'account:id,name',
            'contact:id,first_name,last_name,account_id',
        ]);

        return Inertia::render('Cases/Show', [
            'caseRecord' => $case,
            'statuses' => Picklists::CASE_STATUSES_OPEN,
            'owners' => $this->owners($user),
            'can' => [
                'update' => $user->can('update', $case),
                'delete' => $user->can('delete', $case),
                'changeOwner' => $user->can('changeOwner', $case),
                'changeStatus' => $user->can('changeStatus', $case),
                'close' => $user->can('close', $case),
                'reopen' => $user->can('reopen', $case),
            ],
        ]);
    }

    public function edit(Request $request, SupportCase $case): Response
    {
        $this->authorize('update', $case);

        $user = $request->user();

        $case->load([
            'account:id,name',
            'contact:id,first_name,last_name,account_id',
        ]);

        return Inertia::render('Cases/Edit', [
            'caseRecord' => $case,
            ...$this->formOptions($user),
            'owners' => $this->owners($user),
            'canReassign' => $user->mayReassignOwner(),
        ]);
    }

    public function update(UpdateCaseRequest $request, SupportCase $case, UpdateCase $update): RedirectResponse
    {
        $this->authorize('update', $case);

        $update->handle($request->user(), $case, $request->validated());

        $redirect = $request->boolean('save_and_new')
            ? redirect()->route('cases.create')
            : redirect()->route('cases.show', $case);

        return $redirect->with('success', 'Case saved.');
    }

    public function destroy(SupportCase $case, DeleteCase $delete): RedirectResponse
    {
        $this->authorize('delete', $case);

        $delete->handle($case);

        return redirect()->route('cases.index')->with('success', 'Case deleted.');
    }

    public function changeStatus(
        ChangeCaseStatusRequest $request,
        SupportCase $case,
        ChangeCaseStatus $changeStatus,
    ): RedirectResponse {
        $this->authorize('changeStatus', $case);

        $changeStatus->handle($request->user(), $case, $request->validated('status'));

        return redirect()->route('cases.show', $case)->with('success', 'Case status updated.');
    }

    public function changeOwner(
        ChangeCaseOwnerRequest $request,
        SupportCase $case,
        ChangeCaseOwner $changeOwner,
    ): RedirectResponse {
        $this->authorize('changeOwner', $case);

        $changeOwner->handle($request->user(), $case, $request->validated());

        return redirect()->route('cases.show', $case)->with('success', 'Case owner updated.');
    }

    public function close(
        CloseCaseRequest $request,
        SupportCase $case,
        CloseCase $close,
    ): RedirectResponse {
        $this->authorize('close', $case);

        $close->handle($request->user(), $case);

        return redirect()->route('cases.show', $case)->with('success', 'Case closed.');
    }

    public function reopen(
        ReopenCaseRequest $request,
        SupportCase $case,
        ReopenCase $reopen,
    ): RedirectResponse {
        $this->authorize('reopen', $case);

        $reopen->handle($request->user(), $case);

        return redirect()->route('cases.show', $case)->with('success', 'Case reopened.');
    }

    /**
     * @param  Builder<SupportCase>  $query
     */
    private function applyViewFilter(Builder $query, string $view, User $user): void
    {
        if ($view === 'my_open') {
            $query->where('is_closed', false)->where('owner_id', $user->id);

            return;
        }

        if ($view === 'all_open') {
            $query->where('is_closed', false);

            return;
        }

        $query->where('is_closed', true)
            ->where('closed_at', '>=', now()->subDays(30));
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(User $user): array
    {
        return [
            'statuses' => Picklists::CASE_STATUSES_OPEN,
            'origins' => Picklists::CASE_ORIGINS,
            'priorities' => Picklists::CASE_PRIORITIES,
            'types' => Picklists::CASE_TYPES,
            'reasons' => Picklists::CASE_REASONS,
            'accounts' => $this->accountOptions($user),
            'contacts' => $this->contactOptions($user),
        ];
    }

    /**
     * @return Collection<int, Account>
     */
    private function accountOptions(User $user): Collection
    {
        return Account::query()
            ->visibleTo($user)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * @return Collection<int, Contact>
     */
    private function contactOptions(User $user): Collection
    {
        return Contact::query()
            ->visibleTo($user)
            ->with(['account:id,name'])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'account_id']);
    }

    /**
     * @return Collection<int, User>|list<never>
     */
    private function owners(User $user)
    {
        if (! $user->mayReassignOwner()) {
            return [];
        }

        return User::query()->orderBy('name')->get(['id', 'name']);
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

    private function like(string $search): string
    {
        return '%'.addcslashes($search, '%_\\').'%';
    }
}
