<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';

const page = usePage();
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Home" />

        <div class="mx-auto max-w-3xl px-4 py-8">
            <h1 class="text-h1">Welcome, {{ page.props.auth.user.name }}</h1>
            <p class="mt-4 text-body">
                Stage 0 is the application shell. CRM modules arrive in later
                stages.
            </p>
        </div>
    </AuthenticatedLayout>
</template>
