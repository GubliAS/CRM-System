<script setup>
import { Icon } from '@iconify/vue';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const emit = defineEmits(['navigate']);

const page = usePage();
const abilities = computed(() => page.props.auth?.abilities ?? {});
const roleSlug = computed(() => page.props.auth?.role_slug ?? null);

const allTabs = [
    { label: 'Home', routeName: 'home', icon: 'lucide:house' },
    {
        label: 'Leads',
        routeName: 'leads.index',
        active: 'leads.*',
        ability: 'leads',
        icon: 'lucide:user-plus',
    },
    {
        label: 'Accounts',
        routeName: 'accounts.index',
        active: 'accounts.*',
        ability: 'accounts',
        icon: 'lucide:building-2',
    },
    {
        label: 'Contacts',
        routeName: 'contacts.index',
        active: 'contacts.*',
        ability: 'contacts',
        icon: 'lucide:users',
    },
    {
        label: 'Opportunities',
        routeName: 'opportunities.index',
        active: 'opportunities.*',
        ability: 'opportunities',
        icon: 'lucide:chart-column',
    },
    {
        label: 'Cases',
        routeName: 'cases.index',
        active: 'cases.*',
        ability: 'cases',
        icon: 'lucide:shield-alert',
    },
    {
        label: 'Tasks',
        routeName: 'tasks.index',
        active: 'tasks.*',
        ability: 'tasks',
        icon: 'lucide:list-checks',
    },
    {
        label: 'Calendar',
        routeName: 'events.index',
        active: 'events.*',
        ability: 'events',
        icon: 'lucide:calendar',
    },
    {
        label: 'Reports',
        routeName: 'reports.index',
        active: 'reports.*',
        icon: 'lucide:file-text',
    },
    {
        label: 'Dashboards',
        routeName: 'dashboards.index',
        active: 'dashboards.*',
        icon: 'lucide:layout-panel-left',
    },
];

const tabs = computed(() =>
    allTabs.filter((tab) => {
        if (tab.routeName === 'home') {
            return true;
        }

        if (!roleSlug.value) {
            return false;
        }

        return tab.ability ? abilities.value[tab.ability] === true : true;
    }),
);

function tabIsCurrent(tab) {
    if (tab.active) {
        return route().current(tab.active);
    }

    return tab.routeName ? route().current(tab.routeName) : false;
}
</script>

<template>
    <div class="crm-rail-nav" role="navigation" aria-label="Modules">
        <Link
            v-for="tab in tabs"
            :key="tab.label"
            :href="route(tab.routeName)"
            class="crm-rail-link"
            :class="tabIsCurrent(tab) ? 'crm-rail-link-active' : ''"
            :title="tab.label"
            :aria-current="tabIsCurrent(tab) ? 'page' : undefined"
            @click="emit('navigate')"
        >
            <span class="crm-rail-icon" aria-hidden="true">
                <Icon :icon="tab.icon" />
            </span>
            <span class="crm-rail-label">{{ tab.label }}</span>
        </Link>
    </div>
</template>
