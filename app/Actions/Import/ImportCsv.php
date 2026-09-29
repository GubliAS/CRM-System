<?php

namespace App\Actions\Import;

use App\Models\Account;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\User;
use App\Support\ImportObjects;
use App\Support\OpportunityStage;
use App\Support\Picklists;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class ImportCsv
{
    /**
     * @param  array<string, int|string|null>  $mapping  field => csv header index
     * @return array{imported: int, updated: int, failed: int, errors: list<array{row: int, errors: string, data: array<string, mixed>}>}
     */
    public function handle(
        User $actor,
        string $objectType,
        UploadedFile $file,
        array $mapping,
        bool $updateExisting = false,
    ): array {
        if (! in_array($objectType, ImportObjects::OBJECT_TYPES, true)) {
            throw new InvalidArgumentException('Unsupported import object.');
        }

        $handle = fopen($file->getRealPath(), 'r');
        if ($handle === false) {
            throw new InvalidArgumentException('Unable to read CSV file.');
        }

        try {
            $headers = fgetcsv($handle);
            if ($headers === false || $headers === [null] || $headers === []) {
                throw new InvalidArgumentException('CSV file has no header row.');
            }

            $imported = 0;
            $updated = 0;
            $failed = 0;
            $errors = [];
            $rowNumber = 1;

            while (($row = fgetcsv($handle)) !== false) {
                $rowNumber++;

                if ($this->rowIsEmpty($row)) {
                    continue;
                }

                $attributes = $this->mapRow($row, $mapping);
                $validation = $this->validateRow($actor, $objectType, $attributes);

                if ($validation['errors'] !== []) {
                    $failed++;
                    $errors[] = [
                        'row' => $rowNumber,
                        'errors' => implode('; ', $validation['errors']),
                        'data' => $attributes,
                    ];

                    continue;
                }

                $result = DB::transaction(function () use ($actor, $objectType, $validation, $updateExisting): string {
                    return $this->persist($actor, $objectType, $validation['data'], $updateExisting);
                });

                if ($result === 'updated') {
                    $updated++;
                } else {
                    $imported++;
                }
            }
        } finally {
            fclose($handle);
        }

        return [
            'imported' => $imported,
            'updated' => $updated,
            'failed' => $failed,
            'errors' => $errors,
        ];
    }

    /**
     * @param  list<string|null>  $row
     * @param  array<string, int|string|null>  $mapping
     * @return array<string, mixed>
     */
    private function mapRow(array $row, array $mapping): array
    {
        $attributes = [];

        foreach ($mapping as $field => $index) {
            if ($index === null || $index === '' || $index === -1) {
                continue;
            }

            $value = $row[(int) $index] ?? null;
            if (is_string($value)) {
                $value = trim($value);
            }

            if ($value === '' || $value === null) {
                continue;
            }

            $attributes[$field] = $value;
        }

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array{data: array<string, mixed>, errors: list<string>}
     */
    private function validateRow(User $actor, string $objectType, array $attributes): array
    {
        $rules = $this->rules($objectType);
        $validator = Validator::make($attributes, $rules);

        if ($validator->fails()) {
            return [
                'data' => $attributes,
                'errors' => $validator->errors()->all(),
            ];
        }

        $data = $validator->validated();

        if ($objectType === ImportObjects::OBJECT_CONTACT || $objectType === ImportObjects::OBJECT_OPPORTUNITY) {
            $accountName = (string) ($data['account_name'] ?? '');
            $account = Account::query()
                ->visibleTo($actor)
                ->where('name', $accountName)
                ->first();

            if ($account === null) {
                return [
                    'data' => $attributes,
                    'errors' => ["Account [{$accountName}] was not found."],
                ];
            }

            $data['account_id'] = $account->id;
            unset($data['account_name']);
        }

        return [
            'data' => $data,
            'errors' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(string $objectType): array
    {
        return match ($objectType) {
            ImportObjects::OBJECT_LEAD => [
                'last_name' => ['required', 'string', 'max:80'],
                'company' => ['required', 'string', 'max:255'],
                'first_name' => ['nullable', 'string', 'max:40'],
                'email' => ['nullable', 'email', 'max:80'],
                'phone' => ['nullable', 'string', 'max:40'],
                'mobile' => ['nullable', 'string', 'max:40'],
                'title' => ['nullable', 'string', 'max:128'],
                'lead_status' => ['nullable', 'string', Rule::in(Picklists::LEAD_STATUSES_EDITABLE)],
                'lead_source' => ['nullable', 'string', Rule::in(Picklists::LEAD_SOURCES)],
                'rating' => ['nullable', 'string', Rule::in(Picklists::LEAD_RATINGS)],
                'industry' => ['nullable', 'string', 'max:80'],
                'website' => ['nullable', 'string', 'max:255'],
                'street' => ['nullable', 'string', 'max:255'],
                'city' => ['nullable', 'string', 'max:80'],
                'state' => ['nullable', 'string', 'max:80'],
                'postal_code' => ['nullable', 'string', 'max:80'],
                'country' => ['nullable', 'string', 'max:80'],
                'description' => ['nullable', 'string'],
            ],
            ImportObjects::OBJECT_ACCOUNT => [
                'name' => ['required', 'string', 'max:255'],
                'phone' => ['nullable', 'string', 'max:40'],
                'fax' => ['nullable', 'string', 'max:40'],
                'website' => ['nullable', 'string', 'max:255'],
                'type' => ['nullable', 'string', Rule::in(Picklists::ACCOUNT_TYPES)],
                'industry' => ['nullable', 'string', 'max:80'],
                'employees' => ['nullable', 'integer', 'min:0'],
                'annual_revenue' => ['nullable', 'numeric', 'min:0'],
                'billing_street' => ['nullable', 'string', 'max:255'],
                'billing_city' => ['nullable', 'string', 'max:80'],
                'billing_state' => ['nullable', 'string', 'max:80'],
                'billing_postal_code' => ['nullable', 'string', 'max:80'],
                'billing_country' => ['nullable', 'string', 'max:80'],
                'description' => ['nullable', 'string'],
            ],
            ImportObjects::OBJECT_CONTACT => [
                'last_name' => ['required', 'string', 'max:80'],
                'account_name' => ['required', 'string', 'max:255'],
                'first_name' => ['nullable', 'string', 'max:40'],
                'email' => ['nullable', 'email', 'max:80'],
                'phone' => ['nullable', 'string', 'max:40'],
                'mobile' => ['nullable', 'string', 'max:40'],
                'title' => ['nullable', 'string', 'max:128'],
                'department' => ['nullable', 'string', 'max:80'],
                'lead_source' => ['nullable', 'string', 'max:40'],
                'mailing_street' => ['nullable', 'string', 'max:255'],
                'mailing_city' => ['nullable', 'string', 'max:80'],
                'mailing_state' => ['nullable', 'string', 'max:80'],
                'mailing_postal_code' => ['nullable', 'string', 'max:80'],
                'mailing_country' => ['nullable', 'string', 'max:80'],
                'description' => ['nullable', 'string'],
            ],
            ImportObjects::OBJECT_OPPORTUNITY => [
                'name' => ['required', 'string', 'max:255'],
                'account_name' => ['required', 'string', 'max:255'],
                'amount' => ['nullable', 'numeric', 'min:0'],
                'close_date' => ['required', 'date'],
                'stage' => ['nullable', 'string', Rule::in(array_keys(OpportunityStage::PROBABILITIES))],
                'type' => ['nullable', 'string', 'max:80'],
                'lead_source' => ['nullable', 'string', 'max:40'],
                'next_step' => ['nullable', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
            ],
            default => [],
        };
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function persist(User $actor, string $objectType, array $data, bool $updateExisting): string
    {
        return match ($objectType) {
            ImportObjects::OBJECT_LEAD => $this->persistLead($actor, $data, $updateExisting),
            ImportObjects::OBJECT_ACCOUNT => $this->persistAccount($actor, $data, $updateExisting),
            ImportObjects::OBJECT_CONTACT => $this->persistContact($actor, $data, $updateExisting),
            ImportObjects::OBJECT_OPPORTUNITY => $this->persistOpportunity($actor, $data, $updateExisting),
            default => throw new InvalidArgumentException('Unsupported import object.'),
        };
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function persistLead(User $actor, array $data, bool $updateExisting): string
    {
        $existing = null;
        if ($updateExisting && filled($data['email'] ?? null)) {
            $existing = Lead::query()
                ->visibleTo($actor)
                ->where('email', $data['email'])
                ->where('converted', false)
                ->first();
        }

        $payload = [
            ...$data,
            'lead_status' => $data['lead_status'] ?? 'New',
            'converted' => false,
            'updated_by' => $actor->id,
        ];

        if ($existing) {
            $existing->update($payload);

            return 'updated';
        }

        Lead::query()->create([
            ...$payload,
            'owner_id' => $actor->id,
            'created_by' => $actor->id,
        ]);

        return 'created';
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function persistAccount(User $actor, array $data, bool $updateExisting): string
    {
        $existing = null;
        if ($updateExisting) {
            $existing = Account::query()
                ->visibleTo($actor)
                ->where('name', $data['name'])
                ->first();
        }

        $payload = [
            ...$data,
            'updated_by' => $actor->id,
        ];

        if ($existing) {
            $existing->update($payload);

            return 'updated';
        }

        Account::query()->create([
            ...$payload,
            'owner_id' => $actor->id,
            'created_by' => $actor->id,
        ]);

        return 'created';
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function persistContact(User $actor, array $data, bool $updateExisting): string
    {
        $existing = null;
        if ($updateExisting && filled($data['email'] ?? null)) {
            $existing = Contact::query()
                ->visibleTo($actor)
                ->where('email', $data['email'])
                ->first();
        }

        $payload = [
            ...$data,
            'updated_by' => $actor->id,
        ];

        if ($existing) {
            $existing->update($payload);

            return 'updated';
        }

        Contact::query()->create([
            ...$payload,
            'owner_id' => $actor->id,
            'created_by' => $actor->id,
        ]);

        return 'created';
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function persistOpportunity(User $actor, array $data, bool $updateExisting): string
    {
        $existing = null;
        if ($updateExisting) {
            $existing = Opportunity::query()
                ->visibleTo($actor)
                ->whereNull('archived_at')
                ->where('name', $data['name'])
                ->where('account_id', $data['account_id'])
                ->first();
        }

        $stage = $data['stage'] ?? OpportunityStage::QUALIFICATION;
        $payload = [
            ...$data,
            'stage' => $stage,
            'probability' => OpportunityStage::probability($stage),
            'updated_by' => $actor->id,
        ];

        if ($existing) {
            $existing->update($payload);

            return 'updated';
        }

        Opportunity::query()->create([
            ...$payload,
            'owner_id' => $actor->id,
            'created_by' => $actor->id,
        ]);

        return 'created';
    }

    /**
     * @param  list<string|null>  $row
     */
    private function rowIsEmpty(array $row): bool
    {
        foreach ($row as $cell) {
            if (trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }
}
