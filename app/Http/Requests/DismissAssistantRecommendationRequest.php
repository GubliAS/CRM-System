<?php

namespace App\Http\Requests;

use App\Models\Account;
use App\Models\AssistantRecommendationDismissal;
use App\Models\Opportunity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DismissAssistantRecommendationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('recommendable_type')) {
            return;
        }

        $this->merge([
            'recommendable_type' => AssistantRecommendationDismissal::canonicalizeRecommendableType(
                $this->string('recommendable_type')->toString(),
            ),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'rule' => ['required', 'string', Rule::in(AssistantRecommendationDismissal::RULES)],
            'recommendable_type' => ['required', 'string', Rule::in([
                AssistantRecommendationDismissal::TYPE_ACCOUNT,
                AssistantRecommendationDismissal::TYPE_OPPORTUNITY,
            ])],
            'recommendable_id' => ['required', 'integer', 'min:1'],
        ];
    }

    public function recommendable(): Account|Opportunity|null
    {
        $type = $this->string('recommendable_type')->toString();
        $id = (int) $this->input('recommendable_id');

        return match ($type) {
            AssistantRecommendationDismissal::TYPE_ACCOUNT => Account::query()->visibleTo($this->user())->whereKey($id)->first(),
            AssistantRecommendationDismissal::TYPE_OPPORTUNITY => Opportunity::query()->visibleTo($this->user())->whereKey($id)->first(),
            default => null,
        };
    }
}
