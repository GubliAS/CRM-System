<script setup>
import PaginationBar from '@/Components/PaginationBar.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { display, fullName } from '@/display';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    contacts: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        required: true,
    },
    can: {
        type: Object,
        required: true,
    },
});

const search = ref(props.filters.search ?? '');

watch(
    () => props.filters.search,
    (value) => {
        search.value = value ?? '';
    },
);

const views = [
    { key: 'recent', label: 'Recently Viewed' },
    { key: 'all', label: 'All Contacts' },
];

const columns = [
    { key: 'name', label: 'Name' },
    { key: 'account', label: 'Account' },
    { key: 'title', label: 'Title' },
    { key: 'phone', label: 'Phone' },
    { key: 'email', label: 'Email' },
    { key: 'owner', label: 'Owner' },
];

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

function sortHref(column) {
    const direction =
        props.filters.sort === column && props.filters.direction === 'asc' ? 'desc' : 'asc';

    return route('contacts.index', listQuery({ sort: column, direction }));
}

function changePerPage(event) {
    router.get(route('contacts.index'), listQuery({ per_page: event.target.value }), {
        preserveState: true,
        replace: true,
    });
}
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Contacts" />

        <div class="crm-page">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h1 class="text-h1">Contacts</h1>
                    <p class="mt-1 text-small text-text-muted">
                        Showing {{ contacts.from ?? 0 }}–{{ contacts.to ?? 0 }} of {{ contacts.total }}
                    </p>
                </div>
                <Link
                    v-if="can.create"
                    :href="route('contacts.create')"
                    class="crm-btn-primary"
                >
                    New contact
                </Link>
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                <button
                    v-for="view in views"
                    :key="view.key"
                    type="button"
                    class="crm-view-tab"
                    :class="
                        filters.view === view.key
                            ? 'crm-view-tab-active'
                            : 'crm-view-tab-idle'
                    "
                    @click="changeView(view.key)"
                >
                    {{ view.label }}
                </button>
            </div>

            <form class="mt-4 flex flex-wrap items-end gap-3" @submit.prevent="applySearch">
                <div class="min-w-0 flex-1 sm:max-w-xs">
                    <label class="text-small text-text-muted" for="contact-search">Search</label>
                    <TextInput
                        id="contact-search"
                        v-model="search"
                        type="search"
                        class="mt-1 block w-full"
                    />
                </div>
                <div>
                    <label class="text-small text-text-muted" for="contact-per-page">Rows</label>
                    <select
                        id="contact-per-page"
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
                    class="crm-btn-primary"
                >
                    Search
                </button>
            </form>

            <div class="mt-4 crm-table-wrap">
                <table class="crm-table">
                    <thead>
                        <tr>
                            <th v-for="column in columns" :key="column.key" scope="col" class="px-3 py-2">
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
                        <tr v-if="contacts.data.length === 0">
                            <td colspan="6" class="px-4 py-8 text-center text-body text-text-muted">
                                {{
                                    filters.search
                                        ? 'No contacts match your search.'
                                        : filters.view === 'recent'
                                          ? 'No recently viewed contacts.'
                                          : 'No contacts yet.'
                                }}
                            </td>
                        </tr>
                        <tr
                            v-for="contact in contacts.data"
                            :key="contact.id"
                            class="border-t border-border"
                        >
                            <td class="px-3 py-2">
                                <Link
                                    :href="route('contacts.show', contact.id)"
                                    class="text-secondary underline"
                                >
                                    {{ fullName(contact) }}
                                </Link>
                            </td>
                            <td class="px-3 py-2">
                                <Link
                                    v-if="contact.account"
                                    :href="route('accounts.show', contact.account.id)"
                                    class="text-secondary underline"
                                >
                                    {{ contact.account.name }}
                                </Link>
                                <span v-else>—</span>
                            </td>
                            <td class="px-3 py-2">{{ display(contact.title) }}</td>
                            <td class="px-3 py-2">{{ display(contact.phone) }}</td>
                            <td class="px-3 py-2">{{ display(contact.email) }}</td>
                            <td class="px-3 py-2">{{ display(contact.owner?.name) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                <PaginationBar :paginator="contacts" />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
