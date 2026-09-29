<?php

namespace App\Http\Requests;

use App\Support\ImportObjects;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $objectType = (string) $this->input('object_type');

        if ($user === null || $objectType === '') {
            return false;
        }

        $allowed = array_column(ImportObjects::objectsForUser($user), 'key');

        return in_array($objectType, $allowed, true);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $objectType = (string) $this->input('object_type');
        $fieldKeys = array_column(ImportObjects::fields($objectType), 'key');

        return [
            'object_type' => ['required', 'string', Rule::in(ImportObjects::OBJECT_TYPES)],
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
            'update_existing' => ['sometimes', 'boolean'],
            'mapping' => ['required', 'array'],
            'mapping.*' => ['nullable', 'integer', 'min:0'],
            'headers' => ['nullable', 'array'],
            'headers.*' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $objectType = (string) $this->input('object_type');
            $mapping = $this->input('mapping', []);

            if (! is_array($mapping) || $objectType === '') {
                return;
            }

            foreach (ImportObjects::fields($objectType) as $field) {
                if (! $field['required']) {
                    continue;
                }

                $index = $mapping[$field['key']] ?? null;
                if ($index === null || $index === '') {
                    $validator->errors()->add(
                        'mapping.'.$field['key'],
                        "Map a column for {$field['label']}.",
                    );
                }
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $mapping = $this->input('mapping');
        if (is_string($mapping)) {
            $decoded = json_decode($mapping, true);
            if (is_array($decoded)) {
                $this->merge(['mapping' => $decoded]);
            }
        }

        if ($this->has('update_existing')) {
            $this->merge([
                'update_existing' => filter_var($this->input('update_existing'), FILTER_VALIDATE_BOOLEAN),
            ]);
        }
    }
}
