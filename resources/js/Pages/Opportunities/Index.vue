<script setup>
import PaginationBar from '@/Components/PaginationBar.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { display, formatDay, formatMoney, personName } from '@/display';
import { stageToneClass } from '@/forms/opportunity';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    opportunities: { type: Object, required: true },
    filters: { type: Object, required: true },
    stages: { type: Array, required: true },
    can: { type: Object, required: true },
});

const views = [
    { key: 'recent', label: 'Recently Viewed' },
    { key: 'all', label: 'All Opportunities' },
];

function listQuery(extra = {}) {
    return {
        stage: props.filters.stage || undefined,
        year: props.filters.year || undefined,
        view: props.filters.view,
        archived: props.filters.archived ? 1 : undefined,
        per_page: props.filters.per_page,
        ...extra,
    };
}

function changeView(view) {
    router.get(route('opportunities.index'), listQuery({ view, archived: undefined }), {
        preserveState: true,
        replace: true,
    });
}

function changeStage(event) {
    const stage = event.target.value || undefined;

    router.get(route('opportunities.index'), listQuery({ stage, view: 'all' }), {
        preserveState: true,
        replace: true,
    });
}

function clearStage() {
    router.get(route('opportunities.index'), listQuery({ stage: undefined }), {
        preserveState: true,
        replace: true,
    });
}

function toggleArchived() {
    router.get(
        route('opportunities.index'),
        listQuery({
            archived: props.filters.archived ? undefined : 1,
            view: 'all',
        }),
        {
            preserveState: true,
            replace: true,
        },
    );
}

function changePerPage(event) {
    router.get(
        route('opportunities.index'),
        listQuery({ per_page: event.target.value }),
        {
            preserveState: true,
            replace: true,
        },
    );
}
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Opportunities" />

        <div class="mx-auto max-w-7xl px-4 py-6">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h1 class="text-h1">Opportunities</h1>
                    <p class="mt-1 text-small text-text-muted">
                        Showing {{ opportunities.from ?? 0 }}–{{ opportunities.to ?? 0 }}
                        of {{ opportunities.total }}
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a
                        v-if="can.export"
                        :href="route('opportunities.export', listQuery())"
                        class="inline-flex min-h-11 items-center rounded-md border border-border bg-surface px-4 py-2 text-small"
                    >
                        Export CSV
                    </a>
                    <Link
                        v-if="can.create"
                        :href="route('opportunities.create')"
                        class="inline-flex min-h-11 items-center rounded-md border border-transparent bg-primary px-4 py-2 text-small font-semibold uppercase tracking-widest text-white"
                    >
                        New Opportunity
                    </Link>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                <button
                    v-for="view in views"
                    :key="view.key"
                    type="button"
                    class="inline-flex min-h-11 items-center rounded-md border px-3 text-small"
                    :class="
                        filters.view === view.key && !filters.archived
                            ? 'border-secondary bg-surface text-primary'
                            : 'border-border bg-surface text-text'
                    "
                    @click="changeView(view.key)"
                >
                    {{ view.label }}
                </button>
                <button
                    type="button"
                    class="inline-flex min-h-11 items-center rounded-md border px-3 text-small"
                    :class="
                        filters.archived
                            ? 'border-secondary bg-surface text-primary'
                            : 'border-border bg-surface text-text'
                    "
                    @click="toggleArchived"
                >
                    Archived
                </button>
            </div>

            <div class="mt-4 flex flex-wrap items-end gap-3">
                <label class="text-small text-text-muted">
                    Stage
                    <select
                        class="mt-1 block min-h-11 rounded-md border-border bg-surface text-body text-text"
                        :value="filters.stage ?? ''"
                        @change="changeStage"
                    >
                        <option value="">All stages</option>
                        <option
                            v-for="stage in stages"
                            :key="stage"
                            :value="stage"
                        >
                            {{ stage }}
                        </option>
                    </select>
                </label>
                <button
                    v-if="filters.stage"
                    type="button"
                    class="inline-flex min-h-11 items-center rounded-md border border-border bg-surface px-3 text-small text-text"
                    @click="clearStage"
                >
                    Clear stage
                </button>
                <label class="ms-auto text-small text-text-muted">
                    Per page
                    <select
                        class="mt-1 block min-h-11 rounded-md border-border bg-surface text-body text-text"
                        :value="filters.per_page"
                        @change="changePerPage"
                    >
                        <option :value="25">25</option>
                        <option :value="50">50</option>
                        <option :value="100">100</option>
                        <option :value="200">200</option>
                    </select>
                </label>
            </div>

            <p
                v-if="filters.stage || filters.year || filters.archived"
                class="mt-3 text-small text-text-muted"
            >
                <span v-if="filters.archived">Showing archived opportunities. </span>
                <span v-if="filters.stage">Filtered by stage: {{ filters.stage }}. </span>
                <span v-if="filters.year">Close date year: {{ filters.year }}.</span>
            </p>

            <div class="mt-4 overflow-x-auto rounded-md border border-border bg-surface">
                <table class="min-w-full text-left text-body">
                    <thead class="border-b border-border bg-bg text-small text-text-muted">
                        <tr>
                            <th class="px-3 py-3 font-medium">Name</th>
                            <th class="px-3 py-3 font-medium">Account</th>
                            <th class="px-3 py-3 font-medium">Amount</th>
                            <th class="px-3 py-3 font-medium">Close date</th>
                            <th class="px-3 py-3 font-medium">Stage</th>
                            <th class="px-3 py-3 font-medium">Probability</th>
                            <th class="px-3 py-3 font-medium">Owner</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr v-if="opportunities.data.length === 0">
                            <td
                                colspan="7"
                                class="px-3 py-6 text-center text-text-muted"
                            >
                                No opportunities match this filter.
                            </td>
                        </tr>
                        <tr
                            v-for="opportunity in opportunities.data"
                            :key="opportunity.id"
                        >
                            <td class="px-3 py-3">
                                <Link
                                    :href="route('opportunities.show', opportunity.id)"
                                    class="text-secondary underline"
                                >
                                    {{ opportunity.name }}
                                </Link>
                            </td>
                            <td class="px-3 py-3">
                                {{ display(opportunity.account?.name) }}
                            </td>
                            <td class="px-3 py-3">
                                {{ formatMoney(opportunity.amount) }}
                            </td>
                            <td class="px-3 py-3">
                                {{ formatDay(opportunity.close_date) }}
                            </td>
                            <td class="px-3 py-3">
                                <span :class="stageToneClass(opportunity.stage)">
                                    {{ opportunity.stage }}
                                </span>
                            </td>
                            <td class="px-3 py-3">
                                {{
                                    opportunity.probability === null ||
                                    opportunity.probability === undefined
                                        ? '—'
                                        : `${opportunity.probability}%`
                                }}
                            </td>
                            <td class="px-3 py-3">
                                {{ personName(opportunity.owner) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <PaginationBar class="mt-4" :paginator="opportunities" />
        </div>
    </AuthenticatedLayout>
</template>
