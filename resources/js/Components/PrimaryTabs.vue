<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    stacked: {
        type: Boolean,
        default: false,
    },
    tone: {
        type: String,
        default: 'default',
    },
});

const emit = defineEmits(['navigate']);

const page = usePage();
const abilities = computed(() => page.props.auth?.abilities ?? {});
const roleSlug = computed(() => page.props.auth?.role_slug ?? null);

const allTabs = [
    { label: 'Home', routeName: 'home' },
    { label: 'Leads', routeName: 'leads.index', active: 'leads.*', ability: 'leads' },
    {
        label: 'Accounts',
        routeName: 'accounts.index',
        active: 'accounts.*',
        ability: 'accounts',
    },
    {
        label: 'Contacts',
        routeName: 'contacts.index',
        active: 'contacts.*',
        ability: 'contacts',
    },
    {
        label: 'Opportunities',
        routeName: 'opportunities.index',
        active: 'opportunities.*',
        ability: 'opportunities',
    },
    { label: 'Cases', routeName: 'cases.index', active: 'cases.*', ability: 'cases' },
    { label: 'Tasks', routeName: 'tasks.index', active: 'tasks.*', ability: 'tasks' },
    {
        label: 'Calendar',
        routeName: 'events.index',
        active: 'events.*',
        ability: 'events',
    },
    { label: 'Reports', placeholder: true },
    { label: 'Dashboards', placeholder: true },
];

const tabs = computed(() =>
    allTabs.filter((tab) => {
        if (tab.routeName === 'home') {
            return true;
        }

        if (!roleSlug.value) {
            return false;
        }

        if (tab.placeholder) {
            return true;
        }

        return tab.ability ? abilities.value[tab.ability] === true : false;
    }),
);

function tabIsCurrent(tab) {
    if (tab.active) {
        return route().current(tab.active);
    }

    return tab.routeName ? route().current(tab.routeName) : false;
}

function linkClass(tab) {
    const current = tabIsCurrent(tab);
    const onPrimary = props.tone === 'on-primary';

    if (props.stacked) {
        if (onPrimary) {
            return current
                ? 'border-l-4 border-on-primary bg-primary-hover text-on-primary'
                : 'crm-chrome-muted border-l-4 border-transparent hover:bg-primary-hover';
        }

        return current
            ? 'border-l-4 border-secondary text-primary'
            : 'border-l-4 border-transparent text-text hover:bg-bg';
    }

    if (onPrimary) {
        return current
            ? 'border-b-2 border-on-primary font-semibold text-on-primary'
            : 'crm-chrome-muted border-b-2 border-transparent';
    }

    return current
        ? 'border-b-2 border-secondary text-primary'
        : 'border-b-2 border-transparent text-text hover:text-primary';
}
</script>

<template>
    <div :class="stacked ? 'flex flex-col' : 'flex flex-nowrap'">
        <template v-for="tab in tabs" :key="tab.label">
            <Link
                v-if="tab.routeName"
                :href="route(tab.routeName)"
                class="inline-flex min-h-11 shrink-0 items-center whitespace-nowrap px-3 text-body transition-colors duration-fast"
                :class="linkClass(tab)"
                @click="emit('navigate')"
            >
                {{ tab.label }}
            </Link>
            <span
                v-else
                class="inline-flex min-h-11 shrink-0 cursor-not-allowed items-center whitespace-nowrap px-3 text-body"
                :class="
                    tone === 'on-primary'
                        ? 'crm-chrome-muted border-b-2 border-transparent'
                        : 'border-b-2 border-transparent text-text-muted'
                "
                aria-disabled="true"
                title="Coming in a later stage"
            >
                {{ tab.label }}
            </span>
        </template>
    </div>
</template>
