<script setup>
import PaginationBar from '@/Components/PaginationBar.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { display } from '@/display';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    reports: { type: Object, required: true },
    filters: { type: Object, required: true },
    folders: { type: Array, required: true },
    can: { type: Object, required: true },
});

const search = ref(props.filters.search ?? '');

watch(
    () => props.filters.search,
    (value) => {
        search.value = value ?? '';
    },
);

function listQuery(extra = {}) {
    return {
        search: search.value || undefined,
        folder: props.filters.folder,
        per_page: props.filters.per_page,
        ...extra,
    };
}

function applySearch() {
    router.get(route('reports.index'), listQuery(), {
        preserveState: true,
        replace: true,
    });
}

function changeFolder(folder) {
    router.get(route('reports.index'), listQuery({ folder }), {
        preserveState: true,
        replace: true,
    });
}

function changePerPage(event) {
    router.get(route('reports.index'), listQuery({ per_page: event.target.value }), {
        preserveState: true,
        replace: true,
    });
}

function destroyReport(report) {
    if (!window.confirm(`Delete report “${report.name}”?`)) {
        return;
    }

    router.delete(route('reports.destroy', report.id));
}
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Reports" />

        <div class="mx-auto max-w-7xl px-4 py-6">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h1 class="text-h1">Reports</h1>
                    <p class="mt-1 text-small text-text-muted">
                        Showing {{ reports.from ?? 0 }}–{{ reports.to ?? 0 }} of {{ reports.total }}
                    </p>
                </div>
                <Link
                    v-if="can.create"
                    :href="route('reports.create')"
                    class="inline-flex min-h-11 items-center rounded-md bg-primary px-4 py-2 text-small font-semibold uppercase tracking-widest text-surface"
                >
                    New report
                </Link>
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                <button
                    v-for="folder in folders"
                    :key="folder.key"
                    type="button"
                    class="inline-flex min-h-11 items-center rounded-md border px-3 text-small"
                    :class="
                        filters.folder === folder.key
                            ? 'border-secondary bg-surface text-primary'
                            : 'border-border bg-surface text-text'
                    "
                    @click="changeFolder(folder.key)"
                >
                    {{ folder.label }}
                </button>
            </div>

            <form class="mt-4 flex flex-wrap items-end gap-3" @submit.prevent="applySearch">
                <div class="min-w-0 flex-1 sm:max-w-xs">
                    <label class="text-small text-text-muted" for="report-search">Search</label>
                    <TextInput
                        id="report-search"
                        v-model="search"
                        type="search"
                        class="mt-1 block w-full"
                        placeholder="Name or description"
                    />
                </div>
                <div>
                    <label class="text-small text-text-muted" for="report-per-page">Rows</label>
                    <select
                        id="report-per-page"
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
                    class="inline-flex min-h-11 items-center rounded-md bg-primary px-4 py-2 text-small font-semibold uppercase tracking-widest text-surface"
                >
                    Search
                </button>
            </form>

            <div class="mt-4 overflow-x-auto rounded-md border border-border bg-surface">
                <table class="min-w-full text-left text-body">
                    <thead class="bg-bg text-small text-text-muted">
                        <tr>
                            <th scope="col" class="px-3 py-2">Report name</th>
                            <th scope="col" class="px-3 py-2">Description</th>
                            <th scope="col" class="px-3 py-2">Folder</th>
                            <th scope="col" class="px-3 py-2">Created by</th>
                            <th scope="col" class="px-3 py-2">Created on</th>
                            <th scope="col" class="px-3 py-2">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="reports.data.length === 0">
                            <td colspan="6" class="px-4 py-8 text-center text-body text-text-muted">
                                No reports in this folder.
                            </td>
                        </tr>
                        <tr
                            v-for="report in reports.data"
                            :key="report.id"
                            class="border-t border-border"
                        >
                            <td class="px-3 py-2">
                                <Link
                                    :href="route('reports.show', report.id)"
                                    class="text-secondary underline"
                                >
                                    {{ report.name }}
                                </Link>
                            </td>
                            <td class="px-3 py-2">{{ display(report.description) }}</td>
                            <td class="px-3 py-2">{{ report.folder_label }}</td>
                            <td class="px-3 py-2">{{ display(report.created_by?.name) }}</td>
                            <td class="px-3 py-2">{{ display(report.created_on) }}</td>
                            <td class="px-3 py-2">
                                <div class="flex flex-wrap gap-2">
                                    <Link
                                        :href="route('reports.show', report.id)"
                                        class="text-small text-secondary underline"
                                    >
                                        Run
                                    </Link>
                                    <Link
                                        v-if="report.can.update"
                                        :href="route('reports.edit', report.id)"
                                        class="text-small text-secondary underline"
                                    >
                                        Edit
                                    </Link>
                                    <button
                                        v-if="report.can.delete"
                                        type="button"
                                        class="text-small text-danger underline"
                                        @click="destroyReport(report)"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                <PaginationBar :paginator="reports" />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
