<?php

namespace App\Support;

use App\Models\Account;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\SupportCase;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class GlobalSearch
{
    public const SUGGEST_LIMIT = 5;

    /**
     * @var list<string>
     */
    public const OBJECT_TYPES = [
        'leads',
        'accounts',
        'contacts',
        'opportunities',
        'cases',
    ];

    /**
     * @return array<string, list<array<string, mixed>>>
     */
    public function suggest(User $user, string $query): array
    {
        $results = [];

        foreach ($this->searchableTypes($user) as $type) {
            $results[$type] = $this->mapHits(
                $this->queryFor($type, $user, $query)->limit(self::SUGGEST_LIMIT)->get(),
                $type,
            );
        }

        return $results;
    }

    /**
     * @return array{groups: array<string, list<array<string, mixed>>>, totals: array<string, int>}|LengthAwarePaginator<int, array<string, mixed>>
     */
    public function search(User $user, string $query, ?string $type, int $perPage): array|LengthAwarePaginator
    {
        $types = $this->searchableTypes($user);

        if ($type !== null && $type !== '' && $type !== 'all') {
            if (! in_array($type, $types, true)) {
                return new LengthAwarePaginator([], 0, $perPage);
            }

            $paginator = $this->queryFor($type, $user, $query)
                ->paginate($perPage)
                ->withQueryString();

            $mapped = $paginator->getCollection()->map(
                fn (Model $record): array => $this->mapHit($record, $type),
            );

            $paginator->setCollection($mapped);

            return $paginator;
        }

        $groups = [];
        $totals = [];

        foreach ($types as $objectType) {
            $builder = $this->queryFor($objectType, $user, $query);
            $totals[$objectType] = (clone $builder)->count();
            $groups[$objectType] = $this->mapHits(
                $builder->limit($perPage)->get(),
                $objectType,
            );
        }

        return [
            'groups' => $groups,
            'totals' => $totals,
        ];
    }

    /**
     * @return list<string>
     */
    public function searchableTypes(User $user): array
    {
        $map = [
            'leads' => Lead::class,
            'accounts' => Account::class,
            'contacts' => Contact::class,
            'opportunities' => Opportunity::class,
            'cases' => SupportCase::class,
        ];

        $types = [];

        foreach ($map as $type => $model) {
            if ($user->can('viewAny', $model)) {
                $types[] = $type;
            }
        }

        return $types;
    }

    /**
     * @return Builder<Model>
     */
    private function queryFor(string $type, User $user, string $query): Builder
    {
        $like = $this->like($query);

        return match ($type) {
            'leads' => $this->leadsQuery($user, $like),
            'accounts' => $this->accountsQuery($user, $like),
            'contacts' => $this->contactsQuery($user, $like),
            'opportunities' => $this->opportunitiesQuery($user, $like),
            'cases' => $this->casesQuery($user, $like),
            default => Lead::query()->whereRaw('1 = 0'),
        };
    }

    /**
     * @return Builder<Lead>
     */
    private function leadsQuery(User $user, string $like): Builder
    {
        return Lead::query()
            ->visibleTo($user)
            ->select([
                'id',
                'first_name',
                'last_name',
                'company',
                'email',
                'phone',
            ])
            ->where(function (Builder $query) use ($like): void {
                $query->where('first_name', 'like', $like)
                    ->orWhere('last_name', 'like', $like)
                    ->orWhere('company', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('phone', 'like', $like)
                    ->orWhereRaw($this->fullNameSql('leads').' like ?', [$like]);
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->orderBy('id');
    }

    /**
     * @return Builder<Account>
     */
    private function accountsQuery(User $user, string $like): Builder
    {
        return Account::query()
            ->visibleTo($user)
            ->select(['id', 'name', 'phone', 'website'])
            ->where(function (Builder $query) use ($like): void {
                $query->where('name', 'like', $like)
                    ->orWhere('phone', 'like', $like)
                    ->orWhere('website', 'like', $like);
            })
            ->orderBy('name')
            ->orderBy('id');
    }

    /**
     * @return Builder<Contact>
     */
    private function contactsQuery(User $user, string $like): Builder
    {
        return Contact::query()
            ->visibleTo($user)
            ->select([
                'id',
                'account_id',
                'first_name',
                'last_name',
                'email',
                'phone',
            ])
            ->with(['account:id,name'])
            ->where(function (Builder $query) use ($like): void {
                $query->where('first_name', 'like', $like)
                    ->orWhere('last_name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('phone', 'like', $like)
                    ->orWhereRaw($this->fullNameSql('contacts').' like ?', [$like])
                    ->orWhereHas('account', function (Builder $account) use ($like): void {
                        $account->where('name', 'like', $like);
                    });
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->orderBy('id');
    }

    /**
     * @return Builder<Opportunity>
     */
    private function opportunitiesQuery(User $user, string $like): Builder
    {
        return Opportunity::query()
            ->visibleTo($user)
            ->select([
                'id',
                'name',
                'account_id',
                'amount',
            ])
            ->with(['account:id,name'])
            ->where(function (Builder $query) use ($like): void {
                $query->where('name', 'like', $like)
                    ->orWhereRaw($this->amountSql().' like ?', [$like])
                    ->orWhereHas('account', function (Builder $account) use ($like): void {
                        $account->where('name', 'like', $like);
                    });
            })
            ->orderBy('name')
            ->orderBy('id');
    }

    /**
     * @return Builder<SupportCase>
     */
    private function casesQuery(User $user, string $like): Builder
    {
        return SupportCase::query()
            ->visibleTo($user)
            ->select([
                'id',
                'case_number',
                'subject',
                'description',
            ])
            ->where(function (Builder $query) use ($like): void {
                $query->where('case_number', 'like', $like)
                    ->orWhere('subject', 'like', $like)
                    ->orWhere('description', 'like', $like);
            })
            ->orderBy('case_number')
            ->orderBy('id');
    }

    /**
     * @param  Collection<int, Model>  $records
     * @return list<array<string, mixed>>
     */
    private function mapHits(Collection $records, string $type): array
    {
        return $records
            ->map(fn (Model $record): array => $this->mapHit($record, $type))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function mapHit(Model $record, string $type): array
    {
        return match ($type) {
            'leads' => [
                'id' => $record->id,
                'type' => 'leads',
                'label' => 'Lead',
                'title' => trim(($record->first_name ? $record->first_name.' ' : '').$record->last_name),
                'subtitle' => $record->company,
                'url' => route('leads.show', $record),
            ],
            'accounts' => [
                'id' => $record->id,
                'type' => 'accounts',
                'label' => 'Account',
                'title' => $record->name,
                'subtitle' => $record->phone ?: $record->website,
                'url' => route('accounts.show', $record),
            ],
            'contacts' => [
                'id' => $record->id,
                'type' => 'contacts',
                'label' => 'Contact',
                'title' => trim(($record->first_name ? $record->first_name.' ' : '').$record->last_name),
                'subtitle' => $record->account?->name ?: $record->email,
                'url' => route('contacts.show', $record),
            ],
            'opportunities' => [
                'id' => $record->id,
                'type' => 'opportunities',
                'label' => 'Opportunity',
                'title' => $record->name,
                'subtitle' => $record->account?->name,
                'url' => route('opportunities.show', $record),
            ],
            'cases' => [
                'id' => $record->id,
                'type' => 'cases',
                'label' => 'Case',
                'title' => $record->case_number.($record->subject ? ' — '.$record->subject : ''),
                'subtitle' => $record->subject,
                'url' => route('cases.show', $record),
            ],
            default => [
                'id' => $record->id,
                'type' => $type,
                'label' => $type,
                'title' => (string) $record->getKey(),
                'subtitle' => null,
                'url' => null,
            ],
        };
    }

    private function like(string $search): string
    {
        return '%'.addcslashes($search, '%_\\').'%';
    }

    private function fullNameSql(string $table): string
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return "trim(coalesce({$table}.first_name, '') || ' ' || {$table}.last_name)";
        }

        return "trim(concat(coalesce({$table}.first_name, ''), ' ', {$table}.last_name))";
    }

    private function amountSql(): string
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return 'cast(opportunities.amount as text)';
        }

        return 'cast(opportunities.amount as char)';
    }
}
