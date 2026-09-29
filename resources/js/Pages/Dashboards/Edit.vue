<script setup>
import DashboardBuilder from '@/Components/DashboardBuilder.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { clampWidget, resolve } from '@/dashboard/layout';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    dashboard: { type: Object, required: true },
    builder: { type: Object, required: true },
});

const form = useForm({
    name: props.dashboard.name,
    description: props.dashboard.description ?? '',
    folder: props.dashboard.folder ?? 'private',
    // Older dashboards may overlap or sit outside the grid; tidy on load.
    widgets: resolve(
        (props.dashboard.widgets ?? []).map((widget) =>
            clampWidget({
                id: widget.id,
                report_id: widget.report_id,
                type: widget.type,
                title: widget.title ?? '',
                row: widget.row ?? 0,
                col: widget.col ?? 0,
                width: widget.width ?? 6,
                height: widget.height ?? 3,
            }),
        ),
    ),
});

function submit() {
    form.put(route('dashboards.update', props.dashboard.id));
}
</script>

<template>
    <AuthenticatedLayout>
        <Head :title="`Edit ${dashboard.name}`" />

        <template #header>
            <div class="min-w-0">
                <h1 class="crm-page-title truncate">Edit dashboard</h1>
                <p class="crm-page-subtitle truncate">{{ dashboard.name }}</p>
            </div>
        </template>

        <div class="crm-page max-w-none px-4 py-4 sm:px-5">
            <DashboardBuilder
                :form="form"
                :builder="builder"
                submit-label="Save changes"
                :cancel-href="route('dashboards.show', dashboard.id)"
                @submit="submit"
            />
        </div>
    </AuthenticatedLayout>
</template>
