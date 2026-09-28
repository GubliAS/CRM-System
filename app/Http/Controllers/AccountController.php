<?php

namespace App\Http\Controllers;

use App\Actions\Accounts\CreateAccount;
use App\Actions\Accounts\DeleteAccount;
use App\Actions\Accounts\UpdateAccount;
use App\Actions\Search\RecordRecentlyViewed;
use App\Http\Requests\StoreAccountRequest;
use App\Http\Requests\UpdateAccountRequest;
use App\Models\Account;
use App\Models\Contact;
use App\Models\User;
use App\Support\Picklists;
use App\Support\RecentlyViewed;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AccountController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Account::class);

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
            'name' => 'name',
            'phone' => 'phone',
            'type' => 'type',
            'industry' => 'industry',
            'annual_revenue' => 'annual_revenue',
        ];

        if (! array_key_exists($sort, $sortable) && $sort !== 'owner') {
            $sort = 'name';
        }

        $accounts = Account::query()
            ->visibleTo($user)
            ->select([
                'id',
                'name',
                'phone',
                'type',
                'industry',
                'annual_revenue',
                'owner_id',
            ])
            ->with(['owner:id,name']);

        $useRecent = $view === 'recent' && $search === '';
        $recentIds = $useRecent ? RecentlyViewed::ids($user, Account::class) : [];

        if ($useRecent) {
            RecentlyViewed::constrainToIds($accounts, Account::class, $recentIds);
        }

        if ($search !== '') {
            $like = $this->like($search);
            $accounts->where(function (Builder $query) use ($like): void {
                $query->where('name', 'like', $like)
                    ->orWhere('phone', 'like', $like)
                    ->orWhere('industry', 'like', $like);
            });
        }

        if (! $useRecent) {
            if ($sort === 'owner') {
                $accounts->orderBy(
                    User::query()->select('name')->whereColumn('users.id', 'accounts.owner_id'),
                    $direction,
                );
            } else {
                $accounts->orderBy($sortable[$sort], $direction);
            }

            $accounts->orderBy('id');
        }

        $accounts = $accounts->paginate($useRecent ? RecentlyViewed::LIST_LIMIT : $perPage)->withQueryString();

        return Inertia::render('Accounts/Index', [
            'accounts' => $accounts,
            'filters' => [
                'search' => $search,
                'view' => $view,
                'sort' => $sort,
                'direction' => $direction,
                'per_page' => $perPage,
            ],
            'can' => [
                'create' => $user->can('create', Account::class),
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Account::class);

        return Inertia::render('Accounts/Create', [
            'parentAccounts' => $this->parentAccounts($request->user()),
            'types' => Picklists::ACCOUNT_TYPES,
        ]);
    }

    public function store(StoreAccountRequest $request, CreateAccount $create): RedirectResponse
    {
        $this->authorize('create', Account::class);

        $account = $create->handle($request->user(), $request->validated());

        $redirect = $request->boolean('save_and_new')
            ? redirect()->route('accounts.create')
            : redirect()->route('accounts.show', $account);

        return $redirect->with('success', 'Account saved.');
    }

    public function show(Request $request, Account $account, RecordRecentlyViewed $recordRecentlyViewed): Response
    {
        $this->authorize('view', $account);

        $user = $request->user();
        $recordRecentlyViewed->handle($user, $account);

        $account->load([
            'owner:id,name',
            'parent:id,name',
            'createdBy:id,name',
            'updatedBy:id,name',
            'contacts' => function ($query) use ($user): void {
                $query->visibleTo($user)
                    ->select([
                        'id',
                        'account_id',
                        'first_name',
                        'last_name',
                        'title',
                        'email',
                        'phone',
                    ])
                    ->orderBy('last_name')
                    ->orderBy('first_name');
            },
        ]);

        return Inertia::render('Accounts/Show', [
            'account' => $account,
            'can' => [
                'update' => $user->can('update', $account),
                'delete' => $user->can('delete', $account),
                'createContact' => $user->can('create', Contact::class),
            ],
        ]);
    }

    public function edit(Request $request, Account $account): Response
    {
        $this->authorize('update', $account);

        $user = $request->user();

        return Inertia::render('Accounts/Edit', [
            'account' => $account,
            'parentAccounts' => $this->parentAccounts($user, $account->id),
            'types' => Picklists::ACCOUNT_TYPES,
            'owners' => $this->owners($user),
            'canReassign' => $user->mayReassignOwner(),
        ]);
    }

    public function update(UpdateAccountRequest $request, Account $account, UpdateAccount $update): RedirectResponse
    {
        $this->authorize('update', $account);

        $update->handle($request->user(), $account, $request->validated());

        $redirect = $request->boolean('save_and_new')
            ? redirect()->route('accounts.create')
            : redirect()->route('accounts.show', $account);

        return $redirect->with('success', 'Account saved.');
    }

    public function destroy(Account $account, DeleteAccount $delete): RedirectResponse
    {
        $this->authorize('delete', $account);

        $delete->handle($account);

        return redirect()->route('accounts.index')->with('success', 'Account deleted.');
    }

    /**
     * @return Collection<int, Account>
     */
    private function parentAccounts(User $user, ?int $exceptId = null)
    {
        return Account::query()
            ->visibleTo($user)
            ->when($exceptId, fn (Builder $query) => $query->whereKeyNot($exceptId))
            ->orderBy('name')
            ->get(['id', 'name']);
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
