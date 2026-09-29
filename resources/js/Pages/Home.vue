<script setup>
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { display, formatDay, formatMoney, formatWhen } from '@/display';
import { stageToneClass } from '@/forms/opportunity';
import { priorityClass } from '@/forms/task';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    year: { type: Number, required: true },
    pipeline: { type: Array, required: true },
    pipelineTotal: { type: Number, required: true },
    revenueBySource: { type: Array, required: true },
    revenueBySourceTotal: { type: Number, required: true },
    tasksDueToday: { type: Array, required: true },
    eventsToday: { type: Array, required: true },
    keyOpportunities: { type: Array, required: true },
    recommendations: { type: Array, required: true },
});

const pipelineIsEmpty = computed(() => Number(props.pipelineTotal) === 0);

const revenueIsEmpty = computed(() => props.revenueBySource.length === 0);

const todayDate = computed(() => {
    const now = new Date();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');

    return `${now.getFullYear()}-${month}-${day}`;
});

function completeTask(taskId) {
    router.post(
        route('tasks.complete', taskId),
        {},
        { preserveScroll: true },
    );
}

function dismissRecommendation(recommendation) {
    router.post(
        route('home.recommendations.dismiss'),
        {
            rule: recommendation.rule,
            recommendable_type: recommendation.recommendable_type,
            recommendable_id: recommendation.recommendable_id,
        },
        { preserveScroll: true },
    );
}

function eventWhen(event) {
    if (event.all_day) {
        return 'All day';
    }

    return formatWhen(event.starts_at);
}

function barWidthFromPercent(percent) {
    const amount = Number(percent) || 0;

    if (amount <= 0) {
        return '0%';
    }

    return `${Math.max(amount, 4)}%`;
}

