<script setup>
import PaginationBar from '@/Components/PaginationBar.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { display, fullName } from '@/display';
import { Icon } from '@iconify/vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    leads: { type: Object, required: true },
    summary: { type: Object, required: true },
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
    { key: 'recent', label: 'Recently viewed' },
    { key: 'all', label: 'All leads' },
];

const columns = [
    { key: 'name', label: 'Name', icon: 'lucide:user' },
    { key: 'company', label: 'Company', icon: 'lucide:building-2' },
    { key: 'phone', label: 'Phone', icon: 'lucide:phone' },
    { key: 'email', label: 'Email', icon: 'lucide:mail' },
    { key: 'lead_source', label: 'Source', icon: 'lucide:megaphone' },
    { key: 'owner', label: 'Owner', icon: 'lucide:user-round-cog' },
    { key: 'lead_status', label: 'Status', icon: 'lucide:badge-check' },
];

const STATUS_TAGS = {
    New: 'crm-ov-tag-draft',
    Working: 'crm-ov-tag-pending',
    Nurturing: 'crm-ov-tag-partial',
    Qualified: 'crm-ov-tag-open',
    Unqualified: 'crm-ov-tag-lost',
    Converted: 'crm-ov-tag-won',
};

const workingCount = computed(() => statusCount('Working'));

const conversionRate = computed(() => {
    if (!props.summary.total) {
        return null;
    }

    return Math.round((props.summary.converted / props.summary.total) * 100);
});

const statusBars = computed(() => {
    const total = Math.max(1, props.summary.total);
    const max = Math.max(0, ...props.summary.by_status.map((row) => Number(row.count) || 0));

    return props.summary.by_status.map((row) => {
        const count = Number(row.count) || 0;

        return {
            status: row.status,
            count,
            percent: Math.round((count / total) * 100),
            fill: max > 0 ? Math.max((count / max) * 100, count > 0 ? 6 : 0) : 0,
        };
    });
});

const topSources = computed(() => {
    const total = Math.max(1, props.summary.by_source.reduce((sum, row) => sum + (Number(row.count) || 0), 0));

    return props.summary.by_source.slice(0, 5).map((row) => ({
        source: row.source,
        count: Number(row.count) || 0,
        percent: Math.round(((Number(row.count) || 0) / total) * 100),
    }));
});

function statusCount(status) {
    const row = props.summary.by_status.find((item) => item.status === status);

    return row ? Number(row.count) || 0 : 0;
}

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

function statusTagClass(status) {
    return `crm-ov-tag ${STATUS_TAGS[status] ?? 'crm-ov-tag-draft'}`;
}

function initials(lead) {
    const parts = [lead.first_name, lead.last_name].filter(Boolean);

    if (parts.length === 0) {
        return '?';
    }

    return parts
        .map((part) => part.charAt(0).toUpperCase())
        .join('')
        .slice(0, 2);
}

