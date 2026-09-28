<?php

namespace App\Support;

use App\Models\Account;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\SupportCase;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class RelatedRecords
{
    /**
     * @var array<string, class-string<Model>>
     */
    public const TASK_TYPES = [
        'account' => Account::class,
        'contact' => Contact::class,
        'lead' => Lead::class,
        'opportunity' => Opportunity::class,
        'case' => SupportCase::class,
    ];

    /**
     * @var array<string, class-string<Model>>
     */
    public const EVENT_TYPES = [
        'account' => Account::class,
        'contact' => Contact::class,
        'lead' => Lead::class,
        'opportunity' => Opportunity::class,
    ];

    /**
     * @var array<string, string>
     */
    private const LABELS = [
        'account' => 'Account',
        'contact' => 'Contact',
        'lead' => 'Lead',
        'opportunity' => 'Opportunity',
        'case' => 'Case',
    ];

    /**
     * @param  array<string, class-string<Model>>  $types
     * @return list<array{value: string, label: string}>
     */
    public static function typeOptions(array $types): array
    {
        $options = [];

        foreach (array_keys($types) as $key) {
            $options[] = [
                'value' => $key,
                'label' => self::LABELS[$key],
            ];
        }

        return $options;
    }

    /**
     * @param  array<string, class-string<Model>>  $types
     */
    public static function keyFor(?string $class, array $types): ?string
    {
        if ($class === null || $class === '') {
            return null;
        }

        $key = array_search($class, $types, true);

        return $key === false ? null : $key;
    }

    /**
     * @param  array<string, class-string<Model>>  $types
     * @return array<string, list<array{id: int, label: string}>>
     */
    public static function recordOptions(User $user, array $types): array
    {
        $options = [];

        foreach ($types as $key => $class) {
            $options[$key] = $class::query()
                ->visibleTo($user)
                ->orderBy(self::orderColumn($class))
                ->orderBy('id')
                ->get(self::columns($class))
                ->map(fn (Model $record): array => [
                    'id' => (int) $record->getKey(),
                    'label' => self::label($record) ?? '#'.$record->getKey(),
                ])
                ->all();
        }

        return $options;
    }

    /**
     * @param  array<string, class-string<Model>>  $types
     * @return array{
     *     assignees: list<array{id: int, name: string}>,
     *     contacts: list<array{id: int, label: string}>,
     *     relatedTypes: list<array{value: string, label: string}>,
     *     relatedRecords: array<string, list<array{id: int, label: string}>>
     * }
     */
    public static function formPayload(User $user, array $types): array
    {
        return [
            'assignees' => User::query()
                ->orderBy('name')
                ->orderBy('id')
                ->get(['id', 'name'])
                ->map(fn (User $person): array => [
                    'id' => (int) $person->id,
                    'name' => $person->name,
                ])
                ->all(),
            'contacts' => Contact::query()
                ->visibleTo($user)
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->orderBy('id')
                ->get(['id', 'first_name', 'last_name'])
                ->map(fn (Contact $contact): array => [
                    'id' => (int) $contact->id,
                    'label' => self::label($contact) ?? 'Contact #'.$contact->id,
                ])
                ->all(),
            'relatedTypes' => self::typeOptions($types),
            'relatedRecords' => self::recordOptions($user, $types),
        ];
    }

    public static function label(?Model $record): ?string
    {
        if ($record === null) {
            return null;
        }

        return match ($record::class) {
            Account::class, Opportunity::class => $record->getAttribute('name'),
            Contact::class => self::person($record),
            Lead::class => self::person($record) ?: $record->getAttribute('company'),
            SupportCase::class => trim(($record->getAttribute('case_number') ?? '').' '.$record->getAttribute('subject')),
            default => null,
        };
    }

    public static function showRoute(?Model $record): ?string
    {
        if ($record === null) {
            return null;
        }

        return match ($record::class) {
            Account::class => route('accounts.show', $record),
            Contact::class => route('contacts.show', $record),
            Lead::class => route('leads.show', $record),
            Opportunity::class => route('opportunities.show', $record),
            SupportCase::class => route('cases.show', $record),
            default => null,
        };
    }

    /**
     * @param  class-string<Model>  $class
     * @return list<string>
     */
    private static function columns(string $class): array
    {
        return match ($class) {
            Account::class => ['id', 'name'],
            Contact::class => ['id', 'first_name', 'last_name'],
            Lead::class => ['id', 'first_name', 'last_name', 'company'],
            Opportunity::class => ['id', 'name'],
            SupportCase::class => ['id', 'case_number', 'subject'],
            default => ['id'],
        };
    }

    /**
     * @param  class-string<Model>  $class
     */
    private static function orderColumn(string $class): string
    {
        return match ($class) {
            Contact::class, Lead::class => 'last_name',
            SupportCase::class => 'subject',
            default => 'name',
        };
    }

    private static function person(Model $record): string
    {
        return trim(implode(' ', array_filter([
            $record->getAttribute('first_name'),
            $record->getAttribute('last_name'),
        ])));
    }
}