function formatPercent(value) {
    const amount = Number(value) || 0;

    return `${amount % 1 === 0 ? amount.toFixed(0) : amount.toFixed(1)}%`;
}
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Home" />

        <div class="crm-page space-y-6">
            <header class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h1 class="text-h1">Home</h1>
                    <p class="mt-1 text-body text-text-muted">
                        Pipeline, revenue, and today’s activity.
                    </p>
                </div>
                <p
                    class="crm-chip inline-flex min-h-11 items-center px-3 text-small font-semibold"
                    aria-label="Reporting year"
                >
                    Reporting year {{ year }}
                </p>
            </header>

            <div class="grid grid-cols-1 items-start gap-4 xl:grid-cols-2">
                <section
                    class="crm-panel w-full"
                    aria-labelledby="home-pipeline-heading"
                >
                    <div class="flex flex-wrap items-end justify-between gap-2 border-b border-border pb-3">
                        <h2 id="home-pipeline-heading" class="text-h2">
                            Pipeline funnel
                        </h2>
                        <p class="text-small text-text-muted">
                            Total
                            <span class="text-body font-semibold text-text">
                                {{ formatMoney(pipelineTotal) }}
                            </span>
                        </p>
                    </div>

                    <div
                        v-if="pipelineIsEmpty"
                        class="mt-3 rounded-md bg-bg px-3 py-3"
                    >
                        <p class="text-body text-text-muted">
                            No pipeline opportunities for {{ year }}.
                        </p>
                        <p class="mt-1 text-small text-text-muted">
                            Open a stage to create or filter deals.
                        </p>
                        <ul class="mt-3 flex flex-wrap gap-2" role="list">
                            <li v-for="row in pipeline" :key="row.stage">
                                <Link
                                    :href="row.href"
                                    class="inline-flex min-h-11 items-center rounded-md border border-border bg-surface px-3 text-small text-primary hover:bg-primary-soft focus:outline-none focus:ring-2 focus:ring-secondary"
                                    :aria-label="`${row.stage}: 0 opportunities`"
                                >
                                    {{ row.stage }}
                                </Link>
                            </li>
                        </ul>
                    </div>

                    <ul v-else class="mt-4 space-y-4" role="list">
                        <li v-for="row in pipeline" :key="row.stage">
                            <Link
                                :href="row.href"
                                class="block rounded-md focus:outline-none focus:ring-2 focus:ring-secondary"
                                :aria-label="`${row.stage}: ${row.count} opportunities, ${formatMoney(row.value)}, ${formatPercent(row.percent)} of pipeline`"
                            >
                                <div class="flex flex-wrap items-baseline justify-between gap-x-3 gap-y-1">
                                    <span class="text-body font-semibold text-primary">
                                        {{ row.stage }}
                                    </span>
                                    <span class="text-small text-text-muted">
                                        <span class="text-text">{{ row.count }}</span>
                                        · {{ formatMoney(row.value) }}
                                        · {{ formatPercent(row.percent) }}
                                    </span>
                                </div>
                                <div
                                    class="mt-2 h-3 overflow-hidden rounded-md bg-bg"
                                    role="presentation"
                                >
                                    <div
                                        class="h-3 rounded-md bg-secondary transition-all duration-fast"
                                        :style="{
                                            width: barWidthFromPercent(row.percent),
                                        }"
                                    />
                                </div>
                            </Link>
                        </li>
                    </ul>
                </section>

                <section
                    class="crm-panel w-full"
                    :class="revenueIsEmpty ? 'xl:self-start' : ''"
                    aria-labelledby="home-revenue-heading"
                >
                    <div class="flex flex-wrap items-end justify-between gap-2 border-b border-border pb-3">
                        <h2 id="home-revenue-heading" class="text-h2">
                            Revenue by lead source
                        </h2>
                        <p class="text-small text-text-muted">
                            Total
                            <span class="text-body font-semibold text-text">
                                {{ formatMoney(revenueBySourceTotal) }}
                            </span>
                        </p>
                    </div>

                    <div
                        v-if="revenueIsEmpty"
                        class="mt-3 rounded-md bg-bg px-3 py-3"
                    >
                        <p class="text-body text-text-muted">
                            No opportunity revenue for {{ year }}.
                        </p>
                    </div>

                    <ul v-else class="mt-4 space-y-4" role="list">
                        <li v-for="row in revenueBySource" :key="row.source">
                            <div class="flex flex-wrap items-baseline justify-between gap-x-3 gap-y-1">
                                <span class="text-body font-semibold text-text">
                                    {{ row.source }}
                                </span>
                                <span class="text-small text-text-muted">
                                    <span class="text-text">{{ row.count }}</span>
                                    · {{ formatMoney(row.value) }}
                                    · {{ formatPercent(row.percent) }}
                                </span>
                            </div>
                            <div
                                class="mt-2 h-3 overflow-hidden rounded-md bg-bg"
                                role="presentation"
                            >
                                <div
                                    class="h-3 rounded-md bg-primary transition-all duration-fast"
                                    :style="{
                                        width: barWidthFromPercent(row.percent),
                                    }"
                                />
                            </div>
                        </li>
                    </ul>
                </section>

                <section
                    class="crm-panel w-full"
                    aria-labelledby="home-tasks-heading"
                >
                    <div class="flex flex-wrap items-end justify-between gap-2 border-b border-border pb-3">
                        <div>
                            <h2 id="home-tasks-heading" class="text-h2">
                                Tasks due today
                            </h2>
                            <p class="mt-0.5 text-small text-text-muted">
                                {{ tasksDueToday.length }} due
                            </p>
                        </div>
                        <Link
                            :href="route('tasks.index', { view: 'today' })"
                            class="inline-flex min-h-11 items-center text-body text-secondary underline"
                        >
                            View all tasks
                        </Link>
                    </div>

                    <div
                        v-if="tasksDueToday.length === 0"
                        class="mt-3 rounded-md bg-bg px-3 py-3"
                    >
                        <p class="text-body text-text-muted">
                            No tasks due today.
                        </p>
                    </div>

                    <ul v-else class="mt-1 divide-y divide-border" role="list">
                        <li
                            v-for="task in tasksDueToday"
                            :key="task.id"
                            class="flex flex-wrap items-center justify-between gap-3 py-3"
                        >
                            <div class="min-w-0 flex-1">
                                <Link
                                    :href="task.url"
                                    class="text-body font-semibold text-secondary underline"
                                >
                                    {{ task.subject }}
                                </Link>
                                <p class="mt-1 text-small text-text-muted">
                                    {{ display(task.related_label) }}
                                    ·
                                    <span :class="priorityClass(task.priority)">
                                        {{ display(task.priority) }}
                                    </span>
                                    · Due {{ formatDay(task.due_on) }}
                                </p>
                            </div>
                            <PrimaryButton
                                v-if="task.can_complete"
                                type="button"
                                class="min-h-11 shrink-0"
                                :aria-label="`Complete task ${task.subject}`"
                                @click="completeTask(task.id)"
                            >
                                Complete
                            </PrimaryButton>
                        </li>
                    </ul>
                </section>

                <section
                    class="crm-panel w-full"
                    aria-labelledby="home-events-heading"
                >
                    <div class="flex flex-wrap items-end justify-between gap-2 border-b border-border pb-3">
                        <div>
                            <h2 id="home-events-heading" class="text-h2">
                                Today's events
                            </h2>
                            <p class="mt-0.5 text-small text-text-muted">
                                {{ eventsToday.length }} scheduled
                            </p>
                        </div>
                        <Link
                            :href="
                                route('events.index', {
                                    view: 'day',
                                    date: todayDate,
                                })
                            "
                            class="inline-flex min-h-11 items-center text-body text-secondary underline"
                        >
                            View calendar
                        </Link>
                    </div>

                    <div
                        v-if="eventsToday.length === 0"
                        class="mt-3 rounded-md bg-bg px-3 py-3"
                    >
                        <p class="text-body text-text-muted">
                            No events scheduled for today.
                        </p>
                    </div>

                    <ul v-else class="mt-1 divide-y divide-border" role="list">
                        <li
                            v-for="event in eventsToday"
                            :key="event.id"
                            class="py-3"
                        >
                            <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                                <p class="min-w-[5.5rem] shrink-0 text-small font-semibold text-text">
                                    {{ eventWhen(event) }}
                                </p>
                                <div class="min-w-0 flex-1">
                                    <Link
                                        :href="event.url"
                                        class="text-body font-semibold text-secondary underline"
                                    >
                                        {{ event.subject }}
                                    </Link>
                                    <p class="mt-1 text-small text-text-muted">
                                        <span v-if="event.location">{{ event.location }}</span>
                                        <span
                                            v-if="event.location && event.related_label"
                                            aria-hidden="true"
                                        >
                                            ·
                                        </span>
                                        <span v-if="event.related_label">
                                            {{ event.related_label }}
                                        </span>
                                        <span
                                            v-if="!event.location && !event.related_label"
                                        >
                                            —
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </li>
                    </ul>
                </section>

                <section
                    class="crm-panel w-full xl:col-span-2"
                    aria-labelledby="home-deals-heading"
                >
                    <div class="flex flex-wrap items-end justify-between gap-2 border-b border-border pb-3">
                        <div>
                            <h2 id="home-deals-heading" class="text-h2">
                                Key open opportunities
                            </h2>
                            <p class="mt-0.5 text-small text-text-muted">
                                Highest-value open deals
                            </p>
                        </div>
                        <Link
                            :href="route('opportunities.index')"
                            class="inline-flex min-h-11 items-center text-body text-secondary underline"
                        >
                            View all
                        </Link>
                    </div>

                    <div
                        v-if="keyOpportunities.length === 0"
                        class="mt-3 rounded-md bg-bg px-3 py-3"
                    >
                        <p class="text-body text-text-muted">
                            No open opportunities.
                        </p>
                    </div>

                    <div v-else class="mt-4 overflow-x-auto">
                        <table class="crm-table">
                            <thead>
                                <tr>
                                    <th scope="col">Name</th>
                                    <th scope="col">Account</th>
                                    <th scope="col">Amount</th>
                                    <th scope="col">Close date</th>
                                    <th scope="col">Stage</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="opportunity in keyOpportunities"
                                    :key="opportunity.id"
                                >
                                    <td>
                                        <Link
                                            :href="opportunity.url"
                                            class="text-secondary underline"
                                        >
                                            {{ opportunity.name }}
                                        </Link>
                                    </td>
                                    <td>
                                        <Link
                                            v-if="opportunity.account"
                                            :href="
                                                route(
                                                    'accounts.show',
                                                    opportunity.account.id,
                                                )
                                            "
                                            class="text-secondary underline"
                                        >
                                            {{ opportunity.account.name }}
                                        </Link>
                                        <span v-else>—</span>
                                    </td>
                                    <td>
                                        {{ formatMoney(opportunity.amount) }}
                                    </td>
                                    <td>
                                        {{ formatDay(opportunity.close_date) }}
                                    </td>
                                    <td>
                                        <span :class="stageToneClass(opportunity.stage)">
                                            {{ opportunity.stage }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section
                    class="crm-panel w-full xl:col-span-2"
                    aria-labelledby="home-assistant-heading"
                >
                    <div class="border-b border-border pb-3">
                        <h2 id="home-assistant-heading" class="text-h2">
                            Assistant
                        </h2>
                        <p class="mt-0.5 text-small text-text-muted">
                            Suggested follow-ups from inactive accounts and stale deals
                        </p>
                    </div>

                    <div
                        v-if="recommendations.length === 0"
                        class="mt-3 rounded-md bg-bg px-3 py-3"
                    >
                        <p class="text-body text-text-muted">
                            No recommendations right now.
                        </p>
                    </div>

                    <ul v-else class="mt-4 space-y-3" role="list">
                        <li
                            v-for="recommendation in recommendations"
                            :key="recommendation.key"
                            class="flex flex-wrap items-start justify-between gap-3 rounded-md border border-border bg-bg p-3"
                        >
                            <div class="min-w-0 flex-1">
                                <Link
                                    :href="recommendation.url"
                                    class="text-body font-semibold text-secondary underline"
                                >
                                    {{ recommendation.title }}
                                </Link>
                                <p class="mt-1 text-small text-text-muted">
                                    {{ recommendation.message }}
                                </p>
                            </div>
                            <SecondaryButton
                                type="button"
                                class="min-h-11 shrink-0"
                                :aria-label="`Dismiss recommendation for ${recommendation.title}`"
                                @click="dismissRecommendation(recommendation)"
                            >
                                Dismiss
                            </SecondaryButton>
                        </li>
                    </ul>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
