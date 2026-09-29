<script setup>
import CrmSelect from '@/Components/CrmSelect.vue';
import DashboardWidget from '@/Components/DashboardWidget.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useReveal } from '@/composables/useReveal';
import { readingOrder } from '@/dashboard/layout';
import { buildCards } from '@/dashboard/widgets';
import { Icon } from '@iconify/vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = defineProps({
    dashboard: { type: Object, required: true },
    widgets: { type: Array, required: true },
    filters: { type: Object, required: true },
    owners: { type: Array, required: true },
    can: { type: Object, required: true },
});

const REFRESH_OPTIONS = [
    { minutes: 0, label: 'Auto-refresh off' },
    { minutes: 5, label: 'Every 5 min' },
    { minutes: 10, label: 'Every 10 min' },
    { minutes: 30, label: 'Every 30 min' },
    { minutes: 60, label: 'Every 60 min' },
];
const REFRESH_KEY = 'crm-dashboard-refresh';
const REFRESH_SELECT_OPTIONS = REFRESH_OPTIONS.map((option) => ({
    value: option.minutes,
    label: option.label,
    icon: option.minutes === 0 ? 'lucide:timer-off' : 'lucide:timer-reset',
}));
const filterKey = () => `crm-dashboard-filters-${props.dashboard.id}`;

const dateFrom = ref(props.filters.date_from ?? '');
const dateTo = ref(props.filters.date_to ?? '');
const ownerId = ref(props.filters.owner_id ?? '');
const refreshing = ref(false);
const refreshMinutes = ref(0);
const updatedAt = ref(new Date());
let timer = null;

const { observe } = useReveal('.crm-ov-card, .crm-ov-stat');

watch(
    () => props.filters,
    (value) => {
        dateFrom.value = value.date_from ?? '';
        dateTo.value = value.date_to ?? '';
        ownerId.value = value.owner_id ?? '';
    },
);

watch(() => props.widgets, observe);

/* Session-persistent filters and remembered auto-refresh ---------------- */

function readStore(store, key) {
    try {
        return store.getItem(key);
    } catch {
        return null;
    }
}

function writeStore(store, key, value) {
    try {
        if (value === null) {
            store.removeItem(key);
        } else {
            store.setItem(key, value);
        }
    } catch {
        // Storage can be blocked (private windows); the dashboard still works.
    }
}

onMounted(() => {
    const noneApplied = !props.filters.date_from && !props.filters.date_to && !props.filters.owner_id;
    const stored = readStore(sessionStorage, filterKey());

    if (noneApplied && stored) {
        try {
            const saved = JSON.parse(stored);

            if (saved.date_from || saved.date_to || saved.owner_id) {
                router.get(route('dashboards.show', props.dashboard.id), saved, {
                    preserveState: true,
                    replace: true,
                });
            }
        } catch {
            writeStore(sessionStorage, filterKey(), null);
        }
    }

    const remembered = Number(readStore(localStorage, REFRESH_KEY));

    if (REFRESH_OPTIONS.some((option) => option.minutes === remembered)) {
        refreshMinutes.value = remembered;
    }

    schedule();
});

onBeforeUnmount(() => clearInterval(timer));

function schedule() {
    clearInterval(timer);
    timer = null;

    if (refreshMinutes.value > 0) {
        timer = setInterval(refresh, refreshMinutes.value * 60_000);
    }
}

function setAutoRefresh(value) {
    refreshMinutes.value = Number(value);
    writeStore(localStorage, REFRESH_KEY, String(refreshMinutes.value));
    schedule();
}

/* Widgets ---------------------------------------------------------------- */

// Widgets in reading order, decorated by the same code the builder uses.
const cards = computed(() =>
    buildCards(readingOrder(props.widgets.map((widget) => ({ ...widget, ...widget.layout })))),
);

const folderInfo = computed(() =>
    props.dashboard.folder === 'shared'
        ? { icon: 'lucide:users', label: 'Shared' }
        : { icon: 'lucide:lock', label: 'Private' },
);

const updatedLabel = computed(() =>
    updatedAt.value.toLocaleTimeString(undefined, { hour: 'numeric', minute: '2-digit' }),
);

const ownerOptions = computed(() => [
    { value: '', label: 'All owners' },
    ...props.owners.map((owner) => ({ value: owner.id, label: owner.name })),
]);

const hasFilters = computed(() => !!(dateFrom.value || dateTo.value || ownerId.value));

function currentFilters() {
    return {
        date_from: dateFrom.value || undefined,
        date_to: dateTo.value || undefined,
        owner_id: ownerId.value || undefined,
    };
}

function applyFilters() {
    const filters = currentFilters();

    writeStore(
        sessionStorage,
        filterKey(),
        Object.values(filters).some(Boolean) ? JSON.stringify(filters) : null,
    );

    router.get(route('dashboards.show', props.dashboard.id), filters, {
        preserveState: true,
        replace: true,
        onSuccess: () => {
            updatedAt.value = new Date();
        },
    });
}

function clearFilters() {
    dateFrom.value = '';
    dateTo.value = '';
    ownerId.value = '';
    applyFilters();
}

function refresh() {
    if (refreshing.value) {
        return;
    }

    refreshing.value = true;
    router.reload({
        only: ['widgets', 'filters'],
        onSuccess: () => {
            updatedAt.value = new Date();
        },
        onFinish: () => {
            refreshing.value = false;
        },
    });
}

function printDashboard() {
    window.print();
}

