<?php

namespace App\Support;

class Picklists
{
    /**
     * @var list<string>
     */
    public const ACCOUNT_TYPES = [
        'Customer',
        'Prospect',
        'Partner',
        'Other',
    ];

    /**
     * @var list<string>
     */
    public const SALUTATIONS = [
        'Mr.',
        'Ms.',
        'Mrs.',
        'Dr.',
        'Prof.',
    ];

    /**
     * @var list<string>
     */
    public const LEAD_STATUSES = [
        'New',
        'Working',
        'Nurturing',
        'Qualified',
        'Unqualified',
        'Converted',
    ];

    /**
     * Editable lead statuses (Converted is set only by conversion).
     *
     * @var list<string>
     */
    public const LEAD_STATUSES_EDITABLE = [
        'New',
        'Working',
        'Nurturing',
        'Qualified',
        'Unqualified',
    ];

    /**
     * @var list<string>
     */
    public const LEAD_SOURCES = [
        'Advertisement',
        'External Referral',
        'Social',
        'Trade Show',
        'Web',
        'Other',
    ];

    /**
     * @var list<string>
     */
    public const LEAD_RATINGS = [
        'Hot',
        'Warm',
        'Cold',
    ];

    /**
     * @var list<string>
     */
    public const CASE_STATUSES = [
        'New',
        'Working',
        'Escalated',
        'Closed',
    ];

    /**
     * Editable case statuses (Closed is set by Close / cleared by Reopen).
     *
     * @var list<string>
     */
    public const CASE_STATUSES_OPEN = [
        'New',
        'Working',
        'Escalated',
    ];

    /**
     * @var list<string>
     */
    public const CASE_ORIGINS = [
        'Phone',
        'Email',
        'Web',
        'Chat',
    ];

    /**
     * @var list<string>
     */
    public const CASE_PRIORITIES = [
        'High',
        'Medium',
        'Low',
    ];

    /**
     * @var list<string>
     */
    public const CASE_TYPES = [
        'Question',
        'Problem',
        'Feature Request',
    ];

    /**
     * @var list<string>
     */
    public const CASE_REASONS = [
        'Installation',
        'Equipment Complexity',
        'Performance',
        'Breakdown',
        'Usage / How-to',
        'Other',
    ];

    /**
     * @var list<string>
     */
    public const OPPORTUNITY_TYPES = [
        'New Business',
        'Existing Business',
        'Renewal',
    ];
}
