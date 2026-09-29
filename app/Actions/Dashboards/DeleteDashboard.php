<?php

namespace App\Actions\Dashboards;

use App\Models\Dashboard;

class DeleteDashboard
{
    public function handle(Dashboard $dashboard): void
    {
        $dashboard->delete();
    }
}
