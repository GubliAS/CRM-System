<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    stacked: {
        type: Boolean,
        default: false,
    },
});

const tabs = [
    { label: 'Home', routeName: 'home' },
    { label: 'Leads', routeName: 'leads.index', active: 'leads.*' },
    { label: 'Accounts', routeName: 'accounts.index', active: 'accounts.*' },
    { label: 'Contacts', routeName: 'contacts.index', active: 'contacts.*' },
    { label: 'Opportunities' },
    { label: 'Cases' },
    { label: 'Tasks' },
    { label: 'Calendar' },
    { label: 'Reports' },
    { label: 'Dashboards' },
    { label: 'About', routeName: 'about' },
];

function tabIsCurrent(tab) {
    if (tab.active) {
        return route().current(tab.active);
    }

    return tab.routeName ? route().current(tab.routeName) : false;
}
</script>

<template>
    <div :class="stacked ? 'flex flex-col' : 'flex flex-wrap'">
        <template v-for="tab in tabs" :key="tab.label">
            <Link
                v-if="tab.routeName"
                :href="route(tab.routeName)"
                class="inline-flex min-h-11 items-center px-3 text-body"
                :class="
                    tabIsCurrent(tab)
                        ? 'border-secondary text-primary ' +
                          (stacked ? 'border-l-4' : 'border-b-2')
                        : 'border-transparent text-text ' +
                          (stacked ? 'border-l-4' : 'border-b-2')
                "
            >
                {{ tab.label }}
            </Link>
            <span
                v-else
                class="inline-flex min-h-11 cursor-not-allowed items-center px-3 text-body text-text-muted"
                aria-disabled="true"
            >
                {{ tab.label }}
            </span>
        </template>
    </div>
</template>
