<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { display } from '@/display';
import { Icon } from '@iconify/vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    dashboard: { type: Object, required: true },
    widgets: { type: Array, required: true },
    filters: { type: Object, required: true },
    owners: { type: Array, required: true },
    can: { type: Object, required: true },
});

const dateFrom = ref(props.filters.date_from ?? '');
const dateTo = ref(props.filters.date_to ?? '');
const ownerId = ref(props.filters.owner_id ?? '');

watch(
    () => props.filters,
    (value) => {
        dateFrom.value = value.date_from ?? '';
        dateTo.value = value.date_to ?? '';
        ownerId.value = value.owner_id ?? '';
    },
);

const maxChartValue = computed(() => {
    let max = 1;
    for (const widget of props.widgets) {
        const points = widget.result?.chart?.points ?? [];
        for (const point of points) {
            max = Math.max(max, Number(point.value) || 0);
        }
        if (widget.type === 'gauge' || widget.type === 'metric') {
            max = Math.max(max, Number(widget.result?.value) || 0);
        }
    }

    return max;
});

const widgetTypeIcon = {
    metric: 'solar:hashtag-bold-duotone',
    gauge: 'solar:compass-bold-duotone',
    chart: 'solar:chart-bold-duotone',
    table: 'solar:table-bold-duotone',
};

function applyFilters() {
    router.get(
        route('dashboards.show', props.dashboard.id),
        {
            date_from: dateFrom.value || undefined,
            date_to: dateTo.value || undefined,
            owner_id: ownerId.value || undefined,
        },
        { preserveState: true, replace: true },
    );
}

