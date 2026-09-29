<?php

namespace App\Support;

use App\Models\Account;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\User;

class ImportObjects
{
    public const OBJECT_LEAD = 'lead';

    public const OBJECT_ACCOUNT = 'account';

    public const OBJECT_CONTACT = 'contact';

    public const OBJECT_OPPORTUNITY = 'opportunity';

    /**
     * @var list<string>
     */
    public const OBJECT_TYPES = [
        self::OBJECT_LEAD,
        self::OBJECT_ACCOUNT,
        self::OBJECT_CONTACT,
        self::OBJECT_OPPORTUNITY,
    ];

    /**
     * @return list<array{key: string, label: string}>
     */
    public static function objectsForUser(User $user): array
    {
        $objects = [];

        if ($user->can('create', Lead::class)) {
            $objects[] = ['key' => self::OBJECT_LEAD, 'label' => 'Leads'];
        }

        if ($user->can('create', Account::class)) {
            $objects[] = ['key' => self::OBJECT_ACCOUNT, 'label' => 'Accounts'];
        }

        if ($user->can('create', Contact::class)) {
            $objects[] = ['key' => self::OBJECT_CONTACT, 'label' => 'Contacts'];
        }

        if ($user->can('create', Opportunity::class)) {
            $objects[] = ['key' => self::OBJECT_OPPORTUNITY, 'label' => 'Opportunities'];
        }

        return $objects;
    }

    /**
     * @return list<array{key: string, label: string, required: bool}>
     */
    public static function fields(string $objectType): array
    {
        return match ($objectType) {
            self::OBJECT_LEAD => [
                ['key' => 'last_name', 'label' => 'Last name', 'required' => true],
                ['key' => 'company', 'label' => 'Company', 'required' => true],
                ['key' => 'first_name', 'label' => 'First name', 'required' => false],
                ['key' => 'email', 'label' => 'Email', 'required' => false],
                ['key' => 'phone', 'label' => 'Phone', 'required' => false],
                ['key' => 'mobile', 'label' => 'Mobile', 'required' => false],
                ['key' => 'title', 'label' => 'Title', 'required' => false],
                ['key' => 'lead_status', 'label' => 'Lead status', 'required' => false],
                ['key' => 'lead_source', 'label' => 'Lead source', 'required' => false],
                ['key' => 'rating', 'label' => 'Rating', 'required' => false],
                ['key' => 'industry', 'label' => 'Industry', 'required' => false],
                ['key' => 'website', 'label' => 'Website', 'required' => false],
                ['key' => 'street', 'label' => 'Street', 'required' => false],
                ['key' => 'city', 'label' => 'City', 'required' => false],
                ['key' => 'state', 'label' => 'State', 'required' => false],
                ['key' => 'postal_code', 'label' => 'Postal code', 'required' => false],
                ['key' => 'country', 'label' => 'Country', 'required' => false],
                ['key' => 'description', 'label' => 'Description', 'required' => false],
            ],
            self::OBJECT_ACCOUNT => [
                ['key' => 'name', 'label' => 'Account name', 'required' => true],
                ['key' => 'phone', 'label' => 'Phone', 'required' => false],
                ['key' => 'fax', 'label' => 'Fax', 'required' => false],
                ['key' => 'website', 'label' => 'Website', 'required' => false],
                ['key' => 'type', 'label' => 'Type', 'required' => false],
                ['key' => 'industry', 'label' => 'Industry', 'required' => false],
                ['key' => 'employees', 'label' => 'Employees', 'required' => false],
                ['key' => 'annual_revenue', 'label' => 'Annual revenue', 'required' => false],
                ['key' => 'billing_street', 'label' => 'Billing street', 'required' => false],
                ['key' => 'billing_city', 'label' => 'Billing city', 'required' => false],
                ['key' => 'billing_state', 'label' => 'Billing state', 'required' => false],
                ['key' => 'billing_postal_code', 'label' => 'Billing postal code', 'required' => false],
                ['key' => 'billing_country', 'label' => 'Billing country', 'required' => false],
                ['key' => 'description', 'label' => 'Description', 'required' => false],
            ],
            self::OBJECT_CONTACT => [
                ['key' => 'last_name', 'label' => 'Last name', 'required' => true],
                ['key' => 'account_name', 'label' => 'Account name', 'required' => true],
                ['key' => 'first_name', 'label' => 'First name', 'required' => false],
                ['key' => 'email', 'label' => 'Email', 'required' => false],
                ['key' => 'phone', 'label' => 'Phone', 'required' => false],
                ['key' => 'mobile', 'label' => 'Mobile', 'required' => false],
                ['key' => 'title', 'label' => 'Title', 'required' => false],
                ['key' => 'department', 'label' => 'Department', 'required' => false],
                ['key' => 'lead_source', 'label' => 'Lead source', 'required' => false],
                ['key' => 'mailing_street', 'label' => 'Mailing street', 'required' => false],
                ['key' => 'mailing_city', 'label' => 'Mailing city', 'required' => false],
                ['key' => 'mailing_state', 'label' => 'Mailing state', 'required' => false],
                ['key' => 'mailing_postal_code', 'label' => 'Mailing postal code', 'required' => false],
                ['key' => 'mailing_country', 'label' => 'Mailing country', 'required' => false],
                ['key' => 'description', 'label' => 'Description', 'required' => false],
            ],
            self::OBJECT_OPPORTUNITY => [
                ['key' => 'name', 'label' => 'Opportunity name', 'required' => true],
                ['key' => 'account_name', 'label' => 'Account name', 'required' => true],
                ['key' => 'amount', 'label' => 'Amount', 'required' => false],
                ['key' => 'close_date', 'label' => 'Close date', 'required' => true],
                ['key' => 'stage', 'label' => 'Stage', 'required' => false],
                ['key' => 'type', 'label' => 'Type', 'required' => false],
                ['key' => 'lead_source', 'label' => 'Lead source', 'required' => false],
                ['key' => 'next_step', 'label' => 'Next step', 'required' => false],
                ['key' => 'description', 'label' => 'Description', 'required' => false],
            ],
            default => [],
        };
    }

    /**
     * @return list<string>
     */
    public static function matchKeys(string $objectType): array
    {
        return match ($objectType) {
            self::OBJECT_LEAD => ['email'],
            self::OBJECT_ACCOUNT => ['name'],
            self::OBJECT_CONTACT => ['email'],
            self::OBJECT_OPPORTUNITY => ['name', 'account_name'],
            default => [],
        };
    }
}
