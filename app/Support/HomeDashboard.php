<?php

namespace App\Support;

use App\Models\Account;
use App\Models\AssistantRecommendationDismissal;
use App\Models\Event;
use App\Models\Opportunity;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class HomeDashboard
{
    public const KEY_DEALS_LIMIT = 8;

    public const ASSISTANT_LIMIT = 10;

    public const NEAR_CLOSE_DAYS = 14;

    public const STALE_UPDATE_DAYS = 7;

    public const INACTIVE_ACCOUNT_DAYS = 30;

    /**
     * @return array<string, mixed>
     */
    public function payload(User $user): array
    {
        $year = (int) now()->year;
        $yearStart = Carbon::create($year, 1, 1)->startOfDay();
        $yearEnd = Carbon::create($year, 12, 31)->endOfDay();
        $today = now()->toDateString();

        $pipeline = $this->pipelineFunnel($user, $yearStart, $yearEnd);
        $revenueBySource = $this->revenueByLeadSource($user, $yearStart, $yearEnd);

        return [
            'year' => $year,
            'pipeline' => $pipeline,
            'pipelineTotal' => collect($pipeline)->sum('value'),
            'revenueBySource' => $revenueBySource,
            'revenueBySourceTotal' => collect($revenueBySource)->sum('value'),
            'tasksDueToday' => $this->tasksDueToday($user, $today),
            'eventsToday' => $this->eventsToday($user, $today),
            'keyOpportunities' => $this->keyOpenOpportunities($user),
            'recommendations' => $this->recommendations($user),
        ];
    }

    /**
     * @return list<array{stage: string, count: int, value: float, href: string}>
     */
    private function pipelineFunnel(User $user, Carbon $yearStart, Carbon $yearEnd): array
    {
        $rows = Opportunity::query()
            ->visibleTo($user)
            ->whereNull('archived_at')
            ->whereBetween('close_date', [$yearStart->toDateString(), $yearEnd->toDateString()])
            ->selectRaw('stage, count(*) as aggregate_count, coalesce(sum(amount), 0) as aggregate_value')
            ->groupBy('stage')
            ->get()
            ->keyBy('stage');

        $funnel = [];

        foreach (array_keys(OpportunityStage::PROBABILITIES) as $stage) {
            $row = $rows->get($stage);

            $funnel[] = [
                'stage' => $stage,
                'count' => (int) ($row?->aggregate_count ?? 0),
                'value' => round((float) ($row?->aggregate_value ?? 0), 2),
                'href' => route('opportunities.index', ['stage' => $stage, 'year' => $yearStart->year]),
            ];
        }

        return $funnel;
    }

    /**
     * @return list<array{source: string, count: int, value: float}>
     */
    private function revenueByLeadSource(User $user, Carbon $yearStart, Carbon $yearEnd): array
    {
        $rows = Opportunity::query()
            ->visibleTo($user)
            ->whereNull('archived_at')
            ->whereBetween('close_date', [$yearStart->toDateString(), $yearEnd->toDateString()])
            ->selectRaw('lead_source, count(*) as aggregate_count, coalesce(sum(amount), 0) as aggregate_value')
            ->groupBy('lead_source')
            ->orderByDesc('aggregate_value')
            ->orderBy('lead_source')
            ->get();

        return $rows
            ->map(fn ($row): array => [
                'source' => ($row->lead_source === null || $row->lead_source === '')
                    ? '(None)'
                    : (string) $row->lead_source,
                'count' => (int) $row->aggregate_count,
                'value' => round((float) $row->aggregate_value, 2),
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function tasksDueToday(User $user, string $today): array
    {
        return Task::query()
            ->visibleTo($user)
            ->whereDate('due_on', $today)
            ->where('status', '!=', 'Completed')
            ->with(['related'])
            ->orderBy('subject')
            ->orderBy('id')
            ->get(['id', 'subject', 'due_on', 'status', 'priority', 'related_type', 'related_id'])
            ->map(fn (Task $task): array => [
                'id' => $task->id,
                'subject' => $task->subject,
                'due_on' => optional($task->due_on)?->toDateString(),
                'status' => $task->status,
                'priority' => $task->priority,
                'related_label' => $this->relatedLabel($task->related),
                'can_complete' => $user->can('update', $task),
            ])
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function eventsToday(User $user, string $today): array
    {
        $dayStart = Carbon::parse($today)->startOfDay();
        $dayEnd = Carbon::parse($today)->endOfDay();

        return Event::query()
            ->visibleTo($user)
            ->where('starts_at', '<=', $dayEnd)
            ->where('ends_at', '>=', $dayStart)
            ->with(['related'])
            ->orderBy('starts_at')
            ->orderBy('id')
            ->get(['id', 'subject', 'starts_at', 'ends_at', 'all_day', 'location', 'related_type', 'related_id'])
            ->map(fn (Event $event): array => [
                'id' => $event->id,
                'subject' => $event->subject,
                'starts_at' => optional($event->starts_at)?->toIso8601String(),
                'ends_at' => optional($event->ends_at)?->toIso8601String(),
                'all_day' => (bool) $event->all_day,
                'location' => $event->location,
                'related_label' => $this->relatedLabel($event->related),
            ])
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function keyOpenOpportunities(User $user): array
    {
        return Opportunity::query()
            ->visibleTo($user)
            ->whereNull('archived_at')
            ->where('is_closed', false)
            ->with(['account:id,name'])
            ->orderByDesc('amount')
            ->orderBy('close_date')
            ->orderBy('id')
            ->limit(self::KEY_DEALS_LIMIT)
            ->get(['id', 'name', 'account_id', 'amount', 'close_date', 'stage'])
            ->map(fn (Opportunity $opportunity): array => [
                'id' => $opportunity->id,
                'name' => $opportunity->name,
                'account' => $opportunity->account?->only(['id', 'name']),
                'amount' => $opportunity->amount,
                'close_date' => optional($opportunity->close_date)?->toDateString(),
                'stage' => $opportunity->stage,
                'url' => route('opportunities.show', $opportunity),
            ])
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function recommendations(User $user): array
    {
        $dismissed = AssistantRecommendationDismissal::query()
            ->where('user_id', $user->id)
            ->get(['rule', 'recommendable_type', 'recommendable_id']);

        $inactiveAccounts = $this->inactiveAccounts($user, $dismissed);
        $staleOpportunities = $this->staleOpportunities($user, $dismissed);

        return $inactiveAccounts
            ->concat($staleOpportunities)
            ->take(self::ASSISTANT_LIMIT)
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, AssistantRecommendationDismissal>  $dismissed
     * @return Collection<int, array<string, mixed>>
     */
    private function inactiveAccounts(User $user, Collection $dismissed): Collection
    {
        $cutoff = now()->subDays(self::INACTIVE_ACCOUNT_DAYS);

        return Account::query()
            ->visibleTo($user)
            ->where('updated_at', '<=', $cutoff)
            ->orderBy('updated_at')
            ->orderBy('name')
            ->limit(self::ASSISTANT_LIMIT)
            ->get(['id', 'name', 'updated_at'])
            ->reject(fn (Account $account): bool => $this->isDismissed(
                $dismissed,
                AssistantRecommendationDismissal::RULE_INACTIVE_ACCOUNT,
                AssistantRecommendationDismissal::TYPE_ACCOUNT,
                $account->id,
            ))
            ->map(fn (Account $account): array => [
                'key' => AssistantRecommendationDismissal::RULE_INACTIVE_ACCOUNT.':'.AssistantRecommendationDismissal::TYPE_ACCOUNT.':'.$account->id,
                'rule' => AssistantRecommendationDismissal::RULE_INACTIVE_ACCOUNT,
                'recommendable_type' => AssistantRecommendationDismissal::TYPE_ACCOUNT,
                'recommendable_id' => $account->id,
                'title' => $account->name,
                'message' => 'No activity for '.self::INACTIVE_ACCOUNT_DAYS.' days. Consider a follow-up.',
                'url' => route('accounts.show', $account),
            ])
            ->values();
    }

    /**
     * @param  Collection<int, AssistantRecommendationDismissal>  $dismissed
     * @return Collection<int, array<string, mixed>>
     */
    private function staleOpportunities(User $user, Collection $dismissed): Collection
    {
        $today = now()->startOfDay();
        $near = now()->addDays(self::NEAR_CLOSE_DAYS)->endOfDay();
        $staleCutoff = now()->subDays(self::STALE_UPDATE_DAYS);

        return Opportunity::query()
            ->visibleTo($user)
            ->whereNull('archived_at')
            ->where('is_closed', false)
            ->whereBetween('close_date', [$today->toDateString(), $near->toDateString()])
            ->where('updated_at', '<=', $staleCutoff)
            ->with(['account:id,name'])
            ->orderBy('close_date')
            ->orderBy('name')
            ->limit(self::ASSISTANT_LIMIT)
            ->get(['id', 'name', 'account_id', 'close_date', 'updated_at'])
            ->reject(fn (Opportunity $opportunity): bool => $this->isDismissed(
                $dismissed,
                AssistantRecommendationDismissal::RULE_STALE_OPPORTUNITY,
                AssistantRecommendationDismissal::TYPE_OPPORTUNITY,
                $opportunity->id,
            ))
            ->map(fn (Opportunity $opportunity): array => [
                'key' => AssistantRecommendationDismissal::RULE_STALE_OPPORTUNITY.':'.AssistantRecommendationDismissal::TYPE_OPPORTUNITY.':'.$opportunity->id,
                'rule' => AssistantRecommendationDismissal::RULE_STALE_OPPORTUNITY,
                'recommendable_type' => AssistantRecommendationDismissal::TYPE_OPPORTUNITY,
                'recommendable_id' => $opportunity->id,
                'title' => $opportunity->name,
                'message' => 'Close date is near and the opportunity has no recent update.',
                'url' => route('opportunities.show', $opportunity),
            ])
            ->values();
    }

    /**
     * @param  Collection<int, AssistantRecommendationDismissal>  $dismissed
     */
    private function isDismissed(Collection $dismissed, string $rule, string $type, int $id): bool
    {
        $typeKeys = AssistantRecommendationDismissal::recommendableTypeKeys($type);

        return $dismissed->contains(function (AssistantRecommendationDismissal $row) use ($rule, $typeKeys, $id): bool {
            return $row->rule === $rule
                && in_array($row->recommendable_type, $typeKeys, true)
                && (int) $row->recommendable_id === $id;
        });
    }

    private function relatedLabel(mixed $related): ?string
    {
        if ($related === null) {
            return null;
        }

        if ($related instanceof Account) {
            return $related->name;
        }

        if ($related instanceof Opportunity) {
            return $related->name;
        }

        if (isset($related->subject)) {
            return (string) $related->subject;
        }

        if (isset($related->name)) {
            return (string) $related->name;
        }

        if (isset($related->last_name)) {
            return trim(($related->first_name ? $related->first_name.' ' : '').$related->last_name);
        }

        return null;
    }
}