function refresh() {
    router.reload({ only: ['widgets', 'filters'] });
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

function barWidth(value) {
    const amount = Number(value) || 0;

    return `${Math.max((amount / maxChartValue.value) * 100, amount > 0 ? 4 : 0)}%`;
}

function gaugePercent(value) {
    const amount = Number(value) || 0;
    const pct = Math.min(100, Math.round((amount / Math.max(maxChartValue.value, 1)) * 100));

    return `${pct}%`;
}

function typeIcon(type) {
    return widgetTypeIcon[type] ?? 'solar:widget-2-bold-duotone';
}
</script>

<template>
    <AuthenticatedLayout>
        <Head :title="dashboard.name" />

        <div class="crm-page space-y-6">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="inline-flex items-center gap-1 text-small text-text-muted">
                        <Icon icon="solar:widget-4-bold-duotone" />
                        <Link :href="route('dashboards.index')" class="text-secondary underline">
                            Dashboards
                        </Link>
                    </p>
                    <h1 class="mt-1 text-h1">{{ dashboard.name }}</h1>
                    <p v-if="dashboard.description" class="mt-1 text-body text-text-muted">
                        {{ dashboard.description }}
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button type="button" class="crm-btn-secondary gap-2" @click="refresh">
                        <Icon icon="solar:refresh-bold-duotone" class="text-base" />
                        Refresh
                    </button>
                    <Link
                        v-if="can.update"
                        :href="route('dashboards.edit', dashboard.id)"
                        class="crm-btn-secondary gap-2"
                    >
                        <Icon icon="solar:pen-bold-duotone" class="text-base" />
                        Edit
                    </Link>
                    <button
                        v-if="can.clone"
                        type="button"
                        class="crm-btn-secondary gap-2"
                        @click="cloneDashboard"
                    >
                        <Icon icon="solar:copy-bold-duotone" class="text-base" />
                        Clone
                    </button>
                    <button
                        v-if="can.delete"
                        type="button"
                        class="crm-btn-secondary gap-2 text-danger"
                        @click="destroyDashboard"
                    >
                        <Icon icon="solar:trash-bin-trash-bold-duotone" class="text-base" />
                        Delete
                    </button>
                </div>
            </div>

            <form
                class="crm-card flex flex-wrap items-end gap-3"
                @submit.prevent="applyFilters"
            >
                <div>
                    <label class="text-small text-text-muted" for="date-from">Date from</label>
                    <input
                        id="date-from"
                        v-model="dateFrom"
                        type="date"
                        class="mt-1 block min-h-11 rounded-md border-border bg-surface text-body text-text"
                    />
                </div>
                <div>
                    <label class="text-small text-text-muted" for="date-to">Date to</label>
                    <input
                        id="date-to"
                        v-model="dateTo"
                        type="date"
                        class="mt-1 block min-h-11 rounded-md border-border bg-surface text-body text-text"
                    />
                </div>
                <div>
                    <label class="text-small text-text-muted" for="owner-id">Owner</label>
                    <select
                        id="owner-id"
                        v-model="ownerId"
                        class="mt-1 block min-h-11 rounded-md border-border bg-surface text-body text-text"
                    >
                        <option value="">All owners</option>
                        <option v-for="owner in owners" :key="owner.id" :value="owner.id">
                            {{ owner.name }}
                        </option>
                    </select>
                </div>
                <button type="submit" class="crm-btn-primary gap-2">
                    <Icon icon="solar:filter-bold-duotone" class="text-base" />
                    Apply filters
                </button>
            </form>

            <div v-if="widgets.length === 0" class="crm-card text-text-muted">
                <div class="crm-empty">
                    <Icon
                        icon="solar:widget-add-bold-duotone"
                        class="mb-2 text-3xl text-secondary"
                    />
                    <p>
                        This dashboard has no widgets yet.
                        <Link
                            v-if="can.update"
                            :href="route('dashboards.edit', dashboard.id)"
                            class="text-secondary underline"
                        >
                            Add widgets
                        </Link>
                    </p>
                </div>
            </div>

            <div v-else class="crm-dash-grid">
                <section
                    v-for="widget in widgets"
                    :key="widget.id"
                    class="crm-dash-card"
                >
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <div>
                            <h2 class="crm-card-title text-h2">
                                <span class="crm-card-title-icon" aria-hidden="true">
                                    <Icon :icon="typeIcon(widget.type)" />
                                </span>
                                {{ widget.title }}
                            </h2>
                            <p class="mt-1 text-small capitalize text-text-muted">
                                {{ widget.type }}
                            </p>
                        </div>
                        <Link
                            v-if="widget.report_url"
                            :href="widget.report_url"
                            class="inline-flex items-center gap-1 text-small text-secondary underline"
                        >
                            <Icon icon="solar:document-text-bold-duotone" />
                            Open report
                        </Link>
                    </div>

                    <p v-if="widget.error" class="mt-4 text-body text-danger">{{ widget.error }}</p>

                    <div v-else-if="widget.type === 'metric'" class="mt-6">
                        <p class="text-h1 text-primary">{{ display(widget.result?.value) }}</p>
                        <p class="text-small text-text-muted">{{ widget.result?.label }}</p>
                    </div>

                    <div v-else-if="widget.type === 'gauge'" class="mt-6 space-y-2">
                        <div class="h-3 w-full overflow-hidden rounded-md bg-bg">
                            <div
                                class="h-full rounded-md bg-secondary"
                                :style="{ width: gaugePercent(widget.result?.value) }"
                            />
                        </div>
                        <p class="text-h2 text-primary">{{ display(widget.result?.value) }}</p>
                        <p class="text-small text-text-muted">{{ widget.result?.label }}</p>
                    </div>

                    <div v-else-if="widget.type === 'chart'" class="mt-4 space-y-2">
                        <div
                            v-for="(point, index) in widget.result?.chart?.points ?? []"
                            :key="`${widget.id}-${index}`"
                            class="space-y-1"
                        >
                            <div class="flex justify-between text-small">
                                <span>{{ point.label }}</span>
                                <span>{{ point.value }}</span>
                            </div>
                            <div class="h-2 w-full overflow-hidden rounded-md bg-bg">
                                <div
                                    class="h-full rounded-md bg-secondary"
                                    :style="{ width: barWidth(point.value) }"
                                />
                            </div>
                        </div>
                        <p
                            v-if="(widget.result?.chart?.points ?? []).length === 0"
                            class="text-small text-text-muted"
                        >
                            No chart data.
                        </p>
                    </div>

                    <div v-else class="mt-4 overflow-x-auto">
                        <table class="min-w-full text-left text-small">
                            <thead class="border-b border-border text-text-muted">
                                <tr>
                                    <th
                                        v-for="column in widget.result?.columns ?? []"
                                        :key="column.key"
                                        class="px-2 py-1 font-medium"
                                    >
                                        {{ column.label }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(row, rowIndex) in widget.result?.rows ?? []"
                                    :key="`${widget.id}-row-${rowIndex}`"
                                    class="border-b border-border"
                                >
                                    <td
                                        v-for="column in widget.result?.columns ?? []"
                                        :key="column.key"
                                        class="px-2 py-1"
                                    >
                                        {{ display(row[column.key]) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <p
                            v-if="(widget.result?.rows ?? []).length === 0"
                            class="mt-2 text-small text-text-muted"
                        >
                            No rows.
                        </p>
                    </div>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
