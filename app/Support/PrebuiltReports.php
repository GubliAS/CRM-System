<?php

namespace App\Support;

use App\Models\Report;

final class PrebuiltReports
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function definitions(): array
    {
        $year = (int) now()->year;

        return [
            [
                'system_key' => 'leads_by_source_this_fy',
                'name' => 'Leads by Source This FY',
                'description' => "Lead count by source for calendar year {$year}.",
                'object_type' => Report::OBJECT_LEAD,
                'columns' => ['lead_source', 'record_count'],
                'filters' => [
                    ['field' => 'created_at', 'operator' => 'this_fy', 'value' => null],
                ],
                'group_by' => ['lead_source'],
                'chart' => ['type' => 'bar', 'label' => 'lead_source', 'value' => 'record_count'],
            ],
            [
                'system_key' => 'leads_converted_this_fy',
                'name' => 'Leads Converted This FY',
                'description' => "Converted leads created in calendar year {$year}.",
                'object_type' => Report::OBJECT_LEAD,
                'columns' => ['last_name', 'company', 'lead_source', 'owner_name', 'created_at'],
                'filters' => [
                    ['field' => 'created_at', 'operator' => 'this_fy', 'value' => null],
                    ['field' => 'converted', 'operator' => 'is_true', 'value' => null],
                ],
                'group_by' => [],
                'chart' => null,
            ],
            [
                'system_key' => 'leads_created_by_month',
                'name' => 'Leads Created by Month',
                'description' => 'Lead volume grouped by created month.',
                'object_type' => Report::OBJECT_LEAD,
                'columns' => ['created_month', 'record_count'],
                'filters' => [],
                'group_by' => ['created_month'],
                'chart' => ['type' => 'bar', 'label' => 'created_month', 'value' => 'record_count'],
            ],
            [
                'system_key' => 'new_leads_this_fy_by_owner',
                'name' => 'New Leads This FY By Owner',
                'description' => "New leads created in calendar year {$year}, by owner.",
                'object_type' => Report::OBJECT_LEAD,
                'columns' => ['owner_name', 'record_count'],
                'filters' => [
                    ['field' => 'created_at', 'operator' => 'this_fy', 'value' => null],
                ],
                'group_by' => ['owner_name'],
                'chart' => ['type' => 'bar', 'label' => 'owner_name', 'value' => 'record_count'],
            ],
            [
                'system_key' => 'conversion_of_new_leads_this_fy',
                'name' => 'Conversion of New Leads This FY',
                'description' => "Conversion rate of leads created in calendar year {$year}, by owner.",
                'object_type' => Report::OBJECT_LEAD,
                'columns' => ['owner_name', 'record_count', 'conversion_rate'],
                'filters' => [
                    ['field' => 'created_at', 'operator' => 'this_fy', 'value' => null],
                ],
                'group_by' => ['owner_name'],
                'chart' => ['type' => 'bar', 'label' => 'owner_name', 'value' => 'conversion_rate'],
            ],
            [
                'system_key' => 'all_pipeline_current_year',
                'name' => 'All Pipeline — Current Year',
                'description' => "Opportunity pipeline by stage for close dates in {$year}.",
                'object_type' => Report::OBJECT_OPPORTUNITY,
                'columns' => ['stage', 'record_count', 'total_amount'],
                'filters' => [
                    ['field' => 'close_date', 'operator' => 'this_fy', 'value' => null],
                    ['field' => 'archived_at', 'operator' => 'is_null', 'value' => null],
                ],
                'group_by' => ['stage'],
                'chart' => ['type' => 'bar', 'label' => 'stage', 'value' => 'total_amount'],
            ],
            [
                'system_key' => 'potential_revenue_source_current_year',
                'name' => 'Potential Revenue Source — Current Year',
                'description' => "Opportunity amount by lead source for close dates in {$year}.",
                'object_type' => Report::OBJECT_OPPORTUNITY,
                'columns' => ['lead_source', 'record_count', 'total_amount'],
                'filters' => [
                    ['field' => 'close_date', 'operator' => 'this_fy', 'value' => null],
                    ['field' => 'archived_at', 'operator' => 'is_null', 'value' => null],
                ],
                'group_by' => ['lead_source'],
                'chart' => ['type' => 'bar', 'label' => 'lead_source', 'value' => 'total_amount'],
            ],
            [
                'system_key' => 'avg_deal_size_current_fy',
                'name' => 'Avg Deal Size — Current FY',
                'description' => "Average opportunity amount for close dates in {$year}.",
                'object_type' => Report::OBJECT_OPPORTUNITY,
                'columns' => ['record_count', 'avg_amount'],
                'filters' => [
                    ['field' => 'close_date', 'operator' => 'this_fy', 'value' => null],
                    ['field' => 'archived_at', 'operator' => 'is_null', 'value' => null],
                ],
                'group_by' => [],
                'chart' => ['type' => 'metric', 'label' => 'avg_amount', 'value' => 'avg_amount'],
                'summary' => true,
            ],
            [
                'system_key' => 'avg_deal_length',
                'name' => 'Avg. Deal Length',
                'description' => 'Average days from create to close for Closed Won opportunities.',
                'object_type' => Report::OBJECT_OPPORTUNITY,
                'columns' => ['record_count', 'avg_deal_length'],
                'filters' => [
                    ['field' => 'is_won', 'operator' => 'is_true', 'value' => null],
                    ['field' => 'archived_at', 'operator' => 'is_null', 'value' => null],
                ],
                'group_by' => [],
                'chart' => ['type' => 'metric', 'label' => 'avg_deal_length', 'value' => 'avg_deal_length'],
                'summary' => true,
            ],
            [
                'system_key' => 'closed_won_opportunities_this_fy',
                'name' => 'Closed (Won) Opportunities This FY',
                'description' => "Closed Won opportunities with close date in {$year}.",
                'object_type' => Report::OBJECT_OPPORTUNITY,
                'columns' => ['name', 'account_name', 'amount', 'close_date', 'owner_name'],
                'filters' => [
                    ['field' => 'close_date', 'operator' => 'this_fy', 'value' => null],
                    ['field' => 'is_won', 'operator' => 'is_true', 'value' => null],
                    ['field' => 'archived_at', 'operator' => 'is_null', 'value' => null],
                ],
                'group_by' => [],
                'chart' => null,
            ],
            [
                'system_key' => 'closed_won_opportunities_by_owner',
                'name' => 'Closed Won Opportunities by Owner',
                'description' => "Closed Won opportunity totals by owner for close dates in {$year}.",
                'object_type' => Report::OBJECT_OPPORTUNITY,
                'columns' => ['owner_name', 'record_count', 'total_amount'],
                'filters' => [
                    ['field' => 'close_date', 'operator' => 'this_fy', 'value' => null],
                    ['field' => 'is_won', 'operator' => 'is_true', 'value' => null],
                    ['field' => 'archived_at', 'operator' => 'is_null', 'value' => null],
                ],
                'group_by' => ['owner_name'],
                'chart' => ['type' => 'bar', 'label' => 'owner_name', 'value' => 'total_amount'],
            ],
            [
                'system_key' => 'average_case_age',
                'name' => 'Average Case Age',
                'description' => 'Average age in days for cases (open cases use today).',
                'object_type' => Report::OBJECT_CASE,
                'columns' => ['record_count', 'avg_age_days'],
                'filters' => [],
                'group_by' => [],
                'chart' => ['type' => 'metric', 'label' => 'avg_age_days', 'value' => 'avg_age_days'],
                'summary' => true,
            ],
            [
                'system_key' => 'monthly_case_volume_by_origin',
                'name' => 'Monthly Case Volume by Origin',
                'description' => 'Case volume by origin and created month.',
                'object_type' => Report::OBJECT_CASE,
                'columns' => ['origin', 'created_month', 'record_count'],
                'filters' => [],
                'group_by' => ['origin', 'created_month'],
                'chart' => ['type' => 'bar', 'label' => 'origin', 'value' => 'record_count'],
            ],
        ];
    }
}
