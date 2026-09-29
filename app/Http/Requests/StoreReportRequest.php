<?php

namespace App\Http\Requests;

use App\Models\Report;
use App\Support\ReportObjects;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Report::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->reportRules();
    }

    /**
     * @return array<string, mixed>
     */
    protected function reportRules(): array
    {
        $objectType = (string) $this->input('object_type');
        $fieldKeys = $objectType !== '' && in_array($objectType, Report::OBJECT_TYPES, true)
            ? array_keys(ReportObjects::fields($objectType))
            : [];
        $groupKeys = array_column(
            $objectType !== '' && in_array($objectType, Report::OBJECT_TYPES, true)
                ? ReportObjects::groupableFields($objectType)
                : [],
            'key',
        );

        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'folder' => ['required', 'string', Rule::in(Report::FOLDERS)],
            'object_type' => ['required', 'string', Rule::in(Report::OBJECT_TYPES)],
            'columns' => ['required', 'array', 'min:1'],
            'columns.*' => ['required', 'string', Rule::in($fieldKeys)],
            'filters' => ['nullable', 'array'],
            'filters.*.field' => ['required_with:filters', 'string', 'max:64'],
            'filters.*.operator' => ['required_with:filters', 'string', Rule::in([
                ...ReportObjects::operators(),
                'is_null',
            ])],
            'filters.*.value' => ['nullable'],
            'group_by' => ['nullable', 'array'],
            'group_by.*' => ['string', Rule::in($groupKeys)],
            'chart' => ['nullable', 'array'],
            'chart.type' => ['nullable', 'string', Rule::in(['bar', 'metric'])],
            'chart.label' => ['nullable', 'string', 'max:64'],
            'chart.value' => ['nullable', 'string', 'max:64'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $user = $this->user();
            $objectType = (string) $this->input('object_type');

            if ($user && $objectType !== '' && ! ReportObjects::userCanAccessObject($user, $objectType)) {
                $validator->errors()->add('object_type', 'You cannot build reports for that object.');
            }
        });
    }
}
