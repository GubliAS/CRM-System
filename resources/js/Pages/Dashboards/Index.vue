<script setup>
import PaginationBar from '@/Components/PaginationBar.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { display } from '@/display';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    dashboards: { type: Object, required: true },
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
    router.get(route('dashboards.index'), listQuery(), {
        preserveState: true,
        replace: true,
    });
}

function changeFolder(folder) {
    router.get(route('dashboards.index'), listQuery({ folder }), {
        preserveState: true,
        replace: true,
    });
}

function changePerPage(event) {
    router.get(route('dashboards.index'), listQuery({ per_page: event.target.value }), {
        preserveState: true,
        replace: true,
    });
}

function destroyDashboard(dashboard) {
    if (!window.confirm(`Delete dashboard “${dashboard.name}”?`)) {
        return;
    }

    router.delete(route('dashboards.destroy', dashboard.id));
}

function cloneDashboard(dashboard) {
    router.post(route('dashboards.clone', dashboard.id));
}
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Dashboards" />

        <div class="mx-auto max-w-7xl px-4 py-6">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h1 class="text-h1">Dashboards</h1>
                    <p class="mt-1 text-small text-text-muted">
                        Showing {{ dashboards.from ?? 0 }}–{{ dashboards.to ?? 0 }} of
                        {{ dashboards.total }}
                    </p>
                </div>
                <Link
                    v-if="can.create"
                    :href="route('dashboards.create')"
                    class="inline-flex min-h-11 items-center rounded-md bg-primary px-4 py-2 text-small font-semibold uppercase tracking-widest text-surface"
                >
                    New dashboard
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
                    <label class="text-small text-text-muted" for="dashboard-search">Search</label>
                    <TextInput
                        id="dashboard-search"
                        v-model="search"
                        type="search"
                        class="mt-1 block w-full"
                        placeholder="Name or description"
                    />
                </div>
                <div>
                    <label class="text-small text-text-muted" for="dashboard-per-page">Rows</label>
                    <select
                        id="dashboard-per-page"
                        class="mt-1 block min-h-11 rounded-md border-border bg-surface text-body text-text"
                        :value="filters.per_page"
                        @change="changePerPage"
                    >
                        <option :value="25">25</option>
                        <option :value="50">50</option>
                        <option :value="100">100</option>
                    </select>
                </div>
                <button
                    type="submit"
                    class="inline-flex min-h-11 items-center rounded-md border border-border bg-surface px-4 text-small text-text"
                >
                    Search
                </button>
            </form>

            <div class="mt-6 overflow-x-auto border border-border bg-surface">
                <table class="min-w-full text-left text-body">
                    <thead class="border-b border-border text-small text-text-muted">
                        <tr>
                            <th class="px-3 py-2 font-medium">Name</th>
                            <th class="px-3 py-2 font-medium">Widgets</th>
                            <th class="px-3 py-2 font-medium">Created by</th>
                            <th class="px-3 py-2 font-medium">Created on</th>
                            <th class="px-3 py-2 font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="dashboards.data.length === 0">
                            <td colspan="5" class="px-3 py-6 text-text-muted">No dashboards found.</td>
                        </tr>
                        <tr
                            v-for="dashboard in dashboards.data"
                            :key="dashboard.id"
                            class="border-b border-border"
                        >
                            <td class="px-3 py-3">
                                <Link
                                    :href="route('dashboards.show', dashboard.id)"
                                    class="font-medium text-secondary underline"
                                >
                                    {{ dashboard.name }}
                                </Link>
                                <p
                                    v-if="dashboard.description"
                                    class="mt-1 text-small text-text-muted"
                                >
                                    {{ display(dashboard.description) }}
                                </p>
                            </td>
                            <td class="px-3 py-3">{{ dashboard.widget_count }}</td>
                            <td class="px-3 py-3">{{ display(dashboard.created_by?.name) }}</td>
                            <td class="px-3 py-3">{{ display(dashboard.created_on) }}</td>
                            <td class="px-3 py-3">
                                <div class="flex flex-wrap gap-2">
                                    <Link
                                        v-if="dashboard.can.update"
                                        :href="route('dashboards.edit', dashboard.id)"
                                        class="text-small text-secondary underline"
                                    >
                                        Edit
                                    </Link>
                                    <button
                                        v-if="dashboard.can.clone"
                                        type="button"
                                        class="text-small text-secondary underline"
                                        @click="cloneDashboard(dashboard)"
                                    >
                                        Clone
                                    </button>
                                    <button
                                        v-if="dashboard.can.delete"
                                        type="button"
                                        class="text-small text-danger underline"
                                        @click="destroyDashboard(dashboard)"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <PaginationBar :links="dashboards.links" class="mt-4" />
        </div>
    </AuthenticatedLayout>
</template>
