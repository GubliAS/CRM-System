<?php

namespace App\Http\Controllers;

use App\Actions\Opportunities\ArchiveOpportunity;
use App\Actions\Opportunities\ChangeOpportunityOwner;
use App\Actions\Opportunities\CloneOpportunity;
use App\Actions\Opportunities\CreateOpportunity;
use App\Actions\Opportunities\UpdateOpportunity;
use App\Actions\Search\RecordRecentlyViewed;
use App\Http\Requests\ChangeOpportunityOwnerRequest;
use App\Http\Requests\CloneOpportunityRequest;
use App\Http\Requests\StoreOpportunityRequest;
use App\Http\Requests\UpdateOpportunityRequest;
use App\Models\Account;
use App\Models\Opportunity;
use App\Models\User;
use App\Support\OpportunityStage;
use App\Support\Picklists;
use App\Support\RecentlyViewed;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OpportunityController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Opportunity::class);

        $user = $request->user();
        $search = trim($request->string('search')->toString());
        $sort = $request->string('sort')->toString();
        $direction = $request->string('direction')->toString() === 'desc' ? 'desc' : 'asc';
        $showArchived = $request->boolean('show_archived');
        $stage = $request->string('stage')->toString();
        $year = $request->integer('year');
        $view = $request->string('view')->toString();
        $perPage = $this->perPage($request);

        $sortable = [
            'name' => 'name',
            'amount' => 'amount',
            'close_date' => 'close_date',
            'stage' => 'stage',
            'probability' => 'probability',
        ];

        if (! array_key_exists($sort, $sortable) && ! in_array($sort, ['account', 'owner'], true)) {
            $sort = 'name';
        }

        if (! in_array($view, ['recent', 'all'], true)) {
            $view = 'recent';
        }

        if ($year < 1970 || $year > 2100) {
            $year = (int) now()->year;
        }

        if ($stage !== '' && ! array_key_exists($stage, OpportunityStage::PROBABILITIES)) {
            $stage = '';
        }

        $useRecent = $view === 'recent'
            && $search === ''
            && $stage === ''
            && ! $request->filled('year')
            && ! $showArchived;
        $recentIds = $useRecent ? RecentlyViewed::ids($user, Opportunity::class) : [];

        $opportunities = Opportunity::query()
            ->visibleTo($user)
            ->select([
                'id',
                'name',
                'account_id',
                'amount',
                'close_date',
                'stage',
                'probability',
                'lead_source',
                'owner_id',
            ])
            ->with([
                'account:id,name',
                'owner:id,name',
            ]);

        if (! $showArchived) {
            $opportunities->whereNull('archived_at');
        }

        if ($useRecent) {
            RecentlyViewed::constrainToIds($opportunities, Opportunity::class, $recentIds);
        }

        if ($stage !== '') {
            $opportunities->where('stage', $stage);
        }

        if ($request->filled('year')) {
            $opportunities->whereYear('close_date', $year);
        }

        if ($search !== '') {
            $like = $this->like($search);
            $opportunities->where(function (Builder $query) use ($like): void {
                $query->where('name', 'like', $like)
                    ->orWhereHas('account', function (Builder $account) use ($like): void {
                        $account->where('name', 'like', $like);
                    });
            });
        }

        if (! $useRecent) {
            if ($sort === 'account') {
                $opportunities->orderBy(
                    Account::query()->select('name')->whereColumn('accounts.id', 'opportunities.account_id'),
                    $direction,
                );
            } elseif ($sort === 'owner') {
                $opportunities->orderBy(
                    User::query()->select('name')->whereColumn('users.id', 'opportunities.owner_id'),
                    $direction,
                );
            } elseif ($request->filled('sort') || $request->filled('direction')) {
                $opportunities->orderBy($sortable[$sort], $direction);
            } else {
                $opportunities
                    ->orderBy('close_date')
                    ->orderBy('name')
                    ->orderBy('id');
            }
        }

        $opportunities = $opportunities
            ->paginate($useRecent ? RecentlyViewed::LIST_LIMIT : $perPage)
            ->withQueryString();

        return Inertia::render('Opportunities/Index', [
            'opportunities' => $opportunities,
            'filters' => [
                'search' => $search,
                'sort' => $sort,
                'direction' => $direction,
                'per_page' => $perPage,
                'show_archived' => $showArchived,
                'stage' => $stage,
                'year' => $request->filled('year') ? $year : null,
                'view' => $view,
            ],
            'stages' => array_keys(OpportunityStage::PROBABILITIES),
            'can' => [
                'create' => $user->can('create', Opportunity::class),
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Opportunity::class);

        return Inertia::render('Opportunities/Create', [
            'accounts' => $this->accounts($request->user()),
            'stages' => OpportunityStage::options(),
            'types' => Picklists::OPPORTUNITY_TYPES,
            'leadSources' => Picklists::LEAD_SOURCES,
        ]);
    }

    public function store(StoreOpportunityRequest $request, CreateOpportunity $create): RedirectResponse
    {
        $this->authorize('create', Opportunity::class);

        $opportunity = $create->handle($request->user(), $request->validated());

        $redirect = $request->boolean('save_and_new')
            ? redirect()->route('opportunities.create')
            : redirect()->route('opportunities.show', $opportunity);

        return $redirect->with('success', 'Opportunity saved.');
    }

    public function show(
        Request $request,
        Opportunity $opportunity,
        RecordRecentlyViewed $recentlyViewed,
    ): Response {
        $this->authorize('view', $opportunity);

        $user = $request->user();

        $opportunity->load([
            'account:id,name',
            'owner:id,name',
            'createdBy:id,name',
            'updatedBy:id,name',
            'stageHistories.user:id,name',
        ]);

        $recentlyViewed->handle($user, $opportunity);

        return Inertia::render('Opportunities/Show', [
            'opportunity' => $opportunity,
            'stagePath' => $this->stagePath($opportunity->stage),
            'closeDateInPast' => $opportunity->close_date->toDateString() < now()->toDateString(),
            'owners' => $user->can('update', $opportunity)
                ? User::query()->orderBy('name')->get(['id', 'name'])
                : [],
            'can' => [
                'update' => $user->can('update', $opportunity),
                'delete' => $user->can('delete', $opportunity),
                'clone' => $user->can('create', Opportunity::class),
            ],
        ]);
    }

    public function edit(Request $request, Opportunity $opportunity): Response
    {
        $this->authorize('update', $opportunity);

        return Inertia::render('Opportunities/Edit', [
            'opportunity' => $opportunity,
            'accounts' => $this->accounts($request->user()),
            'stages' => OpportunityStage::options(),
            'types' => Picklists::OPPORTUNITY_TYPES,
            'leadSources' => Picklists::LEAD_SOURCES,
        ]);
    }

    public function update(UpdateOpportunityRequest $request, Opportunity $opportunity, UpdateOpportunity $update): RedirectResponse
    {
        $this->authorize('update', $opportunity);

        $update->handle($request->user(), $opportunity, $request->validated());

        $redirect = $request->boolean('save_and_new')
            ? redirect()->route('opportunities.create')
            : redirect()->route('opportunities.show', $opportunity);

        return $redirect->with('success', 'Opportunity saved.');
    }

    public function destroy(Request $request, Opportunity $opportunity, ArchiveOpportunity $archive): RedirectResponse
    {
        $this->authorize('delete', $opportunity);

        $archive->handle($request->user(), $opportunity);

        return redirect()
            ->route('opportunities.show', $opportunity)
            ->with('success', 'Opportunity archived.');
    }

    public function changeOwner(ChangeOpportunityOwnerRequest $request, Opportunity $opportunity, ChangeOpportunityOwner $changeOwner): RedirectResponse
    {
        $this->authorize('update', $opportunity);

        $changeOwner->handle($request->user(), $opportunity, (int) $request->validated('owner_id'));

        return redirect()
            ->route('opportunities.show', $opportunity)
            ->with('success', 'Owner updated.');
    }

    public function storeClone(CloneOpportunityRequest $request, Opportunity $opportunity, CloneOpportunity $clone): RedirectResponse
    {
        $this->authorize('view', $opportunity);
        $this->authorize('create', Opportunity::class);

        $copy = $clone->handle($request->user(), $opportunity, $request->validated());

        return redirect()
            ->route('opportunities.show', $copy)
            ->with('success', 'Opportunity cloned.');
    }

    /**
     * @return Collection<int, Account>
     */
    private function accounts(User $user): Collection
    {
        return Account::query()
            ->visibleTo($user)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * @return list<array{name: string, state: string, emphasis: string}>
     */
    private function stagePath(string $stage): array
    {
        $terminal = $stage === OpportunityStage::CLOSED_LOST
            ? OpportunityStage::CLOSED_LOST
            : OpportunityStage::CLOSED_WON;

        $names = [
            OpportunityStage::QUALIFICATION,
            OpportunityStage::MEETING_SCHEDULED,
            OpportunityStage::PROPOSAL,
            OpportunityStage::NEGOTIATION,
            $terminal,
        ];

        $currentIndex = array_search($stage, $names, true);

        if ($currentIndex === false) {
            $currentIndex = 0;
        }

        $steps = [];

        foreach ($names as $index => $name) {
            if ($index < $currentIndex) {
                $state = 'completed';
                $emphasis = 'success';
            } elseif ($index === $currentIndex) {
                $state = 'current';
                $emphasis = $name === OpportunityStage::CLOSED_LOST ? 'danger' : 'primary';
            } else {
                $state = 'future';
                $emphasis = 'muted';
            }

            $steps[] = [
                'name' => $name,
                'state' => $state,
                'emphasis' => $emphasis,
            ];
        }

        return $steps;
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
