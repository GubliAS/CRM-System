<?php

namespace App\Http\Requests;

use App\Models\Opportunity;
use Illuminate\Foundation\Http\FormRequest;

class CloneOpportunityRequest extends FormRequest
{
    public function authorize(): bool
    {
        $opportunity = $this->route('opportunity');

        return $opportunity instanceof Opportunity
            && ($this->user()?->can('view', $opportunity) ?? false)
            && ($this->user()?->can('create', Opportunity::class) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'include_related' => ['sometimes', 'boolean'],
            'close_date' => [
                $this->sourceCloseDateIsPast() ? 'required' : 'nullable',
                'date',
                'after_or_equal:today',
            ],
        ];
    }

    private function sourceCloseDateIsPast(): bool
    {
        $opportunity = $this->route('opportunity');

        if (! $opportunity instanceof Opportunity) {
            return false;
        }

        return $opportunity->close_date->toDateString() < now()->toDateString();
    }
}
