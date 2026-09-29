<?php

namespace App\Support;

use App\Models\Account;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\Report;
use App\Models\SupportCase;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

final class ReportObjects
{
    /**
     * @return list<array{key: string, label: string, model: class-string<Model>}>
     */
    public static function catalog(): array
    {
        return [
            ['key' => Report::OBJECT_LEAD, 'label' => 'Leads', 'model' => Lead::class],
            ['key' => Report::OBJECT_ACCOUNT, 'label' => 'Accounts', 'model' => Account::class],
            ['key' => Report::OBJECT_CONTACT, 'label' => 'Contacts', 'model' => Contact::class],
            ['key' => Report::OBJECT_OPPORTUNITY, 'label' => 'Opportunities', 'model' => Opportunity::class],
            ['key' => Report::OBJECT_CASE, 'label' => 'Cases', 'model' => SupportCase::class],
        ];
    }

    /**
     * @return class-string<Model>
     */
    public static function modelClass(string $objectType): string
    {
        foreach (self::catalog() as $item) {
            if ($item['key'] === $objectType) {
                return $item['model'];
            }
        }

        throw new InvalidArgumentException("Unknown report object [{$objectType}].");
    }

    /**
     * @return array<string, array{label: string, type: string, sortable?: bool, relation?: string}>
     */
    public static function fields(string $objectType): array
    {
        return match ($objectType) {
            Report::OBJECT_LEAD => [
                'id' => ['label' => 'ID', 'type' => 'number'],
                'first_name' => ['label' => 'First name', 'type' => 'string'],
                'last_name' => ['label' => 'Last name', 'type' => 'string'],
                'company' => ['label' => 'Company', 'type' => 'string'],
                'email' => ['label' => 'Email', 'type' => 'string'],
                'phone' => ['label' => 'Phone', 'type' => 'string'],
                'lead_source' => ['label' => 'Lead source', 'type' => 'string'],
                'lead_status' => ['label' => 'Lead status', 'type' => 'string'],
                'rating' => ['label' => 'Rating', 'type' => 'string'],
                'converted' => ['label' => 'Converted', 'type' => 'boolean'],
                'owner_name' => ['label' => 'Owner', 'type' => 'string', 'relation' => 'owner'],
                'created_at' => ['label' => 'Created on', 'type' => 'datetime'],
                'created_month' => ['label' => 'Created month', 'type' => 'string', 'computed' => true],
                'record_count' => ['label' => 'Count', 'type' => 'number', 'aggregate' => 'count'],
                'conversion_rate' => ['label' => 'Conversion rate', 'type' => 'number', 'aggregate' => 'conversion'],
            ],
            Report::OBJECT_ACCOUNT => [
                'id' => ['label' => 'ID', 'type' => 'number'],
                'name' => ['label' => 'Account name', 'type' => 'string'],
                'type' => ['label' => 'Type', 'type' => 'string'],
                'industry' => ['label' => 'Industry', 'type' => 'string'],
                'phone' => ['label' => 'Phone', 'type' => 'string'],
                'website' => ['label' => 'Website', 'type' => 'string'],
                'owner_name' => ['label' => 'Owner', 'type' => 'string', 'relation' => 'owner'],
                'created_at' => ['label' => 'Created on', 'type' => 'datetime'],
                'record_count' => ['label' => 'Count', 'type' => 'number', 'aggregate' => 'count'],
            ],
            Report::OBJECT_CONTACT => [
                'id' => ['label' => 'ID', 'type' => 'number'],
                'first_name' => ['label' => 'First name', 'type' => 'string'],
                'last_name' => ['label' => 'Last name', 'type' => 'string'],
                'email' => ['label' => 'Email', 'type' => 'string'],
                'phone' => ['label' => 'Phone', 'type' => 'string'],
                'title' => ['label' => 'Title', 'type' => 'string'],
                'owner_name' => ['label' => 'Owner', 'type' => 'string', 'relation' => 'owner'],
                'created_at' => ['label' => 'Created on', 'type' => 'datetime'],
                'record_count' => ['label' => 'Count', 'type' => 'number', 'aggregate' => 'count'],
            ],
            Report::OBJECT_OPPORTUNITY => [
                'id' => ['label' => 'ID', 'type' => 'number'],
                'name' => ['label' => 'Opportunity name', 'type' => 'string'],
                'stage' => ['label' => 'Stage', 'type' => 'string'],
                'amount' => ['label' => 'Amount', 'type' => 'number'],
                'close_date' => ['label' => 'Close date', 'type' => 'date'],
                'lead_source' => ['label' => 'Lead source', 'type' => 'string'],
                'is_won' => ['label' => 'Won', 'type' => 'boolean'],
                'is_closed' => ['label' => 'Closed', 'type' => 'boolean'],
                'owner_name' => ['label' => 'Owner', 'type' => 'string', 'relation' => 'owner'],
                'account_name' => ['label' => 'Account', 'type' => 'string', 'relation' => 'account'],
                'created_at' => ['label' => 'Created on', 'type' => 'datetime'],
                'record_count' => ['label' => 'Count', 'type' => 'number', 'aggregate' => 'count'],
                'total_amount' => ['label' => 'Total amount', 'type' => 'number', 'aggregate' => 'sum:amount'],
                'avg_amount' => ['label' => 'Avg amount', 'type' => 'number', 'aggregate' => 'avg:amount'],
                'avg_deal_length' => ['label' => 'Avg deal length (days)', 'type' => 'number', 'aggregate' => 'avg_deal_length'],
                'expected_revenue' => ['label' => 'Expected revenue', 'type' => 'number', 'aggregate' => 'sum_expected'],
            ],
            Report::OBJECT_CASE => [
                'id' => ['label' => 'ID', 'type' => 'number'],
                'case_number' => ['label' => 'Case number', 'type' => 'string'],
                'subject' => ['label' => 'Subject', 'type' => 'string'],
                'status' => ['label' => 'Status', 'type' => 'string'],
                'priority' => ['label' => 'Priority', 'type' => 'string'],
                'origin' => ['label' => 'Origin', 'type' => 'string'],
                'type' => ['label' => 'Type', 'type' => 'string'],
                'owner_name' => ['label' => 'Owner', 'type' => 'string', 'relation' => 'owner'],
                'created_at' => ['label' => 'Created on', 'type' => 'datetime'],
                'created_month' => ['label' => 'Created month', 'type' => 'string', 'computed' => true],
                'record_count' => ['label' => 'Count', 'type' => 'number', 'aggregate' => 'count'],
                'avg_age_days' => ['label' => 'Avg age (days)', 'type' => 'number', 'aggregate' => 'avg_case_age'],
            ],
            default => throw new InvalidArgumentException("Unknown report object [{$objectType}]."),
        };
    }

