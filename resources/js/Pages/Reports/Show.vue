<script setup>
import PaginationBar from '@/Components/PaginationBar.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { display } from '@/display';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    report: { type: Object, required: true },
    result: { type: Object, required: true },
    filters: { type: Object, required: true },
    can: { type: Object, required: true },
});

const maxChartValue = computed(() => {
    const points = props.result.chart?.points ?? [];
    const values = points.map((point) => Number(point.value) || 0);

    return Math.max(...values, 1);
});

function barWidth(value) {
    const amount = Number(value) || 0;

    return `${Math.max((amount / maxChartValue.value) * 100, amount > 0 ? 4 : 0)}%`;
}

function runQuery(extra = {}) {
    return {
        sort: props.filters.sort || undefined,
        direction: props.filters.direction,
        per_page: props.filters.per_page,
        ...extra,
    };
}

function sortHref(column) {
    const direction =
        props.filters.sort === column && props.filters.direction === 'asc' ? 'desc' : 'asc';

    return route('reports.show', {
        report: props.report.id,
        ...runQuery({ sort: column, direction }),
    });
}

function changePerPage(event) {
    router.get(
        route('reports.show', props.report.id),
        runQuery({ per_page: event.target.value, page: 1 }),
        { preserveState: true, replace: true },
    );
}

function exportHref(format) {
    return route('reports.export', {
        report: props.report.id,
        format,
        sort: props.filters.sort || undefined,
        direction: props.filters.direction,
    });
}

function destroyReport() {
    if (!window.confirm(`Delete report “${props.report.name}”?`)) {
        return;
    }

    router.delete(route('reports.destroy', props.report.id));
}

function openRow(row) {
    if (!row.record_url) {
        return;
    }

    router.visit(row.record_url);
}
</script>

<template>
    <AuthenticatedLayout>
        <Head :title="report.name" />

        <div class="mx-auto max-w-7xl space-y-6 px-4 py-6">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="text-small text-text-muted">
                        <Link :href="route('reports.index')" class="text-secondary underline">
                            Reports
                        </Link>
                        · {{ report.folder_label }}
                    </p>
                    <h1 class="mt-1 text-h1">{{ report.name }}</h1>
                    <p v-if="report.description" class="mt-1 text-body text-text-muted">
                        {{ report.description }}
                    </p>
                    <p class="mt-2 text-small text-text-muted">
                        {{ result.total }} record{{ result.total === 1 ? '' : 's' }}
                        <span v-if="result.from">
                            · showing {{ result.from }}–{{ result.to }}
                        </span>
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a
                        v-if="can.export"
                        :href="exportHref('csv')"
                        class="inline-flex min-h-11 items-center rounded-md border border-border bg-surface px-3 text-small text-text"
                    >
                        CSV
                    </a>
                    <a
                        v-if="can.export"
                        :href="exportHref('excel')"
                        class="inline-flex min-h-11 items-center rounded-md border border-border bg-surface px-3 text-small text-text"
                    >
                        Excel
                    </a>
                    <a
                        v-if="can.export"
                        :href="exportHref('pdf')"
                        class="inline-flex min-h-11 items-center rounded-md border border-border bg-surface px-3 text-small text-text"
                    >
                        PDF
                    </a>
                    <Link
                        v-if="can.update"
                        :href="route('reports.edit', report.id)"
                        class="inline-flex min-h-11 items-center rounded-md border border-border bg-surface px-3 text-small text-text"
                    >
                        Edit
                    </Link>
                    <button
                        v-if="can.delete"
                        type="button"
                        class="inline-flex min-h-11 items-center rounded-md border border-danger bg-surface px-3 text-small text-danger"
                        @click="destroyReport"
                    >
                        Delete
                    </button>
                </div>
            </div>

            <section
                v-if="result.chart && result.chart.points?.length"
                class="rounded-md border border-border bg-surface p-4"
            >
                <h2 class="text-h2">Chart</h2>
                <div
                    v-if="result.chart.type === 'metric'"
                    class="mt-4 text-h1 text-primary"
                >
                    {{ display(result.chart.points[0]?.value) }}
                    <span class="text-body text-text-muted">
                        {{ result.chart.points[0]?.label }}
                    </span>
                </div>
                <ul v-else class="mt-4 space-y-3">
                    <li v-for="point in result.chart.points" :key="point.label">
                        <div class="flex flex-wrap items-baseline justify-between gap-2">
                            <span class="text-body">{{ point.label }}</span>
                            <span class="text-small text-text-muted">{{ point.value }}</span>
                        </div>
                        <div class="mt-1 h-2 rounded bg-bg">
                            <div
                                class="h-2 rounded bg-secondary"
                                :style="{ width: barWidth(point.value) }"
                            />
                        </div>
                    </li>
                </ul>
            </section>

            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <label class="text-small text-text-muted" for="report-run-per-page">Rows</label>
                    <select
                        id="report-run-per-page"
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
            </div>

            <div class="overflow-x-auto rounded-md border border-border bg-surface">
                <table class="min-w-full text-left text-body">
                    <thead class="bg-bg text-small text-text-muted">
                        <tr>
                            <th
                                v-for="column in result.columns"
                                :key="column.key"
                                scope="col"
                                class="px-3 py-2"
                            >
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
                        <tr v-if="result.rows.length === 0">
                            <td
                                :colspan="Math.max(result.columns.length, 1)"
                                class="px-4 py-8 text-center text-body text-text-muted"
                            >
                                No rows match this report.
                            </td>
                        </tr>
                        <tr
                            v-for="(row, index) in result.rows"
                            :key="row.record_id ?? index"
                            class="border-t border-border"
                            :class="row.record_url ? 'cursor-pointer hover:bg-bg' : ''"
                            @click="openRow(row)"
                        >
                            <td
                                v-for="column in result.columns"
                                :key="column.key"
                                class="px-3 py-2"
                            >
                                <span
                                    v-if="column.key === result.columns[0]?.key && row.record_url"
                                    class="text-secondary underline"
                                >
                                    {{ display(row[column.key]) }}
                                </span>
                                <span v-else>{{ display(row[column.key]) }}</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <PaginationBar
                :paginator="{
                    links: result.links ?? [],
                    last_page: result.last_page,
                }"
            />
        </div>
    </AuthenticatedLayout>
</template>
