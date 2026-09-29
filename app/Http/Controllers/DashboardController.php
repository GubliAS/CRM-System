<?php

namespace App\Http\Controllers;

use App\Actions\Dashboards\CloneDashboard;
use App\Actions\Dashboards\CreateDashboard;
use App\Actions\Dashboards\DeleteDashboard;
use App\Actions\Dashboards\UpdateDashboard;
use App\Actions\Search\RecordRecentlyViewed;
use App\Http\Requests\StoreDashboardRequest;
use App\Http\Requests\UpdateDashboardRequest;
use App\Models\Dashboard;
use App\Models\Report;
use App\Models\User;
use App\Support\DashboardRunner;
use App\Support\RecentlyViewed;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class DashboardController extends Controller
{
    public function __construct(private DashboardRunner $runner) {}

    public function index(Request $request): InertiaResponse
    {
        $this->authorize('viewAny', Dashboard::class);

        $user = $request->user();
        $search = trim($request->string('search')->toString());
        $folder = $request->string('folder')->toString();
        $perPage = $this->perPage($request);

        if (! in_array($folder, ['recent', 'mine', 'private', 'all'], true)) {
            $folder = 'all';
        }

        $dashboards = Dashboard::query()
            ->visibleTo($user)
            ->with('createdBy:id,name');

        $useRecent = $folder === 'recent' && $search === '';
        $recentIds = $useRecent ? RecentlyViewed::ids($user, Dashboard::class) : [];

        if ($useRecent) {
            RecentlyViewed::constrainToIds($dashboards, Dashboard::class, $recentIds);
        } elseif ($folder === 'mine') {
            $dashboards->where('created_by', $user->id);
        } elseif ($folder === 'private') {
            $dashboards->where('folder', Dashboard::FOLDER_PRIVATE);
        }

        if ($search !== '') {
            $like = $this->like($search);
            $dashboards->where(function (Builder $query) use ($like): void {
                $query->where('name', 'like', $like)
                    ->orWhere('description', 'like', $like);
            });
        }

        if (! $useRecent) {
            $dashboards->orderBy('name')->orderBy('id');
        }

        $dashboards = $dashboards
            ->paginate($useRecent ? RecentlyViewed::LIST_LIMIT : $perPage)
            ->withQueryString()
            ->through(fn (Dashboard $dashboard): array => [
                'id' => $dashboard->id,
                'name' => $dashboard->name,
                'description' => $dashboard->description,
                'widget_count' => count($dashboard->widgets ?? []),
                'folder' => $dashboard->folder,
                'is_owner' => (int) $dashboard->owner_id === (int) $user->id,
                'preview' => $this->layoutPreview($dashboard),
                // `owner` is only loaded in the rare case the creator was deleted.
                'created_by' => $dashboard->createdBy?->only(['id', 'name']) ?? $dashboard->owner?->only(['id', 'name']),
                'created_on' => optional($dashboard->created_at)?->toDateString(),
                'can' => [
                    'update' => $user->can('update', $dashboard),
                    'delete' => $user->can('delete', $dashboard),
                    'clone' => $user->can('clone', $dashboard),
                ],
            ]);

        return Inertia::render('Dashboards/Index', [
            'dashboards' => $dashboards,
            'filters' => [
                'search' => $search,
                'folder' => $folder,
                'per_page' => $perPage,
            ],
            'folders' => [
                ['key' => 'recent', 'label' => 'Recent'],
                ['key' => 'mine', 'label' => 'Created by Me'],
                ['key' => 'private', 'label' => 'Private Dashboards'],
                ['key' => 'all', 'label' => 'All Dashboards'],
            ],
            'can' => [
                'create' => $user->can('create', Dashboard::class),
            ],
        ]);
    }

    public function create(Request $request): InertiaResponse
    {
        $this->authorize('create', Dashboard::class);

        return Inertia::render('Dashboards/Create', [
            'builder' => $this->builderPayload($request->user()),
        ]);
    }

    public function store(StoreDashboardRequest $request, CreateDashboard $create): RedirectResponse
    {
        $dashboard = $create->handle($request->user(), $request->validated());

        return redirect()
            ->route('dashboards.show', $dashboard)
            ->with('success', 'Dashboard saved.');
    }

    public function show(Request $request, Dashboard $dashboard): InertiaResponse
    {
        $this->authorize('view', $dashboard);

        $user = $request->user();
        app(RecordRecentlyViewed::class)->handle($user, $dashboard);

        $globalFilters = $this->globalFilters($request);
        $widgets = $this->runner->run($dashboard, $user, $globalFilters);

        return Inertia::render('Dashboards/Show', [
            'dashboard' => [
                'id' => $dashboard->id,
                'name' => $dashboard->name,
                'description' => $dashboard->description,
                'folder' => $dashboard->folder,
                'owner' => $dashboard->owner?->only(['id', 'name']),
                'updated_on' => optional($dashboard->updated_at)?->toDateString(),
            ],
            'widgets' => $widgets,
            'filters' => $globalFilters,
            'owners' => $this->ownerOptions($user),
            'can' => [
                'update' => $user->can('update', $dashboard),
                'delete' => $user->can('delete', $dashboard),
                'clone' => $user->can('clone', $dashboard),
            ],
        ]);
    }

    /**
     * Runs one not-yet-saved widget so the builder can show it exactly as the
     * finished dashboard will. Reports the user cannot view come back as an
     * "unavailable" widget, never as data.
     */
    public function previewWidget(Request $request): JsonResponse
    {
        $this->authorize('create', Dashboard::class);

        $data = $request->validate([
            'report_id' => ['required', 'integer'],
            'type' => ['required', 'string', Rule::in(Dashboard::WIDGET_TYPES)],
        ]);

        return response()->json($this->runner->runWidget([
            'id' => 'preview',
            'report_id' => $data['report_id'],
            'type' => $data['type'],
        ], $request->user()));
    }

    public function edit(Request $request, Dashboard $dashboard): InertiaResponse
    {
        $this->authorize('update', $dashboard);

        return Inertia::render('Dashboards/Edit', [
            'dashboard' => [
                'id' => $dashboard->id,
                'name' => $dashboard->name,
                'description' => $dashboard->description,
                'folder' => $dashboard->folder,
                'widgets' => $dashboard->widgets ?? [],
            ],
            'builder' => $this->builderPayload($request->user()),
        ]);
    }

    public function update(
        UpdateDashboardRequest $request,
        Dashboard $dashboard,
        UpdateDashboard $update,
    ): RedirectResponse {
        $update->handle($request->user(), $dashboard, $request->validated());

        return redirect()
            ->route('dashboards.show', $dashboard)
            ->with('success', 'Dashboard updated.');
    }

    public function destroy(Dashboard $dashboard, DeleteDashboard $delete): RedirectResponse
    {
        $this->authorize('delete', $dashboard);
        $delete->handle($dashboard);

        return redirect()
            ->route('dashboards.index')
            ->with('success', 'Dashboard deleted.');
    }

    public function clone(
        Request $request,
        Dashboard $dashboard,
        CloneDashboard $clone,
    ): RedirectResponse {
        $this->authorize('clone', $dashboard);
        $copy = $clone->handle($request->user(), $dashboard);

        return redirect()
            ->route('dashboards.edit', $copy)
            ->with('success', 'Dashboard cloned.');
    }

    /**
     * The stored layout, trimmed to what a thumbnail needs (no report is run).
     *
     * @return list<array{type: string, row: int, col: int, width: int, height: int}>
     */
    private function layoutPreview(Dashboard $dashboard): array
    {
        return collect($dashboard->widgets ?? [])
            ->map(fn (array $widget): array => [
                'type' => (string) ($widget['type'] ?? 'table'),
                'row' => (int) ($widget['row'] ?? 0),
                'col' => (int) ($widget['col'] ?? 0),
                'width' => (int) ($widget['width'] ?? 6),
                'height' => (int) ($widget['height'] ?? 3),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array{date_from: string|null, date_to: string|null, owner_id: int|null}
     */
    private function globalFilters(Request $request): array
    {
        $ownerId = $request->integer('owner_id');
        $dateFrom = $request->string('date_from')->toString();
        $dateTo = $request->string('date_to')->toString();

        return [
            'date_from' => $dateFrom !== '' ? $dateFrom : null,
            'date_to' => $dateTo !== '' ? $dateTo : null,
            'owner_id' => $ownerId > 0 ? $ownerId : null,
        ];
    }

    /**
     * @return array{reports: list<array{id: int, name: string, object_type: string}>, widgetTypes: list<array{key: string, label: string}>, maxWidgets: int}
     */
    private function builderPayload(User $user): array
    {
        $reports = Report::query()
            ->visibleTo($user)
            ->orderBy('name')
            ->orderBy('id')
            ->get(['id', 'name', 'object_type'])
            ->filter(fn (Report $report): bool => $user->can('view', $report))
            ->map(fn (Report $report): array => [
                'id' => $report->id,
                'name' => $report->name,
                'object_type' => $report->object_type,
            ])
            ->values()
            ->all();

        return [
            'reports' => $reports,
            'widgetTypes' => [
                ['key' => 'chart', 'label' => 'Chart'],
                ['key' => 'table', 'label' => 'Table'],
                ['key' => 'metric', 'label' => 'Metric'],
                ['key' => 'gauge', 'label' => 'Gauge'],
            ],
            'maxWidgets' => Dashboard::MAX_WIDGETS,
        ];
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    private function ownerOptions(User $user): array
    {
        $query = User::query()->orderBy('name')->orderBy('id');

        if (! in_array($user->role?->slug, ['admin', 'sales-manager'], true)) {
            $query->whereKey($user->id);
        }

        return $query->get(['id', 'name'])
            ->map(fn (User $owner): array => ['id' => $owner->id, 'name' => $owner->name])
            ->all();
    }

    private function perPage(Request $request): int
    {
        $perPage = (int) $request->input('per_page', 25);

        if ($perPage < 1) {
            return 25;
        }

        return min($perPage, 200);
    }

    private function like(string $search): string
    {
        return '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search).'%';
    }
}
