<?php

namespace App\Http\Controllers;

use App\Actions\Leads\ChangeLeadOwner;
use App\Actions\Leads\ChangeLeadStatus;
use App\Actions\Leads\ConvertLead;
use App\Actions\Leads\CreateLead;
use App\Actions\Leads\DeleteLead;
use App\Actions\Leads\UpdateLead;
use App\Actions\Search\RecordRecentlyViewed;
use App\Http\Requests\ChangeLeadOwnerRequest;
use App\Http\Requests\ChangeLeadStatusRequest;
use App\Http\Requests\ConvertLeadRequest;
use App\Http\Requests\StoreLeadRequest;
use App\Http\Requests\UpdateLeadRequest;
use App\Models\Account;
use App\Models\Lead;
use App\Models\User;
use App\Support\OpportunityStage;
use App\Support\Picklists;
use App\Support\RecentlyViewed;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class LeadController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Lead::class);

        $user = $request->user();
        $search = trim($request->string('search')->toString());
        $view = $request->string('view')->toString();
        $sort = $request->string('sort')->toString();
        $direction = $request->string('direction')->toString() === 'desc' ? 'desc' : 'asc';
        $perPage = $this->perPage($request);

        if (! in_array($view, ['recent', 'all'], true)) {
            $view = 'recent';
        }

        $sortable = [
            'company' => 'company',
            'title' => 'title',
            'phone' => 'phone',
            'email' => 'email',
            'lead_source' => 'lead_source',
            'lead_status' => 'lead_status',
        ];

        if (! array_key_exists($sort, $sortable) && ! in_array($sort, ['name', 'owner'], true)) {
            $sort = 'name';
        }

        $leads = Lead::query()
            ->visibleTo($user)
            ->select([
                'id',
                'first_name',
                'last_name',
                'company',
                'title',
                'phone',
                'email',
                'lead_source',
                'lead_status',
                'owner_id',
                'converted',
            ])
            ->with(['owner:id,name']);

        $useRecent = $view === 'recent' && $search === '';
        $recentIds = $useRecent ? RecentlyViewed::ids($user, Lead::class) : [];

        if ($useRecent) {
            RecentlyViewed::constrainToIds($leads, Lead::class, $recentIds);
        }

        if ($search !== '') {
            $like = $this->like($search);
            $leads->where(function (Builder $query) use ($like): void {
                $query->where('first_name', 'like', $like)
                    ->orWhere('last_name', 'like', $like)
                    ->orWhere('company', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('phone', 'like', $like)
                    ->orWhereRaw($this->fullNameSql().' like ?', [$like]);
            });
        }

        if (! $useRecent) {
            if ($sort === 'name') {
                $leads->orderBy('last_name', $direction)->orderBy('first_name', $direction);
            } elseif ($sort === 'owner') {
                $leads->orderBy(
                    User::query()->select('name')->whereColumn('users.id', 'leads.owner_id'),
                    $direction,
                );
            } else {
                $leads->orderBy($sortable[$sort], $direction);
            }

            $leads->orderBy('id');
        }

        $leads = $leads->paginate($useRecent ? RecentlyViewed::LIST_LIMIT : $perPage)->withQueryString();

        return Inertia::render('Leads/Index', [
            'leads' => $leads,
            'filters' => [
                'search' => $search,
                'view' => $view,
                'sort' => $sort,
                'direction' => $direction,
                'per_page' => $perPage,
            ],
            'can' => [
                'create' => $user->can('create', Lead::class),
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Lead::class);

        return Inertia::render('Leads/Create', $this->formOptions());
    }

    public function store(StoreLeadRequest $request, CreateLead $create): RedirectResponse
    {
        $this->authorize('create', Lead::class);

        $lead = $create->handle($request->user(), $request->validated());

        $redirect = $request->boolean('save_and_new')
            ? redirect()->route('leads.create')
            : redirect()->route('leads.show', $lead);

        return $redirect->with('success', 'Lead saved.');
    }

    public function show(Request $request, Lead $lead, RecordRecentlyViewed $recordRecentlyViewed): Response
    {
        $this->authorize('view', $lead);

        $user = $request->user();
        $recordRecentlyViewed->handle($user, $lead);

        $lead->load([
            'owner:id,name',
            'createdBy:id,name',
            'updatedBy:id,name',
            'convertedAccount:id,name',
            'convertedContact:id,first_name,last_name',
            'convertedOpportunity:id,name',
        ]);

        $matchingAccounts = Account::query()
            ->visibleTo($user)
            ->where('name', $lead->company)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Leads/Show', [
            'lead' => $lead,
            'matchingAccounts' => $matchingAccounts,
            'statuses' => Picklists::LEAD_STATUSES_EDITABLE,
            'owners' => $this->owners($user),
            'opportunityStages' => array_keys(OpportunityStage::PROBABILITIES),
            'can' => [
                'update' => $user->can('update', $lead),
                'delete' => $user->can('delete', $lead),
                'convert' => $user->can('convert', $lead),
                'changeOwner' => $user->can('changeOwner', $lead),
                'changeStatus' => $user->can('changeStatus', $lead),
            ],
        ]);
    }

    public function edit(Request $request, Lead $lead): Response
    {
        $this->authorize('update', $lead);

        $user = $request->user();

        return Inertia::render('Leads/Edit', [
            'lead' => $lead,
            ...$this->formOptions(),
            'owners' => $this->owners($user),
            'canReassign' => $user->mayReassignOwner(),
        ]);
    }

    public function update(UpdateLeadRequest $request, Lead $lead, UpdateLead $update): RedirectResponse
    {
        $this->authorize('update', $lead);

        $update->handle($request->user(), $lead, $request->validated());

        $redirect = $request->boolean('save_and_new')
            ? redirect()->route('leads.create')
            : redirect()->route('leads.show', $lead);

        return $redirect->with('success', 'Lead saved.');
    }

    public function destroy(Lead $lead, DeleteLead $delete): RedirectResponse
    {
        $this->authorize('delete', $lead);

        $delete->handle($lead);

        return redirect()->route('leads.index')->with('success', 'Lead deleted.');
    }

    public function changeStatus(
        ChangeLeadStatusRequest $request,
        Lead $lead,
        ChangeLeadStatus $changeStatus,
    ): RedirectResponse {
        $this->authorize('changeStatus', $lead);

        $changeStatus->handle($request->user(), $lead, $request->validated('lead_status'));

        return redirect()->route('leads.show', $lead)->with('success', 'Lead status updated.');
    }

    public function changeOwner(
        ChangeLeadOwnerRequest $request,
        Lead $lead,
        ChangeLeadOwner $changeOwner,
    ): RedirectResponse {
        $this->authorize('changeOwner', $lead);

        $changeOwner->handle($request->user(), $lead, $request->validated());

        return redirect()->route('leads.show', $lead)->with('success', 'Lead owner updated.');
    }

    public function convert(
        ConvertLeadRequest $request,
        Lead $lead,
        ConvertLead $convert,
    ): RedirectResponse {
        $this->authorize('convert', $lead);

        $convert->handle($request->user(), $lead, $request->validated());

        return redirect()->route('leads.show', $lead)->with('success', 'Lead converted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'salutations' => Picklists::SALUTATIONS,
            'statuses' => Picklists::LEAD_STATUSES_EDITABLE,
            'sources' => Picklists::LEAD_SOURCES,
            'ratings' => Picklists::LEAD_RATINGS,
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

    private function like(string $search): string
    {
        return '%'.addcslashes($search, '%_\\').'%';
    }

    private function fullNameSql(): string
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return "trim(coalesce(leads.first_name, '') || ' ' || leads.last_name)";
        }

        return "trim(concat(coalesce(leads.first_name, ''), ' ', leads.last_name))";
    }
}