function ownerInitials(name) {
    if (!name) {
        return '?';
    }

    return name
        .split(/\s+/)
        .filter(Boolean)
        .map((part) => part.charAt(0).toUpperCase())
        .join('')
        .slice(0, 2);
}
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Leads" />

        <template #header>
            <div class="flex min-w-0 flex-wrap items-end justify-between gap-3">
                <div class="min-w-0">
                    <h1 class="crm-page-title">Leads</h1>
                    <p class="crm-page-subtitle">
                        {{ leads.from ?? 0 }}–{{ leads.to ?? 0 }} of {{ leads.total }} records
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a
                        v-if="can.export"
                        :href="route('leads.export', listQuery())"
                        class="crm-btn-secondary"
                    >
                        Export
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
        </template>

        <div class="crm-page max-w-none px-4 py-4 sm:px-5">
            <div class="crm-list-metrics">
                <article class="crm-list-metric">
                    <span class="crm-list-metric-icon" aria-hidden="true">
                        <Icon icon="lucide:users" />
                    </span>
                    <div class="min-w-0">
                        <p class="crm-list-metric-label">Total</p>
                        <p class="crm-list-metric-value tabular-nums">{{ summary.total }}</p>
                    </div>
                </article>

                <article class="crm-list-metric">
                    <span class="crm-list-metric-icon" aria-hidden="true">
                        <Icon icon="lucide:folder-open" />
                    </span>
                    <div class="min-w-0">
                        <p class="crm-list-metric-label">Open</p>
                        <p class="crm-list-metric-value tabular-nums">{{ summary.open }}</p>
                    </div>
                </article>

                <article class="crm-list-metric">
                    <span class="crm-list-metric-icon" aria-hidden="true">
                        <Icon icon="lucide:circle-dot" />
                    </span>
                    <div class="min-w-0">
                        <p class="crm-list-metric-label">Working</p>
                        <p class="crm-list-metric-value tabular-nums">{{ workingCount }}</p>
                    </div>
                </article>

                <article class="crm-list-metric">
                    <span class="crm-list-metric-icon" aria-hidden="true">
                        <Icon icon="lucide:badge-check" />
                    </span>
                    <div class="min-w-0">
                        <p class="crm-list-metric-label">Converted</p>
                        <p class="crm-list-metric-value tabular-nums">
                            {{ summary.converted }}
                            <span v-if="conversionRate !== null" class="crm-list-metric-rate">
                                {{ conversionRate }}%
                            </span>
                        </p>
                    </div>
                </article>
            </div>

            <div class="crm-list-insights">
                <section class="crm-list-panel" aria-labelledby="leads-status-heading">
                    <header class="crm-list-panel-head">
                        <h2 id="leads-status-heading">Pipeline by status</h2>
                        <p>Share of visible leads</p>
                    </header>

                    <p v-if="statusBars.length === 0" class="crm-list-empty" role="status">
                        No status data yet.
                    </p>
                    <div v-else class="crm-list-bars">
                        <div
                            v-for="bar in statusBars"
                            :key="bar.status"
                            class="crm-list-bar-row"
                        >
                            <span class="crm-list-bar-label">{{ bar.status }}</span>
                            <div class="crm-list-bar-track" aria-hidden="true">
                                <div
                                    class="crm-list-bar-fill"
                                    :style="{ width: `${bar.fill}%` }"
                                />
                            </div>
                            <span class="crm-list-bar-count">
                                {{ bar.count }}
                                <span class="crm-list-bar-pct">{{ bar.percent }}%</span>
                            </span>
                        </div>
                    </div>
                </section>

                <section class="crm-list-panel" aria-labelledby="leads-source-heading">
                    <header class="crm-list-panel-head">
                        <h2 id="leads-source-heading">Lead sources</h2>
                        <p>Top channels</p>
                    </header>

                    <p v-if="topSources.length === 0" class="crm-list-empty" role="status">
                        No source data yet.
                    </p>
                    <ul v-else class="crm-list-sources" role="list">
                        <li
                            v-for="source in topSources"
                            :key="source.source"
                            class="crm-list-source"
                        >
                            <span class="crm-list-source-name">{{ source.source }}</span>
                            <span class="crm-list-source-meta tabular-nums">
                                {{ source.count }}
                                <span>{{ source.percent }}%</span>
                            </span>
                        </li>
                    </ul>
                </section>
            </div>

            <section class="crm-list-panel crm-list-panel-table" aria-labelledby="leads-roster-heading">
                <header class="crm-list-toolbar">
                    <div class="min-w-0">
                        <h2 id="leads-roster-heading">All records</h2>
                        <p>
                            {{ filters.view === 'recent' ? 'Recently viewed' : 'Full list' }}
                        </p>
                    </div>

                    <div
                        class="crm-ov-seg"
                        role="tablist"
                        aria-label="Lead list view"
                    >
                        <button
                            v-for="view in views"
                            :key="view.key"
                            type="button"
                            role="tab"
                            class="crm-ov-seg-btn"
                            :class="filters.view === view.key ? 'crm-ov-seg-btn-active' : ''"
                            :aria-selected="filters.view === view.key"
                            @click="changeView(view.key)"
                        >
                            {{ view.label }}
                        </button>
                    </div>
                </header>

                <form class="crm-list-filters" @submit.prevent="applySearch">
                    <div class="crm-list-search">
                        <label class="sr-only" for="lead-search">Search leads</label>
                        <Icon
                            icon="lucide:search"
                            class="crm-list-search-icon"
                            aria-hidden="true"
                        />
                        <TextInput
                            id="lead-search"
                            v-model="search"
                            type="search"
                            class="crm-list-search-input"
                            placeholder="Search name, company, or email"
                        />
                    </div>
                    <div class="crm-list-rows">
                        <label for="lead-per-page">Rows</label>
                        <select
                            id="lead-per-page"
                            :value="filters.per_page"
                            @change="changePerPage"
                        >
                            <option :value="25">25</option>
                            <option :value="50">50</option>
                            <option :value="100">100</option>
                            <option :value="200">200</option>
                        </select>
                    </div>
                    <button type="submit" class="crm-btn-primary">Search</button>
                </form>

                <div
                    v-if="leads.data.length === 0"
                    class="crm-list-empty crm-list-empty-table"
                    role="status"
                >
                    <template v-if="filters.search">
                        No leads match your search.
                    </template>
                    <template v-else-if="filters.view === 'recent'">
                        <p>No recently viewed leads.</p>
                        <button
                            type="button"
                            class="crm-btn-secondary mt-3"
                            @click="changeView('all')"
                        >
                            View all leads
                        </button>
                    </template>
                    <template v-else>
                        No leads yet.
                    </template>
                </div>

                <div v-else class="crm-list-tablewrap">
                    <table class="crm-list-table">
                        <thead>
                            <tr>
                                <th v-for="column in columns" :key="column.key" scope="col">
                                    <Link :href="sortHref(column.key)" class="crm-list-th">
                                        <Icon :icon="column.icon" aria-hidden="true" />
                                        {{ column.label }}
                                        <span
                                            v-if="filters.sort === column.key"
                                            class="crm-list-sort"
                                        >
                                            {{ filters.direction === 'asc' ? '↑' : '↓' }}
                                        </span>
                                    </Link>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="lead in leads.data"
                                :key="lead.id"
                                @click="router.visit(route('leads.show', lead.id))"
                            >
                                <td>
                                    <div class="crm-list-person">
                                        <span class="crm-list-avatar" aria-hidden="true">
                                            {{ initials(lead) }}
                                        </span>
                                        <div class="crm-list-cell-stack">
                                            <Link
                                                :href="route('leads.show', lead.id)"
                                                class="crm-list-name"
                                                @click.stop
                                            >
                                                {{ fullName(lead) }}
                                            </Link>
                                            <span class="crm-list-cell-meta">
                                                {{ display(lead.title) }}
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ display(lead.company) }}</td>
                                <td class="tabular-nums">{{ display(lead.phone) }}</td>
                                <td>{{ display(lead.email) }}</td>
                                <td>{{ display(lead.lead_source) }}</td>
                                <td>
                                    <div class="crm-list-person crm-list-person-sm">
                                        <span class="crm-list-avatar crm-list-avatar-sm" aria-hidden="true">
                                            {{ ownerInitials(lead.owner?.name) }}
                                        </span>
                                        <span>{{ display(lead.owner?.name) }}</span>
                                    </div>
                                </td>
                                <td>
                                    <span :class="statusTagClass(lead.lead_status)">
                                        {{ display(lead.lead_status) }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="crm-list-footer">
                    <PaginationBar :paginator="leads" />
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
