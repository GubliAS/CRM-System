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
    { label: 'Leads' },
    { label: 'Accounts' },
    { label: 'Contacts' },
    { label: 'Opportunities' },
    { label: 'Cases' },
    { label: 'Tasks' },
    { label: 'Calendar' },
    { label: 'Reports' },
    { label: 'Dashboards' },
    { label: 'About', routeName: 'about' },
];
</script>

<template>
    <div :class="stacked ? 'flex flex-col' : 'flex flex-wrap'">
        <template v-for="tab in tabs" :key="tab.label">
            <Link
                v-if="tab.routeName"
                :href="route(tab.routeName)"
                class="inline-flex min-h-11 items-center px-3 text-body"
                :class="
                    route().current(tab.routeName)
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
