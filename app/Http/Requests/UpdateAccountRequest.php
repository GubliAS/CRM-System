<?php

namespace App\Http\Requests;

use App\Models\Account;
use App\Rules\AllowedOwnerChange;
use App\Rules\VisibleAccount;
use App\Support\Picklists;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        $account = $this->route('account');

        return $account instanceof Account && ($this->user()?->can('update', $account) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Account $account */
        $account = $this->route('account');

        return [
            'name' => ['required', 'string', 'max:255'],
            'parent_account_id' => ['nullable', 'integer', new VisibleAccount($account->id)],
            'phone' => ['nullable', 'string', 'max:40'],
            'fax' => ['nullable', 'string', 'max:40'],
            'website' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'string', Rule::in(Picklists::ACCOUNT_TYPES)],
            'industry' => ['nullable', 'string', Rule::in(Picklists::INDUSTRIES)],
            'employees' => ['nullable', 'integer', 'min:0', 'max:4294967295'],
            'annual_revenue' => ['nullable', 'numeric', 'min:0', 'decimal:0,2', 'max:9999999999999.99'],
            'billing_street' => ['nullable', 'string', 'max:255'],
            'billing_city' => ['nullable', 'string', 'max:80'],
            'billing_state' => ['nullable', 'string', 'max:80'],
            'billing_postal_code' => ['nullable', 'string', 'max:80'],
            'billing_country' => ['nullable', 'string', 'max:80'],
            'shipping_street' => ['nullable', 'string', 'max:255'],
            'shipping_city' => ['nullable', 'string', 'max:80'],
            'shipping_state' => ['nullable', 'string', 'max:80'],
            'shipping_postal_code' => ['nullable', 'string', 'max:80'],
            'shipping_country' => ['nullable', 'string', 'max:80'],
            'description' => ['nullable', 'string'],
            'owner_id' => ['sometimes', 'nullable', 'integer', Rule::exists('users', 'id'), new AllowedOwnerChange($account->owner_id)],
        ];
    }
}
