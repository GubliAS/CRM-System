<?php

namespace App\Http\Requests;

use App\Models\Dashboard;
use App\Models\Report;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreDashboardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Dashboard::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->dashboardRules();
    }

    /**
     * @return array<string, mixed>
     */
    protected function dashboardRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'widgets' => ['nullable', 'array', 'max:'.Dashboard::MAX_WIDGETS],
            'widgets.*.id' => ['required', 'string', 'max:64'],
            'widgets.*.report_id' => ['required', 'integer', Rule::exists('reports', 'id')],
            'widgets.*.type' => ['required', 'string', Rule::in(Dashboard::WIDGET_TYPES)],
            'widgets.*.title' => ['nullable', 'string', 'max:255'],
            'widgets.*.row' => ['nullable', 'integer', 'min:0', 'max:100'],
            'widgets.*.col' => ['nullable', 'integer', 'min:0', 'max:11'],
            'widgets.*.width' => ['nullable', 'integer', 'min:1', 'max:12'],
            'widgets.*.height' => ['nullable', 'integer', 'min:1', 'max:8'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $user = $this->user();
            $widgets = $this->input('widgets', []);

            if (! is_array($widgets) || $user === null) {
                return;
            }

            foreach ($widgets as $index => $widget) {
                $reportId = (int) ($widget['report_id'] ?? 0);
                if ($reportId < 1) {
                    continue;
                }

                $report = Report::query()->find($reportId);
                if ($report === null || $user->cannot('view', $report)) {
                    $validator->errors()->add(
                        "widgets.{$index}.report_id",
                        'You can only add reports you can view.',
                    );
                }
            }
        });
    }
}
