<?php

namespace App\Http\Controllers;

use App\Actions\Reports\CreateReport;
use App\Actions\Reports\DeleteReport;
use App\Actions\Reports\SeedPrebuiltReports;
use App\Actions\Reports\UpdateReport;
use App\Actions\Search\RecordRecentlyViewed;
use App\Http\Requests\PreviewReportRequest;
use App\Http\Requests\StoreReportRequest;
use App\Http\Requests\UpdateReportRequest;
use App\Models\Report;
use App\Models\User;
use App\Support\RecentlyViewed;
use App\Support\ReportExporter;
use App\Support\ReportObjects;
use App\Support\ReportRunner;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(
        private ReportRunner $runner,
        private ReportExporter $exporter,
    ) {}

    public function index(Request $request): InertiaResponse
    {
        $this->authorize('viewAny', Report::class);

        $user = $request->user();
        $this->ensurePrebuiltReports($user);

        $search = trim($request->string('search')->toString());
        $folder = $request->string('folder')->toString();
        $perPage = $this->perPage($request);

        if (! in_array($folder, ['recent', 'mine', 'private', 'public', 'all'], true)) {
            $folder = 'all';
        }

        $reports = Report::query()
            ->visibleTo($user)
            ->with(['owner:id,name', 'createdBy:id,name']);

        $this->constrainObjectAccess($reports, $user);

        $useRecent = $folder === 'recent' && $search === '';
        $recentIds = $useRecent ? RecentlyViewed::ids($user, Report::class) : [];

        if ($useRecent) {
            RecentlyViewed::constrainToIds($reports, Report::class, $recentIds);
        } elseif ($folder === 'mine') {
            $reports->where('created_by', $user->id);
        } elseif ($folder === 'private') {
            $reports->where('folder', Report::FOLDER_PRIVATE);
        } elseif ($folder === 'public') {
            $reports->where('folder', Report::FOLDER_PUBLIC);
        }

        if ($search !== '') {
            $like = $this->like($search);
            $reports->where(function (Builder $query) use ($like): void {
                $query->where('name', 'like', $like)
                    ->orWhere('description', 'like', $like);
            });
        }

        if (! $useRecent) {
            $reports->orderBy('name')->orderBy('id');
        }

        $reports = $reports
            ->paginate($useRecent ? RecentlyViewed::LIST_LIMIT : $perPage)
            ->withQueryString()
            ->through(fn (Report $report): array => [
                'id' => $report->id,
                'name' => $report->name,
                'description' => $report->description,
                'folder' => $report->folder,
                'folder_label' => $report->folder === Report::FOLDER_PRIVATE ? 'Private Reports' : 'Public Reports',
                'object_type' => $report->object_type,
                'is_system' => $report->is_system,
                'created_by' => $report->createdBy?->only(['id', 'name']) ?? $report->owner?->only(['id', 'name']),
                'created_on' => optional($report->created_at)?->toDateString(),
                'can' => [
                    'update' => $user->can('update', $report),
                    'delete' => $user->can('delete', $report),
                ],
            ]);

        return Inertia::render('Reports/Index', [
            'reports' => $reports,
            'filters' => [
                'search' => $search,
                'folder' => $folder,
                'per_page' => $perPage,
            ],
            'folders' => [
                ['key' => 'recent', 'label' => 'Recent'],
                ['key' => 'mine', 'label' => 'Created by Me'],
                ['key' => 'private', 'label' => 'Private Reports'],
                ['key' => 'public', 'label' => 'Public Reports'],
                ['key' => 'all', 'label' => 'All Reports'],
            ],
            'can' => [
                'create' => $user->can('create', Report::class),
            ],
        ]);
    }

    public function create(Request $request): InertiaResponse
    {
        $this->authorize('create', Report::class);

        return Inertia::render('Reports/Create', [
            'builder' => $this->builderPayload($request->user()),
        ]);
    }

    public function store(StoreReportRequest $request, CreateReport $create): RedirectResponse
    {
        $report = $create->handle($request->user(), $request->validated());

        return redirect()
            ->route('reports.show', $report)
            ->with('success', 'Report saved.');
    }

    public function show(Request $request, Report $report): InertiaResponse
    {
        $this->authorize('view', $report);

        $user = $request->user();
        app(RecordRecentlyViewed::class)->handle($user, $report);

        $result = $this->runner->runReport($report, $user, [
            'sort' => $request->string('sort')->toString(),
            'direction' => $request->string('direction')->toString(),
            'page' => (int) $request->integer('page', 1),
            'per_page' => $this->perPage($request),
        ]);

        $result['links'] = $this->resultLinks($report, $result, $request);

        $report->load(['owner:id,name', 'createdBy:id,name']);

        return Inertia::render('Reports/Show', [
            'report' => [
                'id' => $report->id,
                'name' => $report->name,
                'description' => $report->description,
                'folder' => $report->folder,
                'folder_label' => $report->folder === Report::FOLDER_PRIVATE ? 'Private Reports' : 'Public Reports',
                'object_type' => $report->object_type,
                'is_system' => $report->is_system,
                'columns' => $report->columns,
                'filters' => $report->filters,
                'group_by' => $report->group_by,
                'chart' => $report->chart,
                'created_by' => $report->createdBy?->only(['id', 'name']),
            ],
            'result' => $result,
            'filters' => [
                'sort' => $request->string('sort')->toString(),
                'direction' => $request->string('direction')->toString() === 'desc' ? 'desc' : 'asc',
                'per_page' => $this->perPage($request),
            ],
            'can' => [
                'update' => $user->can('update', $report),
                'delete' => $user->can('delete', $report),
                'export' => $user->can('export', $report),
            ],
        ]);
    }

    public function edit(Request $request, Report $report): InertiaResponse
    {
        $this->authorize('update', $report);

        return Inertia::render('Reports/Edit', [
            'report' => [
                'id' => $report->id,
                'name' => $report->name,
                'description' => $report->description,
                'folder' => $report->folder,
                'object_type' => $report->object_type,
                'columns' => $report->columns ?? [],
                'filters' => $report->filters ?? [],
                'group_by' => $report->group_by ?? [],
                'chart' => $report->chart,
            ],
            'builder' => $this->builderPayload($request->user()),
        ]);
    }

    public function update(UpdateReportRequest $request, Report $report, UpdateReport $update): RedirectResponse
    {
        $update->handle($request->user(), $report, $request->validated());

        return redirect()
            ->route('reports.show', $report)
            ->with('success', 'Report updated.');
    }

    public function destroy(Report $report, DeleteReport $delete): RedirectResponse
    {
        $this->authorize('delete', $report);
        $delete->handle($report);

        return redirect()
            ->route('reports.index')
            ->with('success', 'Report deleted.');
    }

    public function preview(PreviewReportRequest $request): InertiaResponse|Response
    {
        $data = $request->validated();
        $result = $this->runner->run(
            $request->user(),
            $data['object_type'],
            [
                'columns' => $data['columns'],
                'filters' => $data['filters'] ?? [],
                'group_by' => $data['group_by'] ?? [],
                'chart' => $data['chart'] ?? null,
            ],
            [
                'per_page' => $data['per_page'] ?? ReportRunner::DEFAULT_PER_PAGE,
                'page' => 1,
            ],
        );

        return response()->json(['result' => $result]);
    }

    public function export(Request $request, Report $report): StreamedResponse
    {
        $this->authorize('export', $report);

        $format = strtolower($request->string('format')->toString());
        if (! in_array($format, ['csv', 'excel', 'pdf'], true)) {
            abort(422, 'Unsupported export format.');
        }

        $payload = $this->runner->exportRows($report, $request->user(), [
            'sort' => $request->string('sort')->toString(),
            'direction' => $request->string('direction')->toString(),
        ]);

        return $this->exporter->download(
            $format,
            $report->name,
            $payload['columns'],
            $payload['rows'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function builderPayload(User $user): array
    {
        $objects = ReportObjects::objectsForUser($user);
        $fields = [];

        foreach ($objects as $object) {
            $fields[$object['key']] = [
                'columns' => ReportObjects::selectableColumns($object['key']),
                'groupable' => ReportObjects::groupableFields($object['key']),
                'all' => collect(ReportObjects::fields($object['key']))
                    ->map(fn (array $meta, string $key): array => [
                        'key' => $key,
                        'label' => $meta['label'],
                        'type' => $meta['type'],
                    ])
                    ->values()
                    ->all(),
            ];
        }

        return [
            'objects' => $objects,
            'fields' => $fields,
            'operators' => [
                ['key' => 'equals', 'label' => 'Equals'],
                ['key' => 'not_equals', 'label' => 'Not equals'],
                ['key' => 'contains', 'label' => 'Contains'],
                ['key' => 'gt', 'label' => 'Greater than'],
                ['key' => 'gte', 'label' => 'Greater or equal'],
                ['key' => 'lt', 'label' => 'Less than'],
                ['key' => 'lte', 'label' => 'Less or equal'],
                ['key' => 'this_fy', 'label' => 'This fiscal year'],
                ['key' => 'is_true', 'label' => 'Is true'],
                ['key' => 'is_false', 'label' => 'Is false'],
                ['key' => 'is_null', 'label' => 'Is empty'],
            ],
            'folders' => [
                ['key' => Report::FOLDER_PRIVATE, 'label' => 'Private Reports'],
                ['key' => Report::FOLDER_PUBLIC, 'label' => 'Public Reports'],
            ],
            'chartTypes' => [
                ['key' => 'bar', 'label' => 'Bar chart'],
                ['key' => 'metric', 'label' => 'Metric'],
            ],
        ];
    }

    private function ensurePrebuiltReports(User $user): void
    {
        if (Report::query()->where('is_system', true)->exists()) {
            return;
        }

        $owner = User::query()->orderBy('id')->first() ?? $user;
        app(SeedPrebuiltReports::class)->handle($owner);
    }

    /**
     * @param  Builder<Report>  $query
     */
    private function constrainObjectAccess(Builder $query, User $user): void
    {
        $allowed = array_column(ReportObjects::objectsForUser($user), 'key');

        if ($allowed === []) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->whereIn('object_type', $allowed);
    }

    private function perPage(Request $request): int
    {
        $value = $request->input('per_page', ReportRunner::DEFAULT_PER_PAGE);
        $perPage = (int) $value;

        if ($perPage < 1) {
            return ReportRunner::DEFAULT_PER_PAGE;
        }

        return min($perPage, ReportRunner::MAX_PER_PAGE);
    }

    private function like(string $search): string
    {
        return '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search).'%';
    }

    /**
     * @param  array{current_page: int, last_page: int}  $result
     * @return list<array{url: string|null, label: string, active: bool}>
     */
    private function resultLinks(Report $report, array $result, Request $request): array
    {
        $query = $request->except('page');
        $links = [];
        $current = $result['current_page'];
        $last = $result['last_page'];

        $links[] = [
            'url' => $current > 1
                ? route('reports.show', ['report' => $report, ...$query, 'page' => $current - 1])
                : null,
            'label' => '&laquo; Previous',
            'active' => false,
        ];

        for ($page = 1; $page <= $last; $page++) {
            $links[] = [
                'url' => route('reports.show', ['report' => $report, ...$query, 'page' => $page]),
                'label' => (string) $page,
                'active' => $page === $current,
            ];
        }

        $links[] = [
            'url' => $current < $last
                ? route('reports.show', ['report' => $report, ...$query, 'page' => $current + 1])
                : null,
            'label' => 'Next &raquo;',
            'active' => false,
        ];

        return $links;
    }
}
