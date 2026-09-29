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
use App\Support\CsvExporter;
use App\Support\OpportunityStage;
use App\Support\Picklists;
use App\Support\RecentlyViewed;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OpportunityController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Opportunity::class);

        $user = $request->user();
        $stage = $request->string('stage')->toString();
        $year = $request->integer('year');
        $view = $request->string('view')->toString();
        $archived = $request->boolean('archived');
        $perPage = $this->perPage($request);

        if (! in_array($view, ['recent', 'all'], true)) {
            $view = 'recent';
        }

        if ($year < 1970 || $year > 2100) {
            $year = (int) now()->year;
        }

        if ($stage !== '' && ! array_key_exists($stage, OpportunityStage::PROBABILITIES)) {
            $stage = '';
        }

        $opportunities = Opportunity::query()
            ->visibleTo($user)
            ->when(
                $archived,
                fn ($query) => $query->whereNotNull('archived_at'),
                fn ($query) => $query->whereNull('archived_at'),
            )
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
                'archived_at',
            ])
            ->with(['account:id,name', 'owner:id,name']);

        $useRecent = $view === 'recent' && $stage === '' && ! $request->filled('year') && ! $archived;
        $recentIds = $useRecent ? RecentlyViewed::ids($user, Opportunity::class) : [];

        if ($useRecent) {
            RecentlyViewed::constrainToIds($opportunities, Opportunity::class, $recentIds);
        }

        if ($stage !== '') {
            $opportunities->where('stage', $stage);
        }

        if ($request->filled('year')) {
            $opportunities->whereYear('close_date', $year);
        }

        if (! $useRecent) {
            $opportunities
                ->orderBy('close_date')
                ->orderBy('name')
                ->orderBy('id');
        }

        $opportunities = $opportunities
            ->paginate($useRecent ? RecentlyViewed::LIST_LIMIT : $perPage)
            ->withQueryString();

        return Inertia::render('Opportunities/Index', [
            'opportunities' => $opportunities,
            'filters' => [
                'stage' => $stage,
                'year' => $request->filled('year') ? $year : null,
                'view' => $view,
                'archived' => $archived,
                'per_page' => $perPage,
            ],
            'stages' => array_keys(OpportunityStage::PROBABILITIES),
            'can' => [
                'create' => $user->can('create', Opportunity::class),
                'export' => $user->can('viewAny', Opportunity::class),
            ],
        ]);
    }

    public function export(Request $request, CsvExporter $exporter): StreamedResponse
    {
        $this->authorize('viewAny', Opportunity::class);

        $user = $request->user();
        $stage = $request->string('stage')->toString();
        $year = $request->integer('year');
        $view = $request->string('view')->toString();
        $archived = $request->boolean('archived');

        if (! in_array($view, ['recent', 'all'], true)) {
            $view = 'recent';
        }

        if ($year < 1970 || $year > 2100) {
            $year = (int) now()->year;
        }

        if ($stage !== '' && ! array_key_exists($stage, OpportunityStage::PROBABILITIES)) {
            $stage = '';
        }

        $opportunities = Opportunity::query()
            ->visibleTo($user)
            ->when(
                $archived,
                fn ($query) => $query->whereNotNull('archived_at'),
                fn ($query) => $query->whereNull('archived_at'),
            )
            ->with(['account:id,name', 'owner:id,name']);

        $useRecent = $view === 'recent' && $stage === '' && ! $request->filled('year') && ! $archived;
        if ($useRecent) {
            RecentlyViewed::constrainToIds(
                $opportunities,
                Opportunity::class,
                RecentlyViewed::ids($user, Opportunity::class),
            );
        }

        if ($stage !== '') {
            $opportunities->where('stage', $stage);
        }

        if ($request->filled('year')) {
            $opportunities->whereYear('close_date', $year);
        }

        $rows = $opportunities->orderBy('close_date')->orderBy('id')->limit(200)->get()
            ->map(fn (Opportunity $opportunity): array => [
                $opportunity->name,
                $opportunity->account?->name,
                $opportunity->amount,
                optional($opportunity->close_date)?->toDateString(),
                $opportunity->stage,
                $opportunity->probability,
                $opportunity->lead_source,
                $opportunity->owner?->name,
            ]);

        return $exporter->download('opportunities.csv', [
            'Name',
            'Account',
            'Amount',
            'Close date',
            'Stage',
            'Probability',
            'Lead source',
            'Owner',
        ], $rows);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Opportunity::class);

        return Inertia::render('Opportunities/Create', $this->formOptions($request->user()));
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
            'stages' => array_keys(OpportunityStage::PROBABILITIES),
            'owners' => $this->owners($user),
            'can' => [
                'update' => $user->can('update', $opportunity),
                'delete' => $user->can('delete', $opportunity),
                'changeOwner' => $user->can('changeOwner', $opportunity),
                'clone' => $user->can('clone', $opportunity),
            ],
        ]);
    }

    public function edit(Request $request, Opportunity $opportunity): Response
    {
        $this->authorize('update', $opportunity);

        $user = $request->user();

        return Inertia::render('Opportunities/Edit', [
            'opportunity' => $opportunity->load('account:id,name'),
            ...$this->formOptions($user),
            'owners' => $this->owners($user),
            'canReassign' => $user->mayReassignOwner(),
        ]);
    }

    public function update(
        UpdateOpportunityRequest $request,
        Opportunity $opportunity,
        UpdateOpportunity $update,
    ): RedirectResponse {
        $this->authorize('update', $opportunity);

        $update->handle($request->user(), $opportunity, $request->validated());

        $redirect = $request->boolean('save_and_new')
            ? redirect()->route('opportunities.create')
            : redirect()->route('opportunities.show', $opportunity);

        return $redirect->with('success', 'Opportunity saved.');
    }

    public function destroy(
        Request $request,
        Opportunity $opportunity,
        ArchiveOpportunity $archive,
    ): RedirectResponse {
        $this->authorize('delete', $opportunity);

        $archive->handle($request->user(), $opportunity);

        return redirect()->route('opportunities.index')->with('success', 'Opportunity archived.');
    }

    public function clone(
        CloneOpportunityRequest $request,
        Opportunity $opportunity,
        CloneOpportunity $clone,
    ): RedirectResponse {
        $this->authorize('clone', $opportunity);

        $copy = $clone->handle($request->user(), $opportunity, $request->validated());

        return redirect()
            ->route('opportunities.show', $copy)
            ->with('success', 'Opportunity cloned.');
    }

    public function changeOwner(
        ChangeOpportunityOwnerRequest $request,
        Opportunity $opportunity,
        ChangeOpportunityOwner $changeOwner,
    ): RedirectResponse {
        $this->authorize('changeOwner', $opportunity);

        $changeOwner->handle($request->user(), $opportunity, $request->validated());

        return redirect()
            ->route('opportunities.show', $opportunity)
            ->with('success', 'Opportunity owner updated.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(User $user): array
    {
        return [
            'accounts' => Account::query()
                ->visibleTo($user)
                ->orderBy('name')
                ->get(['id', 'name']),
            'stages' => array_keys(OpportunityStage::PROBABILITIES),
            'types' => Picklists::OPPORTUNITY_TYPES,
            'sources' => Picklists::LEAD_SOURCES,
        ];
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
}
