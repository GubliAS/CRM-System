<?php

namespace App\Http\Requests;

use App\Models\Dashboard;

class UpdateDashboardRequest extends StoreDashboardRequest
{
    public function authorize(): bool
    {
        $dashboard = $this->route('dashboard');

        return $dashboard instanceof Dashboard
            && ($this->user()?->can('update', $dashboard) ?? false);
    }
}
