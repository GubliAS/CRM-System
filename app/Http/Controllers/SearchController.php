<?php

namespace App\Http\Controllers;

use App\Actions\Search\RecordSearchHistory;
use App\Http\Requests\SearchIndexRequest;
use App\Http\Requests\SearchSuggestRequest;
use App\Models\SearchHistory;
use App\Support\GlobalSearch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SearchController extends Controller
{
    public function suggest(
        SearchSuggestRequest $request,
        GlobalSearch $search,
    ): JsonResponse {
        $user = $request->user();
        $query = trim((string) $request->validated('q', ''));

        $recentSearches = SearchHistory::query()
            ->where('user_id', $user->id)
            ->latest('updated_at')
            ->limit(RecordSearchHistory::KEEP)
            ->pluck('query')
            ->all();

        if (mb_strlen($query) < 2) {
            return response()->json([
                'query' => $query,
                'results' => [],
                'recent_searches' => $recentSearches,
            ]);
        }

        return response()->json([
            'query' => $query,
            'results' => $search->suggest($user, $query),
            'recent_searches' => $recentSearches,
        ]);
    }

    public function index(
        SearchIndexRequest $request,
        GlobalSearch $search,
        RecordSearchHistory $recordSearchHistory,
    ): Response {
        $user = $request->user();
        $query = trim((string) $request->validated('q', ''));
        $type = $request->validated('type') ?: 'all';
        $perPage = $this->perPage($request);

        $results = null;
        $groups = null;
        $totals = null;

        if (mb_strlen($query) >= 2) {
            $recordSearchHistory->handle($user, $query);
            $found = $search->search($user, $query, $type, $perPage);

            if ($type === 'all') {
                $groups = $found['groups'];
                $totals = $found['totals'];
            } else {
                $results = $found;
            }
        }

        $recentSearches = SearchHistory::query()
            ->where('user_id', $user->id)
            ->latest('updated_at')
            ->limit(RecordSearchHistory::KEEP)
            ->pluck('query')
            ->all();

        return Inertia::render('Search/Index', [
            'query' => $query,
            'type' => $type,
            'types' => $search->searchableTypes($user),
            'results' => $results,
            'groups' => $groups,
            'totals' => $totals,
            'recentSearches' => $recentSearches,
            'filters' => [
                'q' => $query,
                'type' => $type,
                'per_page' => $perPage,
            ],
        ]);
    }

    private function perPage(Request $request): int
    {
        $value = $request->query('per_page', 25);

        if (! is_numeric($value)) {
            return 25;
        }

        $perPage = (int) $value;

        if ($perPage < 1) {
            return 25;
        }

        return min($perPage, 200);
    }
}
