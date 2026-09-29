<script setup>
import PaginationBar from '@/Components/PaginationBar.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { display, formatWhen } from '@/display';
import { priorityClass } from '@/forms/case';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    cases: { type: Object, required: true },
    filters: { type: Object, required: true },
    can: { type: Object, required: true },
});

const search = ref(props.filters.search ?? '');

watch(
    () => props.filters.search,
    (value) => {
        search.value = value ?? '';
    },
);

const views = [
    { key: 'recent', label: 'Recently Viewed' },
    { key: 'my_open', label: 'My Open Cases' },
    { key: 'all_open', label: 'All Open Cases' },
    { key: 'recently_closed', label: 'Recently Closed Cases' },
];

const columns = [
    { key: 'case_number', label: 'Case number' },
    { key: 'subject', label: 'Subject' },
    { key: 'status', label: 'Status' },
    { key: 'priority', label: 'Priority' },
    { key: 'opened', label: 'Date/time opened' },
    { key: 'owner', label: 'Owner' },
];

function listQuery(extra = {}) {
    return {
        search: search.value || undefined,
        view: props.filters.view,
        sort: props.filters.sort,
        direction: props.filters.direction,
        per_page: props.filters.per_page,
        ...extra,
    };
}

function applySearch() {
    router.get(route('cases.index'), listQuery(), {
        preserveState: true,
        replace: true,
    });
}

function changeView(view) {
    router.get(route('cases.index'), listQuery({ view }), {
        preserveState: true,
        replace: true,
    });
}

function sortHref(column) {
    const direction =
        props.filters.sort === column && props.filters.direction === 'asc' ? 'desc' : 'asc';

    return route('cases.index', listQuery({ sort: column, direction }));
}

function changePerPage(event) {
    router.get(route('cases.index'), listQuery({ per_page: event.target.value }), {
        preserveState: true,
        replace: true,
    });
}
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Cases" />

        <div class="crm-page">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h1 class="text-h1">Cases</h1>
                    <p class="mt-1 text-small text-text-muted">
                        Showing {{ cases.from ?? 0 }}–{{ cases.to ?? 0 }} of {{ cases.total }}
                    </p>
                </div>
                <Link
                    v-if="can.create"
                    :href="route('cases.create')"
                    class="crm-btn-primary"
                >
                    New case
                </Link>
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                <button
                    v-for="view in views"
                    :key="view.key"
                    type="button"
                    class="crm-view-tab"
                    :class="
                        filters.view === view.key
                            ? 'crm-view-tab-active'
                            : 'crm-view-tab-idle'
                    "
                    @click="changeView(view.key)"
                >
                    {{ view.label }}
                </button>
            </div>

            <form class="mt-4 flex flex-wrap items-end gap-3" @submit.prevent="applySearch">
                <div class="min-w-0 flex-1 sm:max-w-xs">
                    <label class="text-small text-text-muted" for="case-search">Search</label>
                    <TextInput id="case-search" v-model="search" type="search" class="mt-1 block w-full" />
                </div>
                <div>
                    <label class="text-small text-text-muted" for="case-per-page">Rows</label>
                    <select
                        id="case-per-page"
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
                <button
                    type="submit"
                    class="crm-btn-primary"
                >
                    Search
                </button>
            </form>

            <div class="mt-4 crm-table-wrap">
                <table class="crm-table">
                    <thead>
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
                        <tr v-if="cases.data.length === 0">
                            <td colspan="6" class="px-4 py-8 text-center text-body text-text-muted">
                                <template v-if="filters.search">
                                    No cases match your search.
                                </template>
                                <template v-else-if="filters.view === 'recent'">
                                    <p>No recently viewed cases.</p>
                                    <button
                                        type="button"
                                        class="crm-btn-secondary mt-3"
                                        @click="changeView('all_open')"
                                    >
                                        View all open cases
                                    </button>
                                </template>
                                <template v-else>
                                    No cases in this view.
                                </template>
                            </td>
                        </tr>
                        <tr v-for="item in cases.data" :key="item.id" class="border-t border-border">
                            <td class="px-3 py-2">
                                <Link :href="route('cases.show', item.id)" class="text-secondary underline">
                                    {{ display(item.case_number) }}
                                </Link>
                            </td>
                            <td class="px-3 py-2">{{ display(item.subject) }}</td>
                            <td class="px-3 py-2">{{ display(item.status) }}</td>
                            <td class="px-3 py-2">
                                <span :class="priorityClass(item.priority)">{{ display(item.priority) }}</span>
                            </td>
                            <td class="px-3 py-2">{{ formatWhen(item.created_at) }}</td>
                            <td class="px-3 py-2">{{ display(item.owner?.name) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                <PaginationBar :paginator="cases" />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
