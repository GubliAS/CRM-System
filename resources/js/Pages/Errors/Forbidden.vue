<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps({
    message: {
        type: String,
        default: 'You do not have access to this page.',
    },
    missingRole: {
        type: Boolean,
        default: false,
    },
});

const page = usePage();
const isAuthenticated = computed(() => !!page.props.auth?.user);
</script>

<template>
    <Head title="Access denied" />

    <AuthenticatedLayout v-if="isAuthenticated">
        <div class="mx-auto max-w-xl px-4 py-10">
            <div class="crm-panel p-6">
                <h1 class="text-h1">Access denied</h1>
                <p class="mt-3 text-body text-text">{{ message }}</p>
                <p
                    v-if="missingRole"
                    class="mt-2 text-body text-text-muted"
                >
                    Your account has no role. Ask an administrator.
                </p>
                <Link
                    :href="route('home')"
                    class="mt-6 inline-flex min-h-11 items-center text-body text-secondary hover:text-secondary-hover"
                >
                    Back to Home
                </Link>
            </div>
        </div>
    </AuthenticatedLayout>

    <GuestLayout v-else title="Access denied">
        <p class="text-body text-text">{{ message }}</p>
        <p
            v-if="missingRole"
            class="mt-2 text-body text-text-muted"
        >
            Your account has no role. Ask an administrator.
        </p>
        <Link
            :href="route('login')"
            class="mt-6 inline-flex min-h-11 items-center text-body text-secondary hover:text-secondary-hover"
        >
            Log in
        </Link>
    </GuestLayout>
</template>
