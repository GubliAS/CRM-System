<script setup>
import PaginationBar from '@/Components/PaginationBar.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { display, fullName } from '@/display';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    leads: { type: Object, required: true },
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
    { key: 'all', label: 'All Leads' },
];

const columns = [
    { key: 'name', label: 'Name' },
    { key: 'title', label: 'Title' },
    { key: 'company', label: 'Company' },
    { key: 'phone', label: 'Phone' },
    { key: 'email', label: 'Email' },
    { key: 'lead_source', label: 'Lead source' },
    { key: 'owner', label: 'Owner' },
    { key: 'lead_status', label: 'Lead status' },
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
    router.get(route('leads.index'), listQuery(), {
        preserveState: true,
        replace: true,
    });
}

function changeView(view) {
    router.get(route('leads.index'), listQuery({ view }), {
        preserveState: true,
        replace: true,
    });
}

function sortHref(column) {
    const direction =
        props.filters.sort === column && props.filters.direction === 'asc' ? 'desc' : 'asc';

    return route('leads.index', listQuery({ sort: column, direction }));
}

function changePerPage(event) {
    router.get(route('leads.index'), listQuery({ per_page: event.target.value }), {
        preserveState: true,
        replace: true,
    });
}
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Leads" />

        <div class="crm-page">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h1 class="text-h1">Leads</h1>
                    <p class="mt-1 text-small text-text-muted">
                        Showing {{ leads.from ?? 0 }}–{{ leads.to ?? 0 }} of {{ leads.total }}
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a
                        v-if="can.export"
                        :href="route('leads.export', listQuery())"
                        class="crm-btn-secondary"
                    >
                        Export CSV
                    </a>
                    <Link
                        v-if="can.create"
                        :href="route('leads.create')"
                        class="crm-btn-primary"
                    >
                        New lead
                    </Link>
                </div>
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
                    <label class="text-small text-text-muted" for="lead-search">Search</label>
                    <TextInput id="lead-search" v-model="search" type="search" class="mt-1 block w-full" />
                </div>
                <div>
                    <label class="text-small text-text-muted" for="lead-per-page">Rows</label>
                    <select
                        id="lead-per-page"
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
                        <tr v-if="leads.data.length === 0">
                            <td colspan="8" class="px-4 py-8 text-center text-body text-text-muted">
                                {{
                                    filters.search
                                        ? 'No leads match your search.'
                                        : filters.view === 'recent'
                                          ? 'No recently viewed leads.'
                                          : 'No leads yet.'
                                }}
                            </td>
                        </tr>
                        <tr v-for="lead in leads.data" :key="lead.id" class="border-t border-border">
                            <td class="px-3 py-2">
                                <Link :href="route('leads.show', lead.id)" class="text-secondary underline">
                                    {{ fullName(lead) }}
                                </Link>
                            </td>
                            <td class="px-3 py-2">{{ display(lead.title) }}</td>
                            <td class="px-3 py-2">{{ display(lead.company) }}</td>
                            <td class="px-3 py-2">{{ display(lead.phone) }}</td>
                            <td class="px-3 py-2">{{ display(lead.email) }}</td>
                            <td class="px-3 py-2">{{ display(lead.lead_source) }}</td>
                            <td class="px-3 py-2">{{ display(lead.owner?.name) }}</td>
                            <td class="px-3 py-2">{{ display(lead.lead_status) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                <PaginationBar :paginator="leads" />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
