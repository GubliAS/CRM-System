<script setup>
import CrmSelect from '@/Components/CrmSelect.vue';
import PaginationBar from '@/Components/PaginationBar.vue';
import WidgetGlyph from '@/Components/WidgetGlyph.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useReveal } from '@/composables/useReveal';
import { display } from '@/display';
import { Icon } from '@iconify/vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    dashboards: { type: Object, required: true },
    filters: { type: Object, required: true },
    folders: { type: Array, required: true },
    can: { type: Object, required: true },
});

const search = ref(props.filters.search ?? '');
// Shown immediately on click; the server confirms it a moment later.
const folderShown = ref(props.filters.folder);
const pending = ref(false);

const PER_PAGE_OPTIONS = [25, 50, 100].map((n) => ({ value: n, label: `${n} / page` }));

// Banner colours cycle through the same three looks as the Overview stat cards.
const VARIANTS = ['dark', 'blue', 'soft'];

const { observe } = useReveal('.crm-db-card');

watch(
    () => props.filters.folder,
    (value) => {
        folderShown.value = value;
    },
);

let searchTimer = null;

// Search as you type (debounced): every request is a round trip to the server.
watch(search, (value) => {
    if ((value ?? '') === (props.filters.search ?? '')) {
        return;
    }

    clearTimeout(searchTimer);
    searchTimer = setTimeout(applySearch, 400);
});

watch(
    () => props.filters.search,
    (value) => {
        search.value = value ?? '';
    },
);

watch(() => props.dashboards.data, observe);

const pageWidgets = computed(() =>
    props.dashboards.data.reduce((sum, dashboard) => sum + (dashboard.widget_count ?? 0), 0),
);

const sharedOnPage = computed(
    () => props.dashboards.data.filter((dashboard) => dashboard.folder === 'shared').length,
);

// The thumbnail is a fixed-height box; its rows stretch so any layout fills it
// (a one-row dashboard is not left as a thin strip above a blank banner).
function thumbGrid(preview) {
    const rows = Math.max(1, ...preview.map((item) => item.row + item.height));

    return { gridTemplateRows: `repeat(${rows}, minmax(0, 1fr))` };
}

// Thumbnail: the saved layout drawn to scale on a 12-column mini grid.
function thumbStyle(item) {
    return {
        gridColumn: `${item.col + 1} / span ${item.width}`,
        gridRow: `${item.row + 1} / span ${item.height}`,
    };
}

const rangeLabel = computed(
    () =>
        `Showing ${props.dashboards.from ?? 0}–${props.dashboards.to ?? 0} of ${props.dashboards.total}`,
);

function variantFor(index) {
    return VARIANTS[index % VARIANTS.length];
}

function initials(name) {
    const parts = String(name ?? '')
        .trim()
        .split(/\s+/)
        .filter(Boolean);

    if (parts.length === 0) {
        return '?';
    }

    return (parts[0][0] + (parts.length > 1 ? parts[parts.length - 1][0] : '')).toUpperCase();
}

function listQuery(extra = {}) {
    return {
        search: search.value || undefined,
        folder: props.filters.folder,
        per_page: props.filters.per_page,
        ...extra,
    };
}

function visit(extra = {}) {
    router.get(route('dashboards.index'), listQuery(extra), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onStart: () => {
            pending.value = true;
        },
        onFinish: () => {
            pending.value = false;
        },
    });
}

function applySearch() {
    clearTimeout(searchTimer);
    visit();
}

// Each request is a round trip to a slow (remote) database, so start fetching a
// tab's data as soon as the pointer reaches it; the click then reuses it.
function prefetchFolder(folder) {
    if (folder === folderShown.value) {
        return;
    }

    router.prefetch(
        route('dashboards.index'),
        { method: 'get', data: listQuery({ folder }) },
        { cacheFor: '30s' },
    );
}

function changeFolder(folder) {
    if (folder === folderShown.value) {
        return;
    }

    folderShown.value = folder;
    visit({ folder });
}

