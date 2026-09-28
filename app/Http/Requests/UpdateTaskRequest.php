<?php

namespace App\Http\Requests;

use App\Models\Task;
use App\Rules\VisibleContact;
use App\Rules\VisibleRelatedRecord;
use App\Support\Picklists;
use App\Support\RelatedRecords;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        $task = $this->route('task');

        return $task instanceof Task
            && ($this->user()?->can('update', $task) ?? false);
    }

    protected function prepareForValidation(): void
    {
        $reminderTime = $this->input('reminder_time');

        $this->merge([
            'assigned_to_id' => $this->input('assigned_to_id') ?: null,
            'related_type' => $this->input('related_type') ?: null,
            'related_id' => $this->input('related_id') ?: null,
            'contact_id' => $this->input('contact_id') ?: null,
            'due_on' => $this->input('due_on') ?: null,
            'status' => $this->input('status') ?: null,
            'priority' => $this->input('priority') ?: null,
            'comments' => $this->filled('comments') ? $this->input('comments') : null,
            'reminder_set' => $this->boolean('reminder_set'),
            'reminder_date' => $this->input('reminder_date') ?: null,
            'reminder_time' => is_string($reminderTime) && $reminderTime !== ''
                ? substr($reminderTime, 0, 5)
                : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:255'],
            'assigned_to_id' => ['required', 'integer', 'exists:users,id'],
            'related_type' => ['nullable', 'required_with:related_id', 'string', Rule::in(array_keys(RelatedRecords::TASK_TYPES))],
            'related_id' => ['nullable', 'required_with:related_type', 'integer', new VisibleRelatedRecord(RelatedRecords::TASK_TYPES)],
            'contact_id' => ['nullable', 'integer', new VisibleContact],
            'due_on' => ['nullable', 'date'],
            'status' => ['nullable', 'string', Rule::in(Picklists::TASK_STATUSES)],
            'priority' => ['nullable', 'string', Rule::in(Picklists::TASK_PRIORITIES)],
            'comments' => ['nullable', 'string'],
            'reminder_set' => ['boolean'],
            'reminder_date' => ['nullable', 'date', Rule::requiredIf(fn () => $this->boolean('reminder_set'))],
            'reminder_time' => ['nullable', 'date_format:H:i', Rule::requiredIf(fn () => $this->boolean('reminder_set'))],
        ];
    }
}
