<script setup>
import PaginationBar from '@/Components/PaginationBar.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { display, fullName } from '@/display';
import { Icon } from '@iconify/vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = defineProps({
    contacts: { type: Object, required: true },
    summary: { type: Object, required: true },
    filters: { type: Object, required: true },
    can: { type: Object, required: true },
});

const search = ref(props.filters.search ?? '');
const rowMenu = ref(null);

watch(
    () => props.filters.search,
    (value) => {
        search.value = value ?? '';
    },
);

const views = [
    { key: 'recent', label: 'Recently viewed' },
    { key: 'all', label: 'All contacts' },
];

const metrics = computed(() => [
    {
        key: 'total',
        label: 'Total contacts',
        value: props.summary.total,
        icon: 'lucide:contact',
        tone: 'violet',
    },
    {
        key: 'with_email',
        label: 'With email',
        value: props.summary.with_email,
        icon: 'lucide:mail',
        tone: 'blue',
    },
    {
        key: 'with_phone',
        label: 'With phone',
        value: props.summary.with_phone,
        icon: 'lucide:phone',
        tone: 'green',
    },
    {
        key: 'accounts',
        label: 'Accounts',
        value: props.summary.accounts,
        icon: 'lucide:building-2',
        tone: 'rose',
    },
]);

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
    router.get(route('contacts.index'), listQuery(), {
        preserveState: true,
        replace: true,
    });
}

function changeView(view) {
    router.get(route('contacts.index'), listQuery({ view }), {
        preserveState: true,
        replace: true,
    });
}

function changePerPage(event) {
    router.get(route('contacts.index'), listQuery({ per_page: event.target.value }), {
        preserveState: true,
        replace: true,
    });
}

