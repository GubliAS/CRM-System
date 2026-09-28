<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use Illuminate\Http\JsonResponse;

class LeadShowController extends Controller
{
    public function __invoke(Lead $lead): JsonResponse
    {
        $this->authorize('view', $lead);

        return response()->json([
            'id' => $lead->id,
        ]);
    }
}
