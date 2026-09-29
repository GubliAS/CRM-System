<?php

namespace App\Http\Controllers;

use App\Actions\Home\DismissAssistantRecommendation;
use App\Http\Requests\DismissAssistantRecommendationRequest;
use App\Support\HomeDashboard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(Request $request, HomeDashboard $dashboard): Response
    {
        return Inertia::render('Home', $dashboard->payload($request->user()));
    }

    public function dismiss(
        DismissAssistantRecommendationRequest $request,
        DismissAssistantRecommendation $dismiss,
    ): RedirectResponse {
        $record = $request->recommendable();

        if ($record === null) {
            return redirect()
                ->route('home')
                ->with('error', 'That recommendation is no longer available.');
        }

        $dismiss->handle(
            $request->user(),
            $request->string('rule')->toString(),
            $record,
        );

        return redirect()->route('home')->with('success', 'Recommendation dismissed.');
    }
}
