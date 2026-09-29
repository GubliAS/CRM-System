<script setup>
import PaginationBar from '@/Components/PaginationBar.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { display } from '@/display';
import { Icon } from '@iconify/vue';
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

        <div class="crm-page">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h1 class="crm-card-title text-h1">
                        <span class="crm-card-title-icon" aria-hidden="true">
                            <Icon icon="solar:widget-4-bold-duotone" />
                        </span>
                        Dashboards
                    </h1>
                    <p class="mt-1 text-small text-text-muted">
                        Showing {{ dashboards.from ?? 0 }}–{{ dashboards.to ?? 0 }} of
                        {{ dashboards.total }}
                    </p>
                </div>
                <Link
                    v-if="can.create"
                    :href="route('dashboards.create')"
                    class="crm-btn-primary gap-2"
                >
                    <Icon icon="solar:add-circle-bold-duotone" class="text-lg" />
                    New dashboard
                </Link>
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                <button
                    v-for="folder in folders"
                    :key="folder.key"
                    type="button"
                    class="crm-view-tab"
                    :class="
                        filters.folder === folder.key
                            ? 'crm-view-tab-active'
                            : 'crm-view-tab-idle'
                    "
                    @click="changeFolder(folder.key)"
                >
                    {{ folder.label }}
                </button>
            </div>

            <form
                class="crm-card mt-4 flex flex-wrap items-end gap-3"
                @submit.prevent="applySearch"
            >
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
                <button type="submit" class="crm-btn-secondary gap-2">
                    <Icon icon="solar:magnifer-bold-duotone" class="text-base" />
                    Search
                </button>
            </form>

            <div v-if="dashboards.data.length === 0" class="crm-card mt-6">
                <div class="crm-empty text-text-muted">
                    <Icon
                        icon="solar:widget-4-bold-duotone"
                        class="mb-2 text-3xl text-secondary"
                    />
                    <p>No dashboards found.</p>
                </div>
            </div>

            <div v-else class="crm-dash-list-grid mt-6">
                <article
                    v-for="dashboard in dashboards.data"
                    :key="dashboard.id"
                    class="crm-dash-card flex flex-col"
                >
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <Link
                                :href="route('dashboards.show', dashboard.id)"
                                class="block truncate text-h3 text-primary hover:text-secondary"
                            >
                                {{ dashboard.name }}
                            </Link>
                            <p
                                v-if="dashboard.description"
                                class="mt-1 line-clamp-2 text-small text-text-muted"
                            >
                                {{ display(dashboard.description) }}
                            </p>
                        </div>
                        <span
                            class="inline-flex shrink-0 items-center gap-1 rounded-md bg-primary-soft px-2 py-1 text-small font-semibold text-primary"
                        >
                            <Icon icon="solar:widget-2-bold-duotone" />
                            {{ dashboard.widget_count }}
                        </span>
                    </div>

                    <dl class="mt-4 grid grid-cols-2 gap-2 text-small text-text-muted">
                        <div>
                            <dt>Created by</dt>
                            <dd class="mt-0.5 font-medium text-text">
                                {{ display(dashboard.created_by?.name) }}
                            </dd>
                        </div>
                        <div>
                            <dt>Created on</dt>
                            <dd class="mt-0.5 font-medium text-text">
                                {{ display(dashboard.created_on) }}
                            </dd>
                        </div>
                    </dl>

                    <div class="mt-auto flex flex-wrap gap-3 border-t border-border pt-3">
                        <Link
                            :href="route('dashboards.show', dashboard.id)"
                            class="inline-flex items-center gap-1 text-small text-secondary underline"
                        >
                            <Icon icon="solar:eye-bold-duotone" />
                            Open
                        </Link>
                        <Link
                            v-if="dashboard.can.update"
                            :href="route('dashboards.edit', dashboard.id)"
                            class="inline-flex items-center gap-1 text-small text-secondary underline"
                        >
                            <Icon icon="solar:pen-bold-duotone" />
                            Edit
                        </Link>
                        <button
                            v-if="dashboard.can.clone"
                            type="button"
                            class="inline-flex items-center gap-1 text-small text-secondary underline"
                            @click="cloneDashboard(dashboard)"
                        >
                            <Icon icon="solar:copy-bold-duotone" />
                            Clone
                        </button>
                        <button
                            v-if="dashboard.can.delete"
                            type="button"
                            class="inline-flex items-center gap-1 text-small text-danger underline"
                            @click="destroyDashboard(dashboard)"
                        >
                            <Icon icon="solar:trash-bin-trash-bold-duotone" />
                            Delete
                        </button>
                    </div>
                </article>
            </div>

            <PaginationBar :paginator="dashboards" class="mt-4" />
        </div>
    </AuthenticatedLayout>
</template>
