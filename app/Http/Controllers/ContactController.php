<?php

namespace App\Http\Controllers;

use App\Actions\Contacts\CreateContact;
use App\Actions\Contacts\DeleteContact;
use App\Actions\Contacts\UpdateContact;
use App\Http\Requests\StoreContactRequest;
use App\Http\Requests\UpdateContactRequest;
use App\Models\Account;
use App\Models\Contact;
use App\Models\User;
use App\Support\Picklists;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ContactController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Contact::class);

        $user = $request->user();
        $search = trim($request->string('search')->toString());
        $sort = $request->string('sort')->toString();
        $direction = $request->string('direction')->toString() === 'desc' ? 'desc' : 'asc';
        $perPage = $this->perPage($request);

        $sortable = [
            'title' => 'title',
            'phone' => 'phone',
            'email' => 'email',
        ];

        if (! array_key_exists($sort, $sortable) && ! in_array($sort, ['name', 'account', 'owner'], true)) {
            $sort = 'name';
        }

        $contacts = Contact::query()
            ->visibleTo($user)
            ->select([
                'id',
                'account_id',
                'first_name',
                'last_name',
                'title',
                'phone',
                'email',
                'owner_id',
            ])
            ->with([
                'owner:id,name',
                'account:id,name',
            ]);

        if ($search !== '') {
            $like = $this->like($search);
            $contacts->where(function (Builder $query) use ($like): void {
                $query->where('first_name', 'like', $like)
                    ->orWhere('last_name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('phone', 'like', $like)
                    ->orWhereRaw($this->fullNameSql().' like ?', [$like])
                    ->orWhereHas('account', function (Builder $account) use ($like): void {
                        $account->where('name', 'like', $like);
                    });
            });
        }

        if ($sort === 'name') {
            $contacts->orderBy('last_name', $direction)->orderBy('first_name', $direction);
        } elseif ($sort === 'account') {
            $contacts->orderBy(
                Account::query()->select('name')->whereColumn('accounts.id', 'contacts.account_id'),
                $direction,
            );
        } elseif ($sort === 'owner') {
            $contacts->orderBy(
                User::query()->select('name')->whereColumn('users.id', 'contacts.owner_id'),
                $direction,
            );
        } else {
            $contacts->orderBy($sortable[$sort], $direction);
        }

        $contacts = $contacts->orderBy('id')->paginate($perPage)->withQueryString();

        return Inertia::render('Contacts/Index', [
            'contacts' => $contacts,
            'filters' => [
                'search' => $search,
                'sort' => $sort,
                'direction' => $direction,
                'per_page' => $perPage,
            ],
            'can' => [
                'create' => $user->can('create', Contact::class),
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Contact::class);

        $user = $request->user();
        $requestedAccountId = $request->integer('account_id');
        $selectedAccountId = null;

        if ($requestedAccountId > 0 && Account::query()->visibleTo($user)->whereKey($requestedAccountId)->exists()) {
            $selectedAccountId = $requestedAccountId;
        }

        return Inertia::render('Contacts/Create', [
            'accounts' => $this->accountOptions($user),
            'reportsTo' => $this->reportOptions($user),
            'salutations' => Picklists::SALUTATIONS,
            'selectedAccountId' => $selectedAccountId,
        ]);
    }

    public function store(StoreContactRequest $request, CreateContact $create): RedirectResponse
    {
        $this->authorize('create', Contact::class);

        $contact = $create->handle($request->user(), $request->validated());

        $redirect = $request->boolean('save_and_new')
            ? redirect()->route('contacts.create')
            : redirect()->route('contacts.show', $contact);

        return $redirect->with('success', 'Contact saved.');
    }

    public function show(Request $request, Contact $contact): Response
    {
        $this->authorize('view', $contact);

        $user = $request->user();

        $contact->load([
            'owner:id,name',
            'account:id,name',
            'reportsTo:id,first_name,last_name',
            'createdBy:id,name',
            'updatedBy:id,name',
        ]);

        return Inertia::render('Contacts/Show', [
            'contact' => $contact,
            'can' => [
                'update' => $user->can('update', $contact),
                'delete' => $user->can('delete', $contact),
            ],
        ]);
    }

    public function edit(Request $request, Contact $contact): Response
    {
        $this->authorize('update', $contact);

        $user = $request->user();

        return Inertia::render('Contacts/Edit', [
            'contact' => $contact,
            'accounts' => $this->accountOptions($user),
            'reportsTo' => $this->reportOptions($user, $contact->id),
            'salutations' => Picklists::SALUTATIONS,
            'owners' => $this->owners($user),
            'canReassign' => $user->mayReassignOwner(),
        ]);
    }

    public function update(UpdateContactRequest $request, Contact $contact, UpdateContact $update): RedirectResponse
    {
        $this->authorize('update', $contact);

        $update->handle($request->user(), $contact, $request->validated());

        $redirect = $request->boolean('save_and_new')
            ? redirect()->route('contacts.create')
            : redirect()->route('contacts.show', $contact);

        return $redirect->with('success', 'Contact saved.');
    }

    public function destroy(Contact $contact, DeleteContact $delete): RedirectResponse
    {
        $this->authorize('delete', $contact);

        $delete->handle($contact);

        return redirect()->route('contacts.index')->with('success', 'Contact deleted.');
    }

    /**
     * @return Collection<int, Account>
     */
    private function accountOptions(User $user)
    {
        return Account::query()
            ->visibleTo($user)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * @return Collection<int, Contact>
     */
    private function reportOptions(User $user, ?int $exceptId = null)
    {
        return Contact::query()
            ->visibleTo($user)
            ->when($exceptId, fn (Builder $query) => $query->whereKeyNot($exceptId))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name']);
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
            return "trim(coalesce(contacts.first_name, '') || ' ' || contacts.last_name)";
        }

        return "trim(concat(coalesce(contacts.first_name, ''), ' ', contacts.last_name))";
    }
}
