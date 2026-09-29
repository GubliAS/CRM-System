<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';

defineProps({
    title: {
        type: String,
        default: '',
    },
});
</script>

<template>
    <div class="min-h-screen bg-bg font-sans text-text lg:grid lg:grid-cols-2">
        <aside
            class="crm-chrome flex flex-col justify-between bg-primary px-6 py-8 text-on-primary sm:px-10 lg:min-h-screen lg:px-12 lg:py-12"
        >
            <Link
                href="/"
                class="inline-flex min-h-11 items-center self-start text-on-primary"
            >
                <ApplicationLogo tone="on-primary" />
            </Link>

            <div class="mt-10 max-w-md lg:mt-0">
                <p class="text-h1 font-bold text-on-primary">CRM</p>
                <p class="mt-3 text-body text-on-primary">
                    Sales pipeline and customer service in one workspace. Track
                    leads, accounts, opportunities, and cases with shared tasks
                    and calendar.
                </p>
            </div>

            <p class="mt-10 text-small text-on-primary lg:mt-0">
                Sales &amp; Service
            </p>
        </aside>

        <div class="flex items-center justify-center px-4 py-8 sm:px-8">
            <div class="w-full max-w-lg">
                <h1 v-if="title" class="mb-4 text-h1 font-bold text-text">
                    {{ title }}
                </h1>
                <div class="rounded-md border border-border bg-surface p-5 shadow-panel sm:p-6">
                    <slot />
                </div>
            </div>
        </div>
    </div>
</template>