    /**
     * Columns selectable in the builder (excludes aggregate-only helpers).
     *
     * @return list<array{key: string, label: string, type: string}>
     */
    public static function selectableColumns(string $objectType): array
    {
        $columns = [];

        foreach (self::fields($objectType) as $key => $meta) {
            if (isset($meta['aggregate']) || ($meta['computed'] ?? false)) {
                continue;
            }

            if ($key === 'id') {
                continue;
            }

            $columns[] = [
                'key' => $key,
                'label' => $meta['label'],
                'type' => $meta['type'],
            ];
        }

        return $columns;
    }

    /**
     * @return list<array{key: string, label: string}>
     */
    public static function groupableFields(string $objectType): array
    {
        $allowed = match ($objectType) {
            Report::OBJECT_LEAD => ['lead_source', 'lead_status', 'rating', 'owner_name', 'created_month', 'converted'],
            Report::OBJECT_ACCOUNT => ['type', 'industry', 'owner_name'],
            Report::OBJECT_CONTACT => ['title', 'owner_name'],
            Report::OBJECT_OPPORTUNITY => ['stage', 'lead_source', 'owner_name', 'is_won', 'is_closed'],
            Report::OBJECT_CASE => ['status', 'priority', 'origin', 'type', 'owner_name', 'created_month'],
            default => [],
        };

        $fields = self::fields($objectType);
        $out = [];

        foreach ($allowed as $key) {
            if (! isset($fields[$key])) {
                continue;
            }

            $out[] = ['key' => $key, 'label' => $fields[$key]['label']];
        }

        return $out;
    }

    /**
     * @return list<string>
     */
    public static function operators(): array
    {
        return [
            'equals',
            'not_equals',
            'contains',
            'gt',
            'gte',
            'lt',
            'lte',
            'this_fy',
            'is_true',
            'is_false',
        ];
    }

    public static function userCanAccessObject(User $user, string $objectType): bool
    {
        return $user->can('viewAny', self::modelClass($objectType));
    }

    /**
     * @return list<array{key: string, label: string}>
     */
    public static function objectsForUser(User $user): array
    {
        $items = [];

        foreach (self::catalog() as $item) {
            if (! $user->can('viewAny', $item['model'])) {
                continue;
            }

            $items[] = ['key' => $item['key'], 'label' => $item['label']];
        }

        return $items;
    }

    public static function recordUrl(string $objectType, int|string $id): ?string
    {
        return match ($objectType) {
            Report::OBJECT_LEAD => route('leads.show', $id),
            Report::OBJECT_ACCOUNT => route('accounts.show', $id),
            Report::OBJECT_CONTACT => route('contacts.show', $id),
            Report::OBJECT_OPPORTUNITY => route('opportunities.show', $id),
            Report::OBJECT_CASE => route('cases.show', $id),
            default => null,
        };
    }
}
