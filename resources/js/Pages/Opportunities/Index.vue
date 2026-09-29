<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import PaginationBar from '@/Components/PaginationBar.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { display, formatDay, formatMoney, personName } from '@/display';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    opportunities: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        required: true,
    },
    stages: {
        type: Array,
        required: true,
    },
    can: {
        type: Object,
        required: true,
    },
});

const search = ref(props.filters.search ?? '');
const showArchived = ref(Boolean(props.filters.show_archived));

watch(
    () => props.filters.search,
    (value) => {
        search.value = value ?? '';
    },
);

watch(
    () => props.filters.show_archived,
    (value) => {
        showArchived.value = Boolean(value);
    },
);

const views = [
    { key: 'recent', label: 'Recently Viewed' },
    { key: 'all', label: 'All Opportunities' },
];

const columns = [
    { key: 'name', label: 'Name' },
    { key: 'account', label: 'Account' },
    { key: 'amount', label: 'Amount' },
    { key: 'close_date', label: 'Close date' },
    { key: 'stage', label: 'Stage' },
    { key: 'probability', label: 'Probability' },
    { key: 'owner', label: 'Owner' },
];

function stageClass(stage) {
    if (stage === 'Closed Won') {
        return 'text-success';
    }

    if (stage === 'Closed Lost') {
        return 'text-danger';
    }

    return 'text-secondary';
}

function listQuery(extra = {}) {
    return {
        search: search.value || undefined,
        sort: props.filters.sort,
        direction: props.filters.direction,
        per_page: props.filters.per_page,
        show_archived: showArchived.value ? 1 : undefined,
        stage: props.filters.stage || undefined,
        year: props.filters.year || undefined,
        view: props.filters.view,
        ...extra,
    };
}

function applySearch() {
    router.get(route('opportunities.index'), listQuery({ view: 'all' }), {
        preserveState: true,
        replace: true,
    });
}

function changeView(view) {
    router.get(route('opportunities.index'), listQuery({ view }), {
        preserveState: true,
        replace: true,
    });
}

function onArchived(value) {
    showArchived.value = value;
    applySearch();
}

function sortHref(column) {
    const direction = props.filters.sort === column && props.filters.direction === 'asc' ? 'desc' : 'asc';

    return route('opportunities.index', listQuery({ sort: column, direction, view: 'all' }));
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

function changePerPage(event) {
    router.get(route('opportunities.index'), listQuery({ per_page: event.target.value }), {
        preserveState: true,
        replace: true,
    });
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
                        Showing {{ opportunities.from ?? 0 }}–{{ opportunities.to ?? 0 }} of {{ opportunities.total }}
                    </p>
                </div>
                <Link
                    v-if="can.create"
                    :href="route('opportunities.create')"
                    class="inline-flex min-h-11 items-center rounded-md bg-primary px-4 py-2 text-small font-semibold uppercase tracking-widest text-surface"
                >
                    New opportunity
                </Link>
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                <button
                    v-for="view in views"
                    :key="view.key"
                    type="button"
                    class="inline-flex min-h-11 items-center rounded-md border px-3 text-small"
                    :class="
                        filters.view === view.key
                            ? 'border-secondary bg-surface text-primary'
                            : 'border-border bg-surface text-text'
                    "
                    @click="changeView(view.key)"
                >
                    {{ view.label }}
                </button>
            </div>

            <form class="mt-4 flex flex-wrap items-end gap-3" @submit.prevent="applySearch">
                <div class="min-w-0 flex-1 sm:max-w-xs">
                    <label class="text-small text-text-muted" for="opportunity-search">Search</label>
                    <TextInput id="opportunity-search" v-model="search" type="search" class="mt-1 block w-full" />
                </div>
                <label class="text-small text-text-muted">
                    Stage
                    <select
                        class="mt-1 block min-h-11 rounded-md border-border bg-surface text-body text-text"
                        :value="filters.stage ?? ''"
                        @change="changeStage"
                    >
                        <option value="">All stages</option>
                        <option v-for="stageOption in stages" :key="stageOption" :value="stageOption">
                            {{ stageOption }}
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
                <div>
                    <label class="text-small text-text-muted" for="opportunity-per-page">Rows</label>
                    <select
                        id="opportunity-per-page"
                        class="mt-1 block min-h-11 rounded-md border-border bg-surface text-body text-text"
                        :value="filters.per_page"
                        @change="changePerPage"
                    >
                        <option :value="25">25</option>
                        <option :value="50">50</option>
                        <option :value="100">100</option>
                        <option :value="200">200</option>
                    </select>
                </div>
                <label class="flex min-h-11 items-center gap-2 text-body text-text">
                    <Checkbox :checked="showArchived" @update:checked="onArchived" />
                    Show archived
                </label>
                <button
                    type="submit"
                    class="inline-flex min-h-11 items-center rounded-md bg-primary px-4 py-2 text-small font-semibold uppercase tracking-widest text-surface"
                >
                    Search
                </button>
            </form>

            <p v-if="filters.stage || filters.year" class="mt-3 text-small text-text-muted">
                <span v-if="filters.stage">Filtered by stage: {{ filters.stage }}. </span>
                <span v-if="filters.year">Close date year: {{ filters.year }}.</span>
            </p>

            <div class="mt-4 overflow-x-auto rounded-md border border-border bg-surface">
                <table class="min-w-full text-left text-body">
                    <thead class="border-b border-border bg-bg text-small text-text-muted">
                        <tr>
                            <th v-for="column in columns" :key="column.key" scope="col" class="px-3 py-3 font-medium">
                                <Link
                                    v-if="filters.view === 'all'"
                                    :href="sortHref(column.key)"
                                    class="inline-flex min-h-11 items-center gap-1 text-small text-text"
                                >
                                    {{ column.label }}
                                    <span v-if="filters.sort === column.key">
                                        {{ filters.direction === 'asc' ? '↑' : '↓' }}
                                    </span>
                                </Link>
                                <span v-else>{{ column.label }}</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr v-if="opportunities.data.length === 0">
                            <td colspan="7" class="px-3 py-6 text-center text-text-muted">
                                {{
                                    filters.search || filters.stage || filters.year
                                        ? 'No opportunities match this filter.'
                                        : filters.view === 'recent'
                                          ? 'No recently viewed opportunities yet.'
                                          : 'No opportunities yet.'
                                }}
                            </td>
                        </tr>
                        <tr v-for="opportunity in opportunities.data" :key="opportunity.id">
                            <td class="px-3 py-3">
                                <Link
                                    :href="route('opportunities.show', opportunity.id)"
                                    class="text-secondary underline"
                                >
                                    {{ opportunity.name }}
                                </Link>
                            </td>
                            <td class="px-3 py-3">
                                <Link
                                    v-if="opportunity.account"
                                    :href="route('accounts.show', opportunity.account.id)"
                                    class="text-secondary underline"
                                >
                                    {{ opportunity.account.name }}
                                </Link>
                                <span v-else>—</span>
                            </td>
                            <td class="px-3 py-3">{{ formatMoney(opportunity.amount) }}</td>
                            <td class="px-3 py-3">{{ formatDay(opportunity.close_date) }}</td>
                            <td class="px-3 py-3" :class="stageClass(opportunity.stage)">{{ opportunity.stage }}</td>
                            <td class="px-3 py-3">{{ opportunity.probability }}%</td>
                            <td class="px-3 py-3">{{ personName(opportunity.owner) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <PaginationBar class="mt-4" :paginator="opportunities" />
        </div>
    </AuthenticatedLayout>
</template>
