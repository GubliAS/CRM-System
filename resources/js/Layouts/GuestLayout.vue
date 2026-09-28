<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div
        class="flex min-h-screen flex-col items-center bg-bg px-4 py-6 font-sans text-text sm:justify-center"
    >
        <Link href="/" class="inline-flex min-h-11 items-center">
            <ApplicationLogo />
        </Link>

        <div
            class="mt-6 w-full max-w-2xl overflow-hidden rounded-md border border-border bg-surface px-4 py-4 sm:px-6"
        >
            <slot />
        </div>
    </div>
</template>