function changePerPage(value) {
    visit({ per_page: value });
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

        <template #header>
            <div class="min-w-0">
                <h1 class="crm-page-title">Dashboards</h1>
                <p class="crm-page-subtitle">
                    Saved views of your pipeline, cases and activity.
                </p>
            </div>
        </template>

        <div class="crm-page max-w-none px-4 py-4 sm:px-5">
            <!-- Hero -->
            <section class="crm-db-hero">
                <span class="crm-ov-spot-rings" aria-hidden="true" />
                <div class="crm-db-hero-copy">
                    <p class="crm-db-hero-kicker">
                        <Icon icon="lucide:sparkles" aria-hidden="true" />
                        Your workspace
                    </p>
                    <h2 class="crm-db-hero-title">
                        Turn saved reports into <em>live</em> views.
                    </h2>
                    <p class="crm-db-hero-sub">
                        Arrange charts, tables, metrics and gauges on one board, then share it with your team.
                    </p>
                    <Link v-if="can.create" :href="route('dashboards.create')" class="crm-db-hero-cta">
                        <Icon icon="lucide:plus" aria-hidden="true" />
                        New dashboard
                    </Link>
                </div>
                <dl class="crm-db-hero-stats">
                    <div>
                        <dt>Dashboards</dt>
                        <dd class="tabular-nums">{{ dashboards.total }}</dd>
                    </div>
                    <div>
                        <dt>Widgets on this page</dt>
                        <dd class="tabular-nums">{{ pageWidgets }}</dd>
                    </div>
                    <div>
                        <dt>Shared on this page</dt>
                        <dd class="tabular-nums">{{ sharedOnPage }}</dd>
                    </div>
                </dl>
            </section>

            <!-- Toolbar -->
            <div class="crm-db-toolbar">
                <div class="crm-ov-seg" role="tablist" aria-label="Dashboard folders">
                    <button
                        v-for="folder in folders"
                        :key="folder.key"
                        type="button"
                        role="tab"
                        class="crm-ov-seg-btn"
                        :class="folderShown === folder.key ? 'crm-ov-seg-btn-active' : ''"
                        :aria-selected="folderShown === folder.key"
                        @pointerenter="prefetchFolder(folder.key)"
                        @focus="prefetchFolder(folder.key)"
                        @click="changeFolder(folder.key)"
                    >
                        {{ folder.label }}
                    </button>
                </div>

                <form class="crm-db-toolbar-right" @submit.prevent="applySearch">
                    <label class="crm-db-search">
                        <Icon icon="lucide:search" aria-hidden="true" />
                        <input
                            v-model="search"
                            type="search"
                            placeholder="Search name or description"
                            aria-label="Search dashboards"
                        />
                    </label>

                    <CrmSelect
                        variant="pill"
                        align="end"
                        aria-label="Rows per page"
                        :model-value="filters.per_page"
                        :options="PER_PAGE_OPTIONS"
                        @update:model-value="changePerPage"
                    />

                    <Link
                        v-if="can.create"
                        :href="route('dashboards.create')"
                        class="crm-db-new"
                    >
                        <Icon icon="lucide:plus" aria-hidden="true" />
                        New dashboard
                    </Link>
                </form>
            </div>

            <p class="crm-db-range">
                {{ rangeLabel }}
                <span v-if="pending" class="crm-db-loading" role="status">
                    <Icon icon="lucide:loader-circle" class="crm-db-spin" aria-hidden="true" />
                    Loading…
                </span>
            </p>

            <!-- Empty -->
            <div v-if="dashboards.data.length === 0" class="crm-ov-card crm-db-empty">
                <span class="crm-db-empty-icon" aria-hidden="true">
                    <Icon icon="lucide:layout-dashboard" />
                </span>
                <p class="crm-ov-title">No dashboards found</p>
                <p class="crm-ov-sub">Try a different folder or search term.</p>
            </div>

            <!-- Cards -->
            <div v-else class="crm-db-list" :class="pending ? 'crm-db-list-pending' : ''" :aria-busy="pending">
                <article
                    v-for="(dashboard, index) in dashboards.data"
                    :key="dashboard.id"
                    class="crm-db-card"
                >
                    <div class="crm-db-card-banner" :class="`crm-db-card-banner-${variantFor(index)}`">
                        <span class="crm-ov-spot-rings" aria-hidden="true" />
                        <span class="crm-db-card-badges">
                            <span class="crm-db-card-count">
                                <Icon :icon="dashboard.folder === 'shared' ? 'lucide:users' : 'lucide:lock'" aria-hidden="true" />
                                {{ dashboard.folder === 'shared' ? 'Shared' : 'Private' }}
                            </span>
                            <span class="crm-db-card-count">
                                <Icon icon="lucide:layout-grid" aria-hidden="true" />
                                {{ dashboard.widget_count }}
                            </span>
                        </span>
                        <div
                            v-if="dashboard.preview.length"
                            class="crm-db-thumb"
                            :style="thumbGrid(dashboard.preview)"
                            aria-hidden="true"
                        >
                            <span
                                v-for="(item, i) in dashboard.preview"
                                :key="i"
                                class="crm-db-thumb-item"
                                :class="`crm-dbt-${item.type}`"
                                :style="thumbStyle(item)"
                            >
                                <WidgetGlyph :type="item.type" />
                            </span>
                        </div>
                        <div v-else class="crm-db-thumb crm-db-thumb-empty" aria-hidden="true">
                            <Icon icon="lucide:layout-dashboard" />
                        </div>
                    </div>

                    <div class="crm-db-card-body">
                        <Link :href="route('dashboards.show', dashboard.id)" class="crm-db-card-name">
                            {{ dashboard.name }}
                        </Link>
                        <p class="crm-db-card-desc">
                            {{ dashboard.description ? display(dashboard.description) : 'No description' }}
                        </p>

                        <div class="crm-db-card-meta">
                            <span class="crm-db-avatar" aria-hidden="true">
                                {{ initials(dashboard.created_by?.name) }}
                            </span>
                            <span class="min-w-0">
                                <span class="crm-db-card-by">{{ display(dashboard.created_by?.name) }}</span>
                                <span class="crm-db-card-on">
                                    {{ display(dashboard.created_on) }}
                                    <template v-if="!dashboard.is_owner"> · shared with you</template>
                                </span>
                            </span>
                        </div>
                    </div>

                    <div class="crm-db-card-actions">
                        <Link :href="route('dashboards.show', dashboard.id)" class="crm-db-open">
                            Open
                            <Icon icon="lucide:arrow-up-right" aria-hidden="true" />
                        </Link>
                        <span class="crm-db-tools">
                            <Link
                                v-if="dashboard.can.update"
                                :href="route('dashboards.edit', dashboard.id)"
                                class="crm-ov-row-btn"
                                title="Edit"
                                aria-label="Edit dashboard"
                            >
                                <Icon icon="lucide:pencil" />
                            </Link>
                            <button
                                v-if="dashboard.can.clone"
                                type="button"
                                class="crm-ov-row-btn"
                                title="Clone"
                                aria-label="Clone dashboard"
                                @click="cloneDashboard(dashboard)"
                            >
                                <Icon icon="lucide:copy" />
                            </button>
                            <button
                                v-if="dashboard.can.delete"
                                type="button"
                                class="crm-ov-row-btn crm-db-danger"
                                title="Delete"
                                aria-label="Delete dashboard"
                                @click="destroyDashboard(dashboard)"
                            >
                                <Icon icon="lucide:trash-2" />
                            </button>
                        </span>
                    </div>
                </article>
            </div>

            <PaginationBar :paginator="dashboards" class="mt-4" />
        </div>
    </AuthenticatedLayout>
</template>
