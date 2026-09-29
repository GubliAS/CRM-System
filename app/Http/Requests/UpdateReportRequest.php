<?php

namespace App\Http\Requests;

use App\Models\Report;

class UpdateReportRequest extends StoreReportRequest
{
    public function authorize(): bool
    {
        /** @var Report|null $report */
        $report = $this->route('report');

        return $report instanceof Report
            && ($this->user()?->can('update', $report) ?? false);
    }
}
