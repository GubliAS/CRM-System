<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps({
    content: {
        type: String,
        required: true,
    },
});

const page = usePage();
const signedIn = computed(() => Boolean(page.props.auth.user));
</script>

<template>
    <Head title="About" />

    <AuthenticatedLayout v-if="signedIn">
        <div class="mx-auto w-full max-w-3xl px-4 py-6">
            <article
                class="markdown-body overflow-x-auto rounded-md bg-surface p-4 text-body text-text sm:p-6"
                v-html="content"
            />
        </div>
    </AuthenticatedLayout>

    <div v-else class="min-h-screen bg-bg font-sans text-text">
        <header class="border-b border-border bg-surface">
            <div
                class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-3 px-4 py-3"
            >
                <Link href="/" class="inline-flex min-h-11 items-center">
                    <ApplicationLogo />
                </Link>
                <nav class="flex flex-wrap items-center gap-2 text-body">
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
                    <Link
                        :href="route('about')"
                        class="inline-flex min-h-11 items-center px-3 text-primary"
                    >
                        About
                    </Link>
                </nav>
            </div>
        </header>

        <main class="mx-auto w-full max-w-3xl px-4 py-6">
            <article
                class="markdown-body overflow-x-auto rounded-md bg-surface p-4 text-body text-text sm:p-6"
                v-html="content"
            />
        </main>
    </div>
</template>
