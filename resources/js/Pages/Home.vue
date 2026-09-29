<script setup>
import PageHeader from '@/Components/PageHeader.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';

const page = usePage();

const links = [
    { label: 'Leads', href: 'leads.index', description: 'Qualify and convert inbound interest.' },
    { label: 'Accounts', href: 'accounts.index', description: 'Companies and billing relationships.' },
    { label: 'Contacts', href: 'contacts.index', description: 'People linked to your accounts.' },
    { label: 'Opportunities', href: 'opportunities.index', description: 'Pipeline deals and stages.' },
    { label: 'Cases', href: 'cases.index', description: 'Service issues and resolutions.' },
    { label: 'Tasks', href: 'tasks.index', description: 'Follow-ups and to-dos.' },
    { label: 'Calendar', href: 'events.index', description: 'Meetings and scheduled events.' },
];
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Home" />

        <div class="crm-page">
            <PageHeader
                :title="`Welcome, ${page.props.auth.user.name}`"
                subtitle="Open a module to continue. Reports and dashboards arrive in later stages."
            />

            <div class="mt-6 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <Link
                    v-for="item in links"
                    :key="item.label"
                    :href="route(item.href)"
                    class="crm-panel block transition duration-fast hover:border-secondary focus:outline-none focus:ring-2 focus:ring-secondary"
                >
                    <p class="text-h3 font-semibold text-primary">{{ item.label }}</p>
                    <p class="mt-1 text-body text-text-muted">{{ item.description }}</p>
                </Link>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
