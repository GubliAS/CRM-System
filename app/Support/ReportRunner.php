<?php

namespace App\Support;

use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ReportRunner
{
    public const DEFAULT_PER_PAGE = 25;

    public const MAX_PER_PAGE = 200;

    /**
     * @param  array{
     *     columns?: list<string>,
     *     filters?: list<array{field: string, operator: string, value?: mixed}>,
     *     group_by?: list<string>,
     *     chart?: array<string, mixed>|null,
     *     summary?: bool,
     *     object_type?: string
     * }  $definition
     * @param  array{sort?: string, direction?: string, page?: int, per_page?: int, global_filters?: array{date_from?: string|null, date_to?: string|null, owner_id?: int|null}}  $options
     * @return array{
     *     columns: list<array{key: string, label: string}>,
     *     rows: list<array<string, mixed>>,
     *     total: int,
     *     per_page: int,
     *     current_page: int,
     *     last_page: int,
     *     from: int|null,
     *     to: int|null,
     *     chart: array{type: string, points: list<array{label: string, value: float|int}>}|null,
     *     grouped: bool
     * }
     */
    public function run(User $user, string $objectType, array $definition, array $options = []): array
    {
        if (! ReportObjects::userCanAccessObject($user, $objectType)) {
            abort(403);
        }

        $columns = array_values($definition['columns'] ?? []);
        $filters = array_values($definition['filters'] ?? []);
        $groupBy = array_values($definition['group_by'] ?? []);
        $summary = (bool) ($definition['summary'] ?? false);
        $chart = $definition['chart'] ?? null;
        $fields = ReportObjects::fields($objectType);

        if (! $summary && $groupBy === []) {
            foreach ($columns as $column) {
                if (isset($fields[$column]['aggregate'])) {
                    $summary = true;
                    break;
                }
            }
        }

        if ($columns === []) {
            throw new InvalidArgumentException('A report needs at least one column.');
        }

        $perPage = $this->perPage($options['per_page'] ?? null);
        $page = max(1, (int) ($options['page'] ?? 1));
        $sort = (string) ($options['sort'] ?? '');
        $direction = (($options['direction'] ?? 'asc') === 'desc') ? 'desc' : 'asc';

        $query = $this->baseQuery($user, $objectType);
        $this->applyFilters($query, $objectType, $filters);
        $this->applyGlobalFilters($query, $options['global_filters'] ?? []);

        if ($summary || $groupBy !== []) {
            return $this->runAggregated(
                $query,
                $objectType,
                $columns,
                $groupBy,
                $summary,
                $chart,
                $sort,
                $direction,
                $page,
                $perPage,
            );
        }

        return $this->runDetail(
            $query,
            $objectType,
            $columns,
            $chart,
            $sort,
            $direction,
            $page,
            $perPage,
        );
    }

    /**
     * @param  array{sort?: string, direction?: string, page?: int, per_page?: int, global_filters?: array{date_from?: string|null, date_to?: string|null, owner_id?: int|null}}  $options
     * @return array{columns: list<array{key: string, label: string}>, rows: list<array<string, mixed>>, total: int, per_page: int, current_page: int, last_page: int, from: int|null, to: int|null, chart: array{type: string, points: list<array{label: string, value: float|int}>}|null, grouped: bool}
     */
    public function runReport(Report $report, User $user, array $options = []): array
    {
        return $this->run($user, $report->object_type, [
            'columns' => $report->columns ?? [],
            'filters' => $report->filters ?? [],
            'group_by' => $report->group_by ?? [],
            'chart' => $report->chart,
            'summary' => $this->isSummaryReport($report),
        ], $options);
    }

    public function isSummaryReport(Report $report): bool
    {
        $groupBy = $report->group_by ?? [];

        if ($groupBy !== []) {
            return false;
        }

        foreach ($report->columns ?? [] as $column) {
            $meta = ReportObjects::fields($report->object_type)[$column] ?? null;
            if ($meta && isset($meta['aggregate'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return Builder<Model>
     */
    private function baseQuery(User $user, string $objectType): Builder
    {
        $modelClass = ReportObjects::modelClass($objectType);

        /** @var Builder<Model> $query */
        $query = $modelClass::query()->visibleTo($user);

        return $query;
    }

    /**
     * @param  Builder<Model>  $query
     * @param  array{date_from?: string|null, date_to?: string|null, owner_id?: int|null}  $globalFilters
     */
    private function applyGlobalFilters(Builder $query, array $globalFilters): void
    {
        $table = $query->getModel()->getTable();

        if (! empty($globalFilters['owner_id'])) {
            $query->where($table.'.owner_id', (int) $globalFilters['owner_id']);
        }

        if (! empty($globalFilters['date_from'])) {
            $query->whereDate($table.'.created_at', '>=', $globalFilters['date_from']);
        }

        if (! empty($globalFilters['date_to'])) {
            $query->whereDate($table.'.created_at', '<=', $globalFilters['date_to']);
        }
    }

    /**
     * @param  Builder<Model>  $query
     * @param  list<array{field: string, operator: string, value?: mixed}>  $filters
     */
    private function applyFilters(Builder $query, string $objectType, array $filters): void
    {
        $table = $query->getModel()->getTable();

        foreach ($filters as $filter) {
            $field = (string) ($filter['field'] ?? '');
            $operator = (string) ($filter['operator'] ?? '');
            $value = $filter['value'] ?? null;

            if ($field === '' || $operator === '') {
                continue;
            }

            if ($field === 'archived_at' && $operator === 'is_null') {
                $query->whereNull($table.'.archived_at');

                continue;
            }

            if ($operator === 'this_fy') {
                [$start, $end] = $this->fiscalYearBounds();
                $column = $this->physicalColumn($objectType, $field, $table);
                $query->whereBetween($column, [$start, $end]);

                continue;
            }

            if ($operator === 'is_true') {
                $query->where($this->physicalColumn($objectType, $field, $table), true);

                continue;
            }

            if ($operator === 'is_false') {
                $query->where($this->physicalColumn($objectType, $field, $table), false);

                continue;
            }

            if ($operator === 'is_null') {
                $query->whereNull($this->physicalColumn($objectType, $field, $table));

                continue;
            }

            $column = $this->physicalColumn($objectType, $field, $table);

            match ($operator) {
                'equals' => $query->where($column, '=', $value),
                'not_equals' => $query->where($column, '!=', $value),
                'contains' => $query->where($column, 'like', '%'.$this->escapeLike((string) $value).'%'),
                'gt' => $query->where($column, '>', $value),
                'gte' => $query->where($column, '>=', $value),
                'lt' => $query->where($column, '<', $value),
                'lte' => $query->where($column, '<=', $value),
                default => null,
            };
        }
    }

    /**
     * @param  Builder<Model>  $query
     * @param  list<string>  $columns
     * @param  array<string, mixed>|null  $chart
     * @return array{columns: list<array{key: string, label: string}>, rows: list<array<string, mixed>>, total: int, per_page: int, current_page: int, last_page: int, from: int|null, to: int|null, chart: array{type: string, points: list<array{label: string, value: float|int}>}|null, grouped: bool}
     */
    private function runDetail(
        Builder $query,
        string $objectType,
        array $columns,
        ?array $chart,
        string $sort,
        string $direction,
        int $page,
        int $perPage,
    ): array {
        $table = $query->getModel()->getTable();
        $fields = ReportObjects::fields($objectType);
        $displayColumns = array_values(array_filter(
            $columns,
            fn (string $key): bool => isset($fields[$key]) && ! isset($fields[$key]['aggregate']),
        ));

        if ($displayColumns === []) {
            $displayColumns = ['id'];
        }

        $needsOwner = in_array('owner_name', $displayColumns, true) || $sort === 'owner_name';
        $needsAccount = in_array('account_name', $displayColumns, true) || $sort === 'account_name';

        if ($needsOwner) {
            $query->with(['owner:id,name']);
        }

        if ($needsAccount && $objectType === Report::OBJECT_OPPORTUNITY) {
            $query->with(['account:id,name']);
        }

        $sortable = [];
        foreach ($displayColumns as $key) {
            if (in_array($key, ['owner_name', 'account_name'], true)) {
                $sortable[$key] = $key;
            } elseif (! ($fields[$key]['computed'] ?? false)) {
                $sortable[$key] = $this->physicalColumn($objectType, $key, $table);
            }
        }

        if ($sort !== '' && array_key_exists($sort, $sortable)) {
            if ($sort === 'owner_name') {
                $query->orderBy(
                    User::query()->select('name')->whereColumn('users.id', $table.'.owner_id'),
                    $direction,
                );
            } elseif ($sort === 'account_name') {
                $query->orderBy(
                    DB::table('accounts')->select('name')->whereColumn('accounts.id', $table.'.account_id'),
                    $direction,
                );
            } else {
                $query->orderBy($sortable[$sort], $direction);
            }
        } else {
            $query->orderBy($table.'.id');
        }

        $query->orderBy($table.'.id');

        /** @var LengthAwarePaginator<int, Model> $paginator */
        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        $rows = collect($paginator->items())
            ->map(function (Model $record) use ($objectType, $displayColumns): array {
                $row = [
                    'record_id' => $record->getKey(),
                    'record_url' => ReportObjects::recordUrl($objectType, $record->getKey()),
                ];

                foreach ($displayColumns as $key) {
                    $row[$key] = $this->cellValue($record, $key);
                }

                return $row;
            })
            ->all();

        return [
            'columns' => $this->columnHeaders($objectType, $displayColumns),
            'rows' => $rows,
            'total' => $paginator->total(),
            'per_page' => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
            'chart' => $this->chartFromRows($chart, $rows),
            'grouped' => false,
        ];
    }

    /**
     * @param  Builder<Model>  $query
     * @param  list<string>  $columns
     * @param  list<string>  $groupBy
     * @param  array<string, mixed>|null  $chart
     * @return array{columns: list<array{key: string, label: string}>, rows: list<array<string, mixed>>, total: int, per_page: int, current_page: int, last_page: int, from: int|null, to: int|null, chart: array{type: string, points: list<array{label: string, value: float|int}>}|null, grouped: bool}
     */
    private function runAggregated(
        Builder $query,
        string $objectType,
        array $columns,
        array $groupBy,
        bool $summary,
        ?array $chart,
        string $sort,
        string $direction,
        int $page,
        int $perPage,
    ): array {
        $table = $query->getModel()->getTable();
        $fields = ReportObjects::fields($objectType);
        $selects = [];
        $groups = [];

        foreach ($groupBy as $field) {
            $expression = $this->groupExpression($objectType, $field, $table);
            $selects[] = DB::raw($expression.' as '.$field);
            $groups[] = DB::raw($expression);
        }

        foreach ($columns as $column) {
            if (in_array($column, $groupBy, true)) {
                continue;
            }

            $meta = $fields[$column] ?? null;
            if (! $meta || ! isset($meta['aggregate'])) {
                continue;
            }

            $selects[] = DB::raw($this->aggregateExpression($objectType, $meta['aggregate'], $table).' as '.$column);
        }

        if ($selects === []) {
            $selects[] = DB::raw('count(*) as record_count');
            if (! in_array('record_count', $columns, true)) {
                $columns[] = 'record_count';
            }
        }

        $query->select($selects);

        if ($groups !== []) {
            $query->groupBy($groups);
        }

        $allRows = $query->get();
        $mapped = $allRows->map(function ($row) use ($columns, $groupBy): array {
            $data = [];
            foreach (array_unique(array_merge($groupBy, $columns)) as $key) {
                $value = $row->{$key} ?? null;
                if ($key === 'conversion_rate' || $key === 'avg_amount' || $key === 'avg_deal_length' || $key === 'avg_age_days') {
                    $data[$key] = $value === null ? null : round((float) $value, 2);
                } elseif ($key === 'total_amount' || $key === 'expected_revenue') {
                    $data[$key] = $value === null ? null : round((float) $value, 2);
                } elseif ($key === 'record_count') {
                    $data[$key] = (int) $value;
                } elseif ($value === null || $value === '') {
                    $data[$key] = '(None)';
                } else {
                    $data[$key] = is_bool($value) ? ($value ? 'Yes' : 'No') : $value;
                }
            }
            $data['record_id'] = null;
            $data['record_url'] = null;

            return $data;
        });

        $sortKey = $sort !== '' && $mapped->isNotEmpty() && array_key_exists($sort, $mapped->first())
            ? $sort
            : ($groupBy[0] ?? ($columns[0] ?? null));

        if ($sortKey) {
            $mapped = $mapped->sortBy(
                fn (array $row) => $row[$sortKey] ?? null,
                SORT_NATURAL | SORT_FLAG_CASE,
                $direction === 'desc',
            )->values();
        }

        $total = $mapped->count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = min($page, $lastPage);
        $slice = $mapped->forPage($page, $perPage)->values();
        $from = $total === 0 ? null : (($page - 1) * $perPage) + 1;
        $to = $total === 0 ? null : min($page * $perPage, $total);

        $displayColumns = array_values(array_unique(array_merge($groupBy, $columns)));

        return [
            'columns' => $this->columnHeaders($objectType, $displayColumns),
            'rows' => $slice->all(),
            'total' => $summary && $total === 0 ? 0 : ($summary ? (int) ($mapped->first()['record_count'] ?? $total) : $total),
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => $lastPage,
            'from' => $from,
            'to' => $to,
            'chart' => $this->chartFromRows($chart, $mapped->all()),
            'grouped' => true,
        ];
    }

    private function aggregateExpression(string $objectType, string $aggregate, string $table): string
    {
        return match ($aggregate) {
            'count' => 'count(*)',
            'sum:amount' => 'coalesce(sum('.$table.'.amount), 0)',
            'avg:amount' => 'coalesce(avg('.$table.'.amount), 0)',
            'sum_expected' => 'coalesce(sum(('.$table.'.amount * '.$table.'.probability) / 100), 0)',
            'avg_deal_length' => $this->avgDealLengthExpression($table),
            'avg_case_age' => $this->avgCaseAgeExpression($table),
            'conversion' => 'round(100.0 * sum(case when '.$table.'.converted = 1 then 1 else 0 end) / nullif(count(*), 0), 2)',
            default => 'count(*)',
        };
    }

    private function avgDealLengthExpression(string $table): string
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            return 'coalesce(avg(julianday('.$table.'.close_date) - julianday('.$table.'.created_at)), 0)';
        }

        return 'coalesce(avg(timestampdiff(day, '.$table.'.created_at, '.$table.'.close_date)), 0)';
    }

    private function avgCaseAgeExpression(string $table): string
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            return 'coalesce(avg(julianday(coalesce('.$table.'.closed_at, current_timestamp)) - julianday('.$table.'.created_at)), 0)';
        }

        return 'coalesce(avg(timestampdiff(day, '.$table.'.created_at, coalesce('.$table.'.closed_at, now()))), 0)';
    }

    private function groupExpression(string $objectType, string $field, string $table): string
    {
        if ($field === 'owner_name') {
            return '(select name from users where users.id = '.$table.'.owner_id)';
        }

        if ($field === 'account_name') {
            return '(select name from accounts where accounts.id = '.$table.'.account_id)';
        }

        if ($field === 'created_month') {
            $driver = DB::connection()->getDriverName();

            if ($driver === 'sqlite') {
                return "strftime('%Y-%m', ".$table.'.created_at)';
            }

            return 'date_format('.$table.".created_at, '%Y-%m')";
        }

        return $table.'.'.$field;
    }

    private function physicalColumn(string $objectType, string $field, string $table): string
    {
        if (in_array($field, ['owner_name', 'account_name', 'created_month'], true)) {
            throw new InvalidArgumentException("Field [{$field}] cannot be filtered directly.");
        }

        return $table.'.'.$field;
    }

    private function cellValue(Model $record, string $key): mixed
    {
        return match ($key) {
            'owner_name' => $record->owner?->name,
            'account_name' => $record->account?->name,
            'created_month' => optional($record->created_at)?->format('Y-m'),
            'converted', 'is_won', 'is_closed' => $record->{$key} ? 'Yes' : 'No',
            'created_at' => optional($record->created_at)?->toDateTimeString(),
            'close_date' => optional($record->close_date)?->toDateString(),
            default => $record->getAttribute($key),
        };
    }

    /**
     * @param  list<string>  $columns
     * @return list<array{key: string, label: string}>
     */
    private function columnHeaders(string $objectType, array $columns): array
    {
        $fields = ReportObjects::fields($objectType);
        $headers = [];

        foreach ($columns as $key) {
            $headers[] = [
                'key' => $key,
                'label' => $fields[$key]['label'] ?? $key,
            ];
        }

        return $headers;
    }

    /**
     * @param  array<string, mixed>|null  $chart
     * @param  list<array<string, mixed>>  $rows
     * @return array{type: string, points: list<array{label: string, value: float|int}>}|null
     */
    private function chartFromRows(?array $chart, array $rows): ?array
    {
        if ($chart === null || $rows === []) {
            return $chart === null ? null : ['type' => (string) ($chart['type'] ?? 'bar'), 'points' => []];
        }

        $type = (string) ($chart['type'] ?? 'bar');
        $labelKey = (string) ($chart['label'] ?? '');
        $valueKey = (string) ($chart['value'] ?? '');

        if ($type === 'metric') {
            $first = $rows[0];
            $value = $first[$valueKey] ?? $first[$labelKey] ?? 0;

            return [
                'type' => 'metric',
                'points' => [[
                    'label' => $labelKey !== '' ? $labelKey : 'Value',
                    'value' => is_numeric($value) ? round((float) $value, 2) : 0,
                ]],
            ];
        }

        if ($labelKey === '' || $valueKey === '') {
            return null;
        }

        $points = [];
        foreach ($rows as $row) {
            $points[] = [
                'label' => (string) ($row[$labelKey] ?? '(None)'),
                'value' => is_numeric($row[$valueKey] ?? null) ? round((float) $row[$valueKey], 2) : 0,
            ];
        }

        return ['type' => $type, 'points' => $points];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function fiscalYearBounds(): array
    {
        $year = (int) now()->year;
        $start = Carbon::create($year, 1, 1)->startOfDay()->toDateTimeString();
        $end = Carbon::create($year, 12, 31)->endOfDay()->toDateTimeString();

        return [$start, $end];
    }

    private function perPage(mixed $value): int
    {
        $perPage = (int) ($value ?: self::DEFAULT_PER_PAGE);

        if ($perPage < 1) {
            return self::DEFAULT_PER_PAGE;
        }

        return min($perPage, self::MAX_PER_PAGE);
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    /**
     * Export all matching rows (capped) without pagination UI.
     *
     * @param  array{sort?: string, direction?: string}  $options
     * @return array{columns: list<array{key: string, label: string}>, rows: list<array<string, mixed>>, chart: array{type: string, points: list<array{label: string, value: float|int}>}|null}
     */
    public function exportRows(Report $report, User $user, array $options = []): array
    {
        $result = $this->runReport($report, $user, [
            ...$options,
            'page' => 1,
            'per_page' => self::MAX_PER_PAGE,
        ]);

        return [
            'columns' => $result['columns'],
            'rows' => $result['rows'],
            'chart' => $result['chart'],
        ];
    }
}