function initials(name) {
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

function avatarTone(id) {
    const tones = ['a', 'b', 'c', 'd', 'e', 'f'];

    return tones[Number(id) % tones.length];
}

function location(contact) {
    return [contact.mailing_city, contact.mailing_country].filter(Boolean).join(', ') || '—';
}

function toggleRowMenu(event, contact) {
    if (rowMenu.value?.contact.id === contact.id) {
        rowMenu.value = null;
        return;
    }

    const rect = event.currentTarget.getBoundingClientRect();
    const width = 176;

    rowMenu.value = {
        contact,
        top: rect.bottom + 4,
        left: Math.max(8, Math.min(rect.right - width, window.innerWidth - width - 8)),
    };
}

function closeRowMenu() {
    rowMenu.value = null;
}

function onDocumentKey(event) {
    if (event.key === 'Escape') {
        closeRowMenu();
    }
}

onMounted(() => {
    document.addEventListener('keydown', onDocumentKey);
    window.addEventListener('scroll', closeRowMenu, true);
});

onBeforeUnmount(() => {
    document.removeEventListener('keydown', onDocumentKey);
    window.removeEventListener('scroll', closeRowMenu, true);
});
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Contacts" />

        <template #header>
            <div class="flex min-w-0 flex-wrap items-end justify-between gap-3">
                <div class="min-w-0">
                    <h1 class="crm-page-title">Contacts</h1>
                    <p class="crm-page-subtitle">
                        {{ contacts.from ?? 0 }}–{{ contacts.to ?? 0 }} of {{ contacts.total }} records
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Link
                        v-if="can.create"
                        :href="route('contacts.create')"
                        class="crm-btn-primary"
                    >
                        + New contact
                    </Link>
                </div>
            </div>
        </template>

        <div class="crm-page max-w-none px-4 py-4 sm:px-5">
            <div class="crm-account-metrics">
                <article
                    v-for="metric in metrics"
                    :key="metric.key"
                    class="crm-account-metric"
                >
                    <span
                        class="crm-account-metric-icon"
                        :class="`crm-account-metric-icon-${metric.tone}`"
                        aria-hidden="true"
                    >
                        <Icon :icon="metric.icon" />
                    </span>
                    <div class="min-w-0">
                        <p class="crm-account-metric-label">{{ metric.label }}</p>
                        <p class="crm-account-metric-value tabular-nums">
                            {{ String(metric.value).padStart(2, '0') }}
                        </p>
                    </div>
                </article>
            </div>

            <section class="crm-account-panel">
                <div class="crm-account-toolbar">
                    <div class="crm-account-toolbar-left">
                        <a
                            v-if="can.export"
                            :href="route('contacts.export', listQuery())"
                            class="crm-account-tool-btn"
                        >
                            <Icon icon="lucide:download" aria-hidden="true" />
                            Export
                        </a>
                        <div class="crm-ov-seg" role="tablist" aria-label="Contact list view">
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
                    </div>

                    <form class="crm-account-toolbar-right" @submit.prevent="applySearch">
                        <div class="crm-account-search">
                            <Icon
                                icon="lucide:search"
                                class="crm-account-search-icon"
                                aria-hidden="true"
                            />
                            <TextInput
                                id="contact-search"
                                v-model="search"
                                type="search"
                                class="crm-account-search-input"
                                placeholder="Search contact"
                            />
                        </div>
                        <select
                            class="crm-account-rows"
                            :value="filters.per_page"
                            aria-label="Rows per page"
                            @change="changePerPage"
                        >
                            <option :value="25">25</option>
                            <option :value="50">50</option>
                            <option :value="100">100</option>
                            <option :value="200">200</option>
                        </select>
                        <button type="submit" class="crm-account-tool-btn crm-account-tool-btn-filter">
                            <Icon icon="lucide:list-filter" aria-hidden="true" />
                            Search
                        </button>
                    </form>
                </div>

                <div
                    v-if="contacts.data.length === 0"
                    class="crm-account-empty"
                    role="status"
                >
                    <template v-if="filters.search">
                        No contacts match your search.
                    </template>
                    <template v-else-if="filters.view === 'recent'">
                        <p>No recently viewed contacts.</p>
                        <button
                            type="button"
                            class="crm-btn-secondary mt-3"
                            @click="changeView('all')"
                        >
                            View all contacts
                        </button>
                    </template>
                    <template v-else>
                        No contacts yet.
                    </template>
                </div>

                <div v-else class="crm-account-grid">
                    <article
                        v-for="contact in contacts.data"
                        :key="contact.id"
                        class="crm-account-card"
                    >
                        <header class="crm-account-card-head">
                            <Link
                                :href="route('contacts.show', contact.id)"
                                class="crm-account-card-identity"
                            >
                                <span
                                    class="crm-account-avatar"
                                    :class="`crm-account-avatar-${avatarTone(contact.id)}`"
                                    aria-hidden="true"
                                >
                                    {{ initials(fullName(contact)) }}
                                </span>
                                <span class="min-w-0">
                                    <span class="crm-account-card-name">{{ fullName(contact) }}</span>
                                    <span class="crm-account-card-meta">{{ location(contact) }}</span>
                                </span>
                            </Link>
                            <button
                                type="button"
                                class="crm-account-card-menu"
                                aria-label="Contact actions"
                                aria-haspopup="menu"
                                :aria-expanded="rowMenu?.contact.id === contact.id"
                                @click.stop="toggleRowMenu($event, contact)"
                            >
                                <Icon icon="lucide:ellipsis-vertical" />
                            </button>
                        </header>

                        <dl class="crm-account-card-fields">
                            <div>
                                <dt>Phone</dt>
                                <dd>{{ display(contact.phone) }}</dd>
                            </div>
                            <div>
                                <dt>Email</dt>
                                <dd class="truncate">{{ display(contact.email) }}</dd>
                            </div>
                            <div>
                                <dt>Account</dt>
                                <dd class="truncate">
                                    <Link
                                        v-if="contact.account"
                                        :href="route('accounts.show', contact.account.id)"
                                        class="crm-record-link"
                                    >
                                        {{ contact.account.name }}
                                    </Link>
                                    <span v-else>—</span>
                                </dd>
                            </div>
                            <div>
                                <dt>Title</dt>
                                <dd>
                                    <span class="crm-account-company-pill">
                                        {{ display(contact.title) }}
                                    </span>
                                </dd>
                            </div>
                        </dl>
                    </article>
                </div>

                <div class="crm-account-footer">
                    <PaginationBar :paginator="contacts" />
                </div>
            </section>
        </div>

        <template v-if="rowMenu">
            <div class="crm-row-menu-backdrop" @click="closeRowMenu" />
            <div
                class="crm-row-menu"
                role="menu"
                :style="{ top: `${rowMenu.top}px`, left: `${rowMenu.left}px` }"
            >
                <Link
                    :href="route('contacts.show', rowMenu.contact.id)"
                    class="crm-row-menu-item"
                    role="menuitem"
                    @click="closeRowMenu"
                >
                    <Icon icon="lucide:eye" aria-hidden="true" />
                    View
                </Link>
                <Link
                    :href="route('contacts.edit', rowMenu.contact.id)"
                    class="crm-row-menu-item"
                    role="menuitem"
                    @click="closeRowMenu"
                >
                    <Icon icon="lucide:pencil" aria-hidden="true" />
                    Edit
                </Link>
            </div>
        </template>
    </AuthenticatedLayout>
</template>
