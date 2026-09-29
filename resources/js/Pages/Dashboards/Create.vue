<script setup>
import DashboardBuilder from '@/Components/DashboardBuilder.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    builder: { type: Object, required: true },
});

const form = useForm({
    name: '',
    description: '',
    folder: 'private',
    widgets: [],
});

function submit() {
    form.post(route('dashboards.store'));
}
</script>

<template>
    <AuthenticatedLayout>
        <Head title="New dashboard" />

        <template #header>
            <div class="min-w-0">
                <h1 class="crm-page-title">New dashboard</h1>
                <p class="crm-page-subtitle">
                    Arrange up to {{ props.builder.maxWidgets }} widgets from your saved reports.
                </p>
            </div>
        </template>

        <div class="crm-page max-w-none px-4 py-4 sm:px-5">
            <DashboardBuilder
                :form="form"
                :builder="builder"
                submit-label="Save dashboard"
                :cancel-href="route('dashboards.index')"
                @submit="submit"
            />
        </div>
    </AuthenticatedLayout>
</template>
