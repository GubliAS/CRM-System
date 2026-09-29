<script setup>
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { display, formatDay, formatMoney, formatWhen } from '@/display';
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

const maxPipelineValue = computed(() => {
    const values = props.pipeline.map((row) => Number(row.value) || 0);

    return Math.max(...values, 1);
});

const maxSourceValue = computed(() => {
    const values = props.revenueBySource.map((row) => Number(row.value) || 0);

    return Math.max(...values, 1);
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

function barWidth(value, max) {
    const amount = Number(value) || 0;

    return `${Math.max((amount / max) * 100, amount > 0 ? 4 : 0)}%`;
}
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Home" />

        <div class="mx-auto max-w-7xl space-y-6 px-4 py-6">
            <div>
                <h1 class="text-h1">Home</h1>
                <p class="mt-1 text-small text-text-muted">
                    Pipeline and activity for {{ year }}.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
                <section class="rounded-md border border-border bg-surface p-4">
                    <div class="flex flex-wrap items-end justify-between gap-2">
                        <h2 class="text-h2">Pipeline funnel</h2>
                        <p class="text-body text-text-muted">
                            Total value {{ formatMoney(pipelineTotal) }}
                        </p>
                    </div>
                    <ul class="mt-4 space-y-3">
                        <li v-for="row in pipeline" :key="row.stage">
                            <Link
                                :href="row.href"
                                class="block rounded-md focus:outline-none focus:ring-2 focus:ring-secondary"
                            >
                                <div class="flex flex-wrap items-baseline justify-between gap-2">
                                    <span class="text-body text-primary">{{ row.stage }}</span>
                                    <span class="text-small text-text-muted">
                                        {{ row.count }} · {{ formatMoney(row.value) }}
                                    </span>
                                </div>
                                <div class="mt-1 h-2 rounded bg-bg">
                                    <div
                                        class="h-2 rounded bg-secondary"
                                        :style="{ width: barWidth(row.value, maxPipelineValue) }"
                                    />
                                </div>
                            </Link>
                        </li>
                    </ul>
                </section>

                <section class="rounded-md border border-border bg-surface p-4">
                    <div class="flex flex-wrap items-end justify-between gap-2">
                        <h2 class="text-h2">Revenue by lead source</h2>
                        <p class="text-body text-text-muted">
                            Total {{ formatMoney(revenueBySourceTotal) }}
                        </p>
                    </div>
                    <p
                        v-if="revenueBySource.length === 0"
                        class="mt-4 text-body text-text-muted"
                    >
                        No opportunity revenue for {{ year }}.
                    </p>
                    <ul v-else class="mt-4 space-y-3">
                        <li v-for="row in revenueBySource" :key="row.source">
                            <div class="flex flex-wrap items-baseline justify-between gap-2">
                                <span class="text-body">{{ row.source }}</span>
                                <span class="text-small text-text-muted">
                                    {{ row.count }} · {{ formatMoney(row.value) }}
                                </span>
                            </div>
                            <div class="mt-1 h-2 rounded bg-bg">
                                <div
                                    class="h-2 rounded bg-primary"
                                    :style="{ width: barWidth(row.value, maxSourceValue) }"
                                />
                            </div>
                        </li>
                    </ul>
                </section>

                <section class="rounded-md border border-border bg-surface p-4">
                    <h2 class="text-h2">Tasks due today</h2>
                    <p
                        v-if="tasksDueToday.length === 0"
                        class="mt-4 text-body text-text-muted"
                    >
                        No tasks due today.
                    </p>
                    <ul v-else class="mt-4 divide-y divide-border">
                        <li
                            v-for="task in tasksDueToday"
                            :key="task.id"
                            class="flex flex-wrap items-center justify-between gap-3 py-3"
                        >
                            <div>
                                <p class="text-body">{{ task.subject }}</p>
                                <p class="text-small text-text-muted">
                                    {{ display(task.related_label) }}
                                    · {{ display(task.priority) }}
                                </p>
                            </div>
                            <PrimaryButton
                                v-if="task.can_complete"
                                type="button"
                                class="min-h-11"
                                @click="completeTask(task.id)"
                            >
                                Complete
                            </PrimaryButton>
                        </li>
                    </ul>
                </section>

                <section class="rounded-md border border-border bg-surface p-4">
                    <h2 class="text-h2">Today's events</h2>
                    <p
                        v-if="eventsToday.length === 0"
                        class="mt-4 text-body text-text-muted"
                    >
                        No events scheduled for today.
                    </p>
                    <ul v-else class="mt-4 divide-y divide-border">
                        <li
                            v-for="event in eventsToday"
                            :key="event.id"
                            class="py-3"
                        >
                            <p class="text-body">{{ event.subject }}</p>
                            <p class="text-small text-text-muted">
                                {{ eventWhen(event) }}
                                <span v-if="event.location"> · {{ event.location }}</span>
                                <span v-if="event.related_label">
                                    · {{ event.related_label }}
                                </span>
                            </p>
                        </li>
                    </ul>
                </section>

                <section class="rounded-md border border-border bg-surface p-4 xl:col-span-2">
                    <div class="flex flex-wrap items-end justify-between gap-2">
                        <h2 class="text-h2">Key open opportunities</h2>
                        <Link
                            :href="route('opportunities.index')"
                            class="text-body text-secondary underline"
                        >
                            View all
                        </Link>
                    </div>
                    <p
                        v-if="keyOpportunities.length === 0"
                        class="mt-4 text-body text-text-muted"
                    >
                        No open opportunities.
                    </p>
                    <div v-else class="mt-4 overflow-x-auto">
                        <table class="min-w-full text-left text-body">
                            <thead class="border-b border-border text-small text-text-muted">
                                <tr>
                                    <th class="px-2 py-2 font-medium">Name</th>
                                    <th class="px-2 py-2 font-medium">Account</th>
                                    <th class="px-2 py-2 font-medium">Amount</th>
                                    <th class="px-2 py-2 font-medium">Close date</th>
                                    <th class="px-2 py-2 font-medium">Stage</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                                <tr
                                    v-for="opportunity in keyOpportunities"
                                    :key="opportunity.id"
                                >
                                    <td class="px-2 py-3">
                                        <Link
                                            :href="opportunity.url"
                                            class="text-secondary underline"
                                        >
                                            {{ opportunity.name }}
                                        </Link>
                                    </td>
                                    <td class="px-2 py-3">
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
                                    <td class="px-2 py-3">
                                        {{ formatMoney(opportunity.amount) }}
                                    </td>
                                    <td class="px-2 py-3">
                                        {{ formatDay(opportunity.close_date) }}
                                    </td>
                                    <td class="px-2 py-3">
                                        {{ opportunity.stage }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="rounded-md border border-border bg-surface p-4 xl:col-span-2">
                    <h2 class="text-h2">Assistant</h2>
                    <p
                        v-if="recommendations.length === 0"
                        class="mt-4 text-body text-text-muted"
                    >
                        No recommendations right now.
                    </p>
                    <ul v-else class="mt-4 space-y-3">
                        <li
                            v-for="recommendation in recommendations"
                            :key="recommendation.key"
                            class="flex flex-wrap items-start justify-between gap-3 rounded-md border border-border p-3"
                        >
                            <div>
                                <Link
                                    :href="recommendation.url"
                                    class="text-body text-secondary underline"
                                >
                                    {{ recommendation.title }}
                                </Link>
                                <p class="mt-1 text-small text-text-muted">
                                    {{ recommendation.message }}
                                </p>
                            </div>
                            <SecondaryButton
                                type="button"
                                class="min-h-11"
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
