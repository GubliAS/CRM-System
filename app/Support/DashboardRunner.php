<?php

namespace App\Support;

use App\Models\Dashboard;
use App\Models\Report;
use App\Models\User;

class DashboardRunner
{
    public function __construct(private ReportRunner $runner) {}

    /**
     * @param  array{date_from?: string|null, date_to?: string|null, owner_id?: int|null}  $globalFilters
     * @return list<array<string, mixed>>
     */
    public function run(Dashboard $dashboard, User $user, array $globalFilters = []): array
    {
        return array_map(
            fn (array $widget): array => $this->runWidget($widget, $user, $globalFilters),
            array_values($dashboard->widgets ?? []),
        );
    }

    /**
     * Runs a single widget definition under the viewer's own permissions. Used
     * by run() and by the builder, which previews a widget before it is saved.
     *
     * @param  array<string, mixed>  $widget
     * @param  array{date_from?: string|null, date_to?: string|null, owner_id?: int|null}  $globalFilters
     * @return array<string, mixed>
     */
    public function runWidget(array $widget, User $user, array $globalFilters = []): array
    {
        $reportId = (int) ($widget['report_id'] ?? 0);
        $report = Report::query()->visibleTo($user)->find($reportId);

        if ($report === null || $user->cannot('view', $report)) {
            return [
                'id' => (string) ($widget['id'] ?? ''),
                'title' => (string) ($widget['title'] ?? 'Unavailable'),
                'type' => (string) ($widget['type'] ?? 'table'),
                'report_id' => $reportId,
                'report_name' => null,
                'report_url' => null,
                'error' => 'Report is unavailable.',
                'result' => null,
                'layout' => $this->layout($widget),
            ];
        }

        $type = (string) ($widget['type'] ?? 'table');
        $result = $this->runner->runReport($report, $user, [
            'page' => 1,
            'per_page' => $type === 'table' ? 10 : ReportRunner::MAX_PER_PAGE,
            'global_filters' => $globalFilters,
        ]);

        return [
            'id' => (string) ($widget['id'] ?? ''),
            'title' => (string) ($widget['title'] ?? $report->name),
            'type' => $type,
            'report_id' => $report->id,
            'report_name' => $report->name,
            'report_url' => route('reports.show', $report),
            'error' => null,
            'result' => $this->shapeForType($type, $result),
            'layout' => $this->layout($widget),
        ];
    }

    /**
     * @param  array<string, mixed>  $widget
     * @return array{row: int, col: int, width: int, height: int}
     */
    private function layout(array $widget): array
    {
        return [
            'row' => (int) ($widget['row'] ?? 0),
            'col' => (int) ($widget['col'] ?? 0),
            'width' => (int) ($widget['width'] ?? 6),
            'height' => (int) ($widget['height'] ?? 3),
        ];
    }

    /**
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    private function shapeForType(string $type, array $result): array
    {
        if ($type === 'metric' || $type === 'gauge') {
            $value = 0.0;
            if (($result['chart']['type'] ?? null) === 'metric' && ($result['chart']['points'][0] ?? null)) {
                $value = (float) $result['chart']['points'][0]['value'];
            } elseif (($result['rows'][0] ?? null) !== null) {
                $first = $result['rows'][0];
                foreach ($first as $key => $cell) {
                    if (in_array($key, ['record_id', 'record_url'], true)) {
                        continue;
                    }
                    if (is_numeric($cell)) {
                        $value = (float) $cell;
                        break;
                    }
                }
                if ($value === 0.0 && isset($result['total'])) {
                    $value = (float) $result['total'];
                }
            } else {
                $value = (float) ($result['total'] ?? 0);
            }

            return [
                'value' => round($value, 2),
                'label' => $result['chart']['points'][0]['label'] ?? 'Value',
                'total' => $result['total'],
            ];
        }

        if ($type === 'chart') {
            return [
                'chart' => $result['chart'] ?? ['type' => 'bar', 'points' => []],
                'total' => $result['total'],
            ];
        }

        return [
            'columns' => $result['columns'],
            'rows' => array_slice($result['rows'], 0, 10),
            'total' => $result['total'],
        ];
    }
}
