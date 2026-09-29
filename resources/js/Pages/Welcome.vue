<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';

const page = usePage();
</script>

<template>
    <div class="min-h-screen bg-bg font-sans text-text lg:grid lg:grid-cols-2">
        <Head title="Welcome" />

        <aside
            class="flex flex-col justify-between bg-primary px-6 py-8 text-on-primary sm:px-10 lg:min-h-screen lg:px-12 lg:py-12"
        >
            <ApplicationLogo tone="on-primary" />

            <div class="mt-10 max-w-md lg:mt-0">
                <h1 class="text-h1 font-bold text-on-primary">CRM</h1>
                <p class="mt-3 text-body text-on-primary">
                    Sales pipeline and customer support in one place. Manage
                    leads, accounts, contacts, opportunities, cases, tasks, and
                    calendar.
                </p>
            </div>

            <p class="mt-10 text-small text-on-primary lg:mt-0">
                Sales &amp; Service
            </p>
        </aside>

        <div class="flex items-center justify-center px-4 py-8 sm:px-8">
            <div class="w-full max-w-md">
                <div class="crm-panel p-5 sm:p-6">
                    <h2 class="text-h2 font-semibold text-text">Get started</h2>
                    <p class="mt-2 text-body text-text-muted">
                        Sign in to open your workspace, or create an account if
                        you are new.
                    </p>

                    <div class="mt-6 flex flex-col gap-3 sm:flex-row">
                        <template v-if="page.props.auth.user">
                            <Link :href="route('home')" class="crm-btn-primary w-full sm:w-auto">
                                Go to Home
                            </Link>
                        </template>
                        <template v-else>
                            <Link :href="route('login')" class="crm-btn-primary w-full sm:w-auto">
                                Log in
                            </Link>
                            <Link :href="route('register')" class="crm-btn-secondary w-full sm:w-auto">
                                Register
                            </Link>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
