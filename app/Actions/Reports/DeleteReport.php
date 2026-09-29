<?php

namespace App\Actions\Reports;

use App\Models\Report;

class DeleteReport
{
    public function handle(Report $report): void
    {
        $report->delete();
    }
}
