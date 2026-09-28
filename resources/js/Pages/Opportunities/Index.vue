<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import PaginationBar from '@/Components/PaginationBar.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { display, formatDay, formatMoney } from '@/display';
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
        ...extra,
    };
}

function applySearch() {
    router.get(route('opportunities.index'), listQuery(), {
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

    return route('opportunities.index', listQuery({ sort: column, direction }));
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

            <form class="mt-4 flex flex-wrap items-end gap-3" @submit.prevent="applySearch">
                <div class="min-w-0 flex-1 sm:max-w-xs">
                    <label class="text-small text-text-muted" for="opportunity-search">Search</label>
                    <TextInput id="opportunity-search" v-model="search" type="search" class="mt-1 block w-full" />
                </div>
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

            <div class="mt-4 overflow-x-auto rounded-md border border-border bg-surface">
                <table class="min-w-full text-left text-body">
                    <thead class="bg-bg text-small text-text-muted">
                        <tr>
                            <th v-for="column in columns" :key="column.key" scope="col" class="px-3 py-2">
                                <Link
                                    :href="sortHref(column.key)"
                                    class="inline-flex min-h-11 items-center gap-1 text-small text-text"
                                >
                                    {{ column.label }}
                                    <span v-if="filters.sort === column.key">
                                        {{ filters.direction === 'asc' ? '↑' : '↓' }}
                                    </span>
                                </Link>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="opportunities.data.length === 0">
                            <td colspan="7" class="px-4 py-8 text-center text-body text-text-muted">
                                {{ filters.search ? 'No opportunities match your search.' : 'No opportunities yet.' }}
                            </td>
                        </tr>
                        <tr
                            v-for="opportunity in opportunities.data"
                            :key="opportunity.id"
                            class="border-t border-border"
                        >
                            <td class="px-3 py-2">
                                <Link
                                    :href="route('opportunities.show', opportunity.id)"
                                    class="text-secondary underline"
                                >
                                    {{ opportunity.name }}
                                </Link>
                            </td>
                            <td class="px-3 py-2">
                                <Link
                                    v-if="opportunity.account"
                                    :href="route('accounts.show', opportunity.account.id)"
                                    class="text-secondary underline"
                                >
                                    {{ opportunity.account.name }}
                                </Link>
                                <span v-else>—</span>
                            </td>
                            <td class="px-3 py-2">{{ formatMoney(opportunity.amount) }}</td>
                            <td class="px-3 py-2">{{ formatDay(opportunity.close_date) }}</td>
                            <td class="px-3 py-2" :class="stageClass(opportunity.stage)">{{ opportunity.stage }}</td>
                            <td class="px-3 py-2">{{ opportunity.probability }}%</td>
                            <td class="px-3 py-2">{{ display(opportunity.owner?.name) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                <PaginationBar :paginator="opportunities" />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
