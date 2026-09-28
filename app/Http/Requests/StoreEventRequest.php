<?php

namespace App\Http\Requests;

use App\Models\Event;
use App\Rules\VisibleContact;
use App\Rules\VisibleRelatedRecord;
use App\Support\Picklists;
use App\Support\RelatedRecords;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Event::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $showTimeAs = $this->input('show_time_as');

        $this->merge([
            'assigned_to_id' => $this->input('assigned_to_id') ?: null,
            'related_type' => $this->input('related_type') ?: null,
            'related_id' => $this->input('related_id') ?: null,
            'contact_id' => $this->input('contact_id') ?: null,
            'location' => $this->filled('location') ? $this->input('location') : null,
            'description' => $this->filled('description') ? $this->input('description') : null,
            'show_time_as' => is_string($showTimeAs) && $showTimeAs !== '' ? $showTimeAs : Picklists::EVENT_SHOW_TIME_AS[0],
            'all_day' => $this->boolean('all_day'),
            'is_private' => $this->boolean('is_private'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $allDay = $this->boolean('all_day');

        return [
            'subject' => ['required', 'string', 'max:255'],
            'assigned_to_id' => ['required', 'integer', 'exists:users,id'],
            'related_type' => ['nullable', 'required_with:related_id', 'string', Rule::in(array_keys(RelatedRecords::EVENT_TYPES))],
            'related_id' => ['nullable', 'required_with:related_type', 'integer', new VisibleRelatedRecord(RelatedRecords::EVENT_TYPES)],
            'contact_id' => ['nullable', 'integer', new VisibleContact],
            'all_day' => ['boolean'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', $allDay ? 'after_or_equal:starts_at' : 'after:starts_at'],
            'location' => ['nullable', 'string', 'max:255'],
            'show_time_as' => ['required', 'string', Rule::in(Picklists::EVENT_SHOW_TIME_AS)],
            'is_private' => ['boolean'],
            'description' => ['nullable', 'string'],
        ];
    }
}
