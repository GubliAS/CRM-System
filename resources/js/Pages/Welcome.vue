<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';

const page = usePage();
</script>

<template>
    <div class="min-h-screen bg-bg font-sans text-text">
        <Head title="Welcome" />

        <header class="border-b border-border bg-surface">
            <div
                class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-3 px-4 py-3"
            >
                <Link href="/" class="inline-flex min-h-11 items-center">
                    <ApplicationLogo />
                </Link>

                <nav class="flex flex-wrap items-center gap-2 text-body">
                    <Link
                        v-if="page.props.auth.user"
                        :href="route('home')"
                        class="inline-flex min-h-11 items-center px-3 text-secondary"
                    >
                        Home
                    </Link>
                    <template v-else>
                        <Link
                            :href="route('login')"
                            class="inline-flex min-h-11 items-center px-3 text-secondary"
                        >
                            Log in
                        </Link>
                        <Link
                            :href="route('register')"
                            class="inline-flex min-h-11 items-center px-3 text-secondary"
                        >
                            Register
                        </Link>
                    </template>
                    <Link
                        :href="route('about')"
                        class="inline-flex min-h-11 items-center px-3 text-secondary"
                    >
                        About
                    </Link>
                </nav>
            </div>
        </header>

        <main class="mx-auto max-w-3xl px-4 py-8">
            <h1 class="text-h1">CRM</h1>
            <p class="mt-4 text-body text-text-muted">
                Web CRM for the sales pipeline and customer support. Sign in to
                open the Stage 0 shell. Leads, accounts, contacts, opportunities,
                and cases arrive in later stages.
            </p>
        </main>
    </div>
</template>