function cloneDashboard() {
    router.post(route('dashboards.clone', props.dashboard.id));
}

function destroyDashboard() {
    if (!window.confirm(`Delete dashboard “${props.dashboard.name}”?`)) {
        return;
    }

    router.delete(route('dashboards.destroy', props.dashboard.id));
}

</script>

<template>
    <AuthenticatedLayout>
        <Head :title="dashboard.name" />

        <template #header>
            <div class="min-w-0">
                <h1 class="crm-page-title truncate">{{ dashboard.name }}</h1>
                <p class="crm-page-subtitle truncate">
                    {{ dashboard.description || 'Live widgets from your saved reports.' }}
                </p>
            </div>
        </template>

        <div class="crm-page crm-db-page max-w-none px-4 py-4 sm:px-5">
            <!-- Print-only title (the app chrome is hidden when printing) -->
            <div class="crm-db-print-title">
                <h1>{{ dashboard.name }}</h1>
                <p>
                    {{ dashboard.description }}
                    <span v-if="hasFilters">· filtered</span>
                    · printed {{ new Date().toLocaleString() }}
                </p>
            </div>

            <!-- Identity + actions -->
            <div class="crm-db-toolbar crm-db-noprint">
                <div class="crm-db-chips">
                    <Link :href="route('dashboards.index')" class="crm-db-back">
                        <Icon icon="lucide:arrow-left" aria-hidden="true" />
                        All dashboards
                    </Link>
                    <span class="crm-db-chip">
                        <Icon :icon="folderInfo.icon" aria-hidden="true" />
                        {{ folderInfo.label }}
                    </span>
                    <span class="crm-db-chip">
                        <Icon icon="lucide:layout-grid" aria-hidden="true" />
                        {{ widgets.length }} {{ widgets.length === 1 ? 'widget' : 'widgets' }}
                    </span>
                    <span v-if="dashboard.owner" class="crm-db-chip">
                        <Icon icon="lucide:user-round" aria-hidden="true" />
                        {{ dashboard.owner.name }}
                    </span>
                </div>

                <div class="crm-db-toolbar-right">
                    <button type="button" class="crm-db-ghost" :disabled="refreshing" @click="refresh">
                        <Icon icon="lucide:refresh-cw" :class="refreshing ? 'crm-db-spin' : ''" aria-hidden="true" />
                        Refresh
                    </button>
                    <CrmSelect
                        variant="pill"
                        align="end"
                        aria-label="Auto-refresh"
                        :model-value="refreshMinutes"
                        :options="REFRESH_SELECT_OPTIONS"
                        :min-width="190"
                        @update:model-value="setAutoRefresh"
                    />
                    <button type="button" class="crm-db-ghost" @click="printDashboard">
                        <Icon icon="lucide:printer" aria-hidden="true" />
                        Print
                    </button>
                    <button v-if="can.clone" type="button" class="crm-db-ghost" @click="cloneDashboard">
                        <Icon icon="lucide:copy" aria-hidden="true" />
                        Clone
                    </button>
                    <button
                        v-if="can.delete"
                        type="button"
                        class="crm-db-ghost crm-db-ghost-danger"
                        @click="destroyDashboard"
                    >
                        <Icon icon="lucide:trash-2" aria-hidden="true" />
                        Delete
                    </button>
                    <Link v-if="can.update" :href="route('dashboards.edit', dashboard.id)" class="crm-db-new">
                        <Icon icon="lucide:pencil" aria-hidden="true" />
                        Edit
                    </Link>
                </div>
            </div>

            <!-- Global filters -->
            <form class="crm-db-filters crm-db-noprint" @submit.prevent="applyFilters">
                <span class="crm-db-filters-label">
                    <Icon icon="lucide:sliders-horizontal" aria-hidden="true" />
                    Filters
                </span>

                <label class="crm-db-field">
                    <span>From</span>
                    <input v-model="dateFrom" type="date" aria-label="Date from" />
                </label>
                <label class="crm-db-field">
                    <span>To</span>
                    <input v-model="dateTo" type="date" aria-label="Date to" />
                </label>
                <div class="crm-db-field">
                    <span>Owner</span>
                    <CrmSelect
                        v-model="ownerId"
                        variant="bare"
                        aria-label="Owner"
                        :options="ownerOptions"
                        :min-width="200"
                    />
                </div>

                <button type="submit" class="crm-db-new">
                    <Icon icon="lucide:check" aria-hidden="true" />
                    Apply
                </button>
                <button v-if="hasFilters" type="button" class="crm-db-ghost" @click="clearFilters">Clear</button>

                <span class="crm-db-updated" aria-live="polite">
                    <span class="crm-db-live" aria-hidden="true" />
                    Updated {{ updatedLabel }}
                </span>
            </form>

            <!-- Empty -->
            <div v-if="widgets.length === 0" class="crm-ov-card crm-db-empty">
                <span class="crm-db-empty-icon" aria-hidden="true">
                    <Icon icon="lucide:layout-grid" />
                </span>
                <p class="crm-ov-title">This dashboard has no widgets yet</p>
                <p class="crm-ov-sub">
                    <Link v-if="can.update" :href="route('dashboards.edit', dashboard.id)" class="crm-ov-link-btn">
                        Add widgets
                    </Link>
                </p>
            </div>

            <!-- Widgets, placed on the saved 12-column layout -->
            <div v-else class="crm-db-canvas">
                <DashboardWidget v-for="card in cards" :key="card.widget.id" :card="card" />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
