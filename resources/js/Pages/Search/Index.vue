<script setup>
import PaginationBar from '@/Components/PaginationBar.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    query: { type: String, default: '' },
    type: { type: String, default: 'all' },
    types: { type: Array, required: true },
    results: { type: Object, default: null },
    groups: { type: Object, default: null },
    totals: { type: Object, default: null },
    recentSearches: { type: Array, default: () => [] },
    filters: { type: Object, required: true },
});

const search = ref(props.query ?? '');

watch(
    () => props.query,
    (value) => {
        search.value = value ?? '';
    },
);

const typeLabels = {
    all: 'All',
    leads: 'Leads',
    accounts: 'Accounts',
    contacts: 'Contacts',
    opportunities: 'Opportunities',
    cases: 'Cases',
};

const filterTypes = ['all', ...props.types];

function listQuery(extra = {}) {
    return {
        q: search.value || undefined,
        type: props.filters.type,
        per_page: props.filters.per_page,
        ...extra,
    };
}

function applySearch() {
    router.get(route('search.index'), listQuery(), {
        preserveState: true,
        replace: true,
    });
}

function changeType(type) {
    router.get(route('search.index'), listQuery({ type }), {
        preserveState: true,
        replace: true,
    });
}

function useRecent(term) {
    search.value = term;
    router.get(route('search.index'), listQuery({ q: term }), {
        preserveState: true,
        replace: true,
    });
}
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Search" />

        <div class="crm-page">
            <h1 class="text-h1">Search</h1>

            <form class="mt-4 flex flex-wrap items-end gap-3" @submit.prevent="applySearch">
                <div class="min-w-0 flex-1 sm:max-w-md">
                    <label class="text-small text-text-muted" for="search-q">Query</label>
                    <TextInput
                        id="search-q"
                        v-model="search"
                        type="search"
                        class="mt-1 block w-full"
                    />
                </div>
                <button
                    type="submit"
                    class="crm-btn-primary"
                >
                    Search
                </button>
            </form>

            <div
                v-if="recentSearches.length && (!query || query.length < 2)"
                class="mt-4"
            >
                <p class="text-small text-text-muted">Recent searches</p>
                <ul class="mt-2 flex flex-wrap gap-2">
                    <li v-for="term in recentSearches" :key="term">
                        <button
                            type="button"
                            class="inline-flex min-h-11 items-center rounded-md border border-border bg-surface px-3 text-small text-text"
                            @click="useRecent(term)"
                        >
                            {{ term }}
                        </button>
                    </li>
                </ul>
            </div>

            <div v-if="query.length >= 2" class="mt-4 flex flex-wrap gap-2">
                <button
                    v-for="filterType in filterTypes"
                    :key="filterType"
                    type="button"
                    class="crm-view-tab"
                    :class="
                        type === filterType
                            ? 'crm-view-tab-active'
                            : 'crm-view-tab-idle'
                    "
                    @click="changeType(filterType)"
                >
                    {{ typeLabels[filterType] ?? filterType }}
                    <span
                        v-if="filterType !== 'all' && totals?.[filterType] != null"
                        class="ms-1 text-text-muted"
                    >
                        ({{ totals[filterType] }})
                    </span>
                </button>
            </div>

            <p
                v-if="query.length > 0 && query.length < 2"
                class="mt-6 text-body text-text-muted"
            >
                Enter at least 2 characters to search.
            </p>

            <div v-else-if="type === 'all' && groups" class="mt-6 space-y-8">
                <section v-for="objectType in types" :key="objectType">
                    <div class="flex flex-wrap items-baseline justify-between gap-2">
                        <h2 class="text-h2">{{ typeLabels[objectType] }}</h2>
                        <button
                            v-if="totals?.[objectType] > (groups[objectType]?.length ?? 0)"
                            type="button"
                            class="text-small text-secondary"
                            @click="changeType(objectType)"
                        >
                            View all {{ totals[objectType] }}
                        </button>
                    </div>
                    <p
                        v-if="!(groups[objectType]?.length)"
                        class="mt-2 text-body text-text-muted"
                    >
                        No matches
                    </p>
                    <ul v-else class="mt-3 divide-y divide-border border border-border bg-surface">
                        <li
                            v-for="item in groups[objectType]"
                            :key="`${objectType}-${item.id}`"
                            class="px-4 py-3"
                        >
                            <Link
                                v-if="item.url"
                                :href="item.url"
                                class="block text-body text-secondary"
                            >
                                {{ item.title }}
                            </Link>
                            <span v-else class="block text-body text-text">{{ item.title }}</span>
                            <p
                                v-if="item.subtitle"
                                class="text-small text-text-muted"
                            >
                                {{ item.subtitle }}
                            </p>
                        </li>
                    </ul>
                </section>
            </div>

            <div v-else-if="results" class="mt-6">
                <p class="text-small text-text-muted">
                    Showing {{ results.from ?? 0 }}–{{ results.to ?? 0 }} of {{ results.total }}
                </p>
                <ul
                    v-if="results.data?.length"
                    class="mt-3 divide-y divide-border border border-border bg-surface"
                >
                    <li
                        v-for="item in results.data"
                        :key="`${item.type}-${item.id}`"
                        class="px-4 py-3"
                    >
                        <Link
                            v-if="item.url"
                            :href="item.url"
                            class="block text-body text-secondary"
                        >
                            {{ item.title }}
                        </Link>
                        <span v-else class="block text-body text-text">{{ item.title }}</span>
                        <p v-if="item.subtitle" class="text-small text-text-muted">
                            {{ item.subtitle }}
                        </p>
                    </li>
                </ul>
                <p v-else class="mt-3 text-body text-text-muted">No matches</p>
                <div class="mt-4">
                    <PaginationBar :paginator="results" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
