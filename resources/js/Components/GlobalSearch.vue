<script setup>
import { Link, router } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

defineProps({
    variant: {
        type: String,
        default: 'default',
    },
});

const query = ref('');
const open = ref(false);
const loading = ref(false);
const results = ref({});
const recentSearches = ref([]);
const root = ref(null);
let debounceTimer = null;
let abortController = null;

const hasTypedResults = computed(() => {
    return Object.values(results.value).some((rows) => Array.isArray(rows) && rows.length > 0);
});

const showPanel = computed(() => {
    return open.value && (query.value.length >= 2 || recentSearches.value.length > 0);
});

const sectionOrder = ['leads', 'accounts', 'contacts', 'opportunities', 'cases'];

const sectionLabels = {
    leads: 'Leads',
    accounts: 'Accounts',
    contacts: 'Contacts',
    opportunities: 'Opportunities',
    cases: 'Cases',
};

watch(query, (value) => {
    if (debounceTimer) {
        clearTimeout(debounceTimer);
    }

    debounceTimer = setTimeout(() => {
        fetchSuggestions(value);
    }, 250);
});

onMounted(() => {
    document.addEventListener('click', onDocumentClick);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', onDocumentClick);

    if (debounceTimer) {
        clearTimeout(debounceTimer);
    }

    abortController?.abort();
});

function onDocumentClick(event) {
    if (!root.value?.contains(event.target)) {
        open.value = false;
    }
}

async function fetchSuggestions(value) {
    const trimmed = String(value ?? '').trim();

    abortController?.abort();
    abortController = new AbortController();
    loading.value = true;

    try {
        const response = await fetch(
            route('search.suggest', { q: trimmed || undefined }),
            {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                signal: abortController.signal,
            },
        );

        if (!response.ok) {
            return;
        }

        const payload = await response.json();
        results.value = payload.results ?? {};
        recentSearches.value = payload.recent_searches ?? [];
    } catch (error) {
        if (error?.name !== 'AbortError') {
            results.value = {};
        }
    } finally {
        loading.value = false;
    }
}

function submitSearch() {
    const trimmed = query.value.trim();

    if (trimmed.length < 2) {
        return;
    }

    open.value = false;
    router.get(route('search.index'), { q: trimmed });
}

function useRecent(term) {
    query.value = term;
    open.value = false;
    router.get(route('search.index'), { q: term });
}

function goToResult(item) {
    if (!item?.url) {
        return;
    }

    open.value = false;
    router.visit(item.url);
}
</script>

<template>
    <div ref="root" class="relative min-w-0 w-full max-w-xl flex-1">
        <form @submit.prevent="submitSearch" class="relative">
            <label class="sr-only" for="global-search">Search</label>
            <span
                class="pointer-events-none absolute inset-y-0 start-3 flex items-center text-text-muted"
                aria-hidden="true"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.75"
                    class="h-4 w-4"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M21 21l-4.35-4.35m1.6-5.4a7 7 0 11-14 0 7 7 0 0114 0z"
                    />
                </svg>
            </span>
            <input
                id="global-search"
                v-model="query"
                type="search"
                autocomplete="off"
                placeholder="Search CRM…"
                class="w-full min-h-11 rounded-md border py-2 pe-3 ps-9 text-body shadow-sm focus:border-secondary focus:outline-none focus:ring-2 focus:ring-secondary"
                :class="
                    variant === 'header'
                        ? 'border-transparent bg-surface text-text placeholder:text-text-muted'
                        : 'border-border bg-surface text-text'
                "
                @focus="open = true"
            />
        </form>

        <div
            v-if="showPanel"
            class="absolute z-30 mt-1 max-h-96 w-full overflow-y-auto rounded-md border border-border bg-surface shadow-dropdown"
            role="listbox"
        >
            <div v-if="query.trim().length < 2" class="p-3">
                <p class="text-small text-text-muted">Recent searches</p>
                <ul class="mt-2 space-y-1">
                    <li v-for="term in recentSearches" :key="term">
                        <button
                            type="button"
                            class="flex min-h-11 w-full items-center rounded-md px-2 text-left text-body text-text hover:bg-bg"
                            @click="useRecent(term)"
                        >
                            {{ term }}
                        </button>
                    </li>
                </ul>
            </div>

            <div v-else class="p-3">
                <p v-if="loading" class="text-small text-text-muted">Searching…</p>
                <p
                    v-else-if="!hasTypedResults"
                    class="text-small text-text-muted"
                >
                    No matches
                </p>

                <template v-for="section in sectionOrder" :key="section">
                    <div
                        v-if="results[section]?.length"
                        class="mb-3 last:mb-0"
                    >
                        <p class="text-small font-semibold text-text-muted">
                            {{ sectionLabels[section] }}
                        </p>
                        <ul class="mt-1 space-y-1">
                            <li v-for="item in results[section]" :key="`${section}-${item.id}`">
                                <button
                                    v-if="item.url"
                                    type="button"
                                    class="flex min-h-11 w-full flex-col justify-center rounded-md px-2 text-left hover:bg-bg"
                                    @click="goToResult(item)"
                                >
                                    <span class="text-body text-text">{{ item.title }}</span>
                                    <span
                                        v-if="item.subtitle"
                                        class="text-small text-text-muted"
                                    >
                                        {{ item.subtitle }}
                                    </span>
                                </button>
                                <div
                                    v-else
                                    class="flex min-h-11 flex-col justify-center rounded-md px-2"
                                >
                                    <span class="text-body text-text">{{ item.title }}</span>
                                    <span
                                        v-if="item.subtitle"
                                        class="text-small text-text-muted"
                                    >
                                        {{ item.subtitle }}
                                    </span>
                                </div>
                            </li>
                        </ul>
                    </div>
                </template>

                <Link
                    v-if="query.trim().length >= 2"
                    :href="route('search.index', { q: query.trim() })"
                    class="mt-2 inline-flex min-h-11 items-center text-small text-secondary"
                    @click="open = false"
                >
                    View all results
                </Link>
            </div>
        </div>
    </div>
</template>
