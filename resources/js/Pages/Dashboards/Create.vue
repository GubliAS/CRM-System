<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    builder: { type: Object, required: true },
});

const form = useForm({
    name: '',
    description: '',
    widgets: [],
});

const canAddWidget = computed(() => form.widgets.length < props.builder.maxWidgets);

function newWidgetId() {
    return `w-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`;
}

function addWidget() {
    if (!canAddWidget.value) {
        return;
    }

    const report = props.builder.reports[0];
    form.widgets.push({
        id: newWidgetId(),
        report_id: report?.id ?? null,
        type: 'table',
        title: '',
        row: form.widgets.length,
        col: 0,
        width: 6,
        height: 3,
    });
}

function removeWidget(index) {
    form.widgets.splice(index, 1);
}

function submit() {
    form.post(route('dashboards.store'));
}
</script>

<template>
    <AuthenticatedLayout>
        <Head title="New dashboard" />

        <div class="mx-auto max-w-5xl px-4 py-6">
            <p class="text-small text-text-muted">
                <Link :href="route('dashboards.index')" class="text-secondary underline">
                    Dashboards
                </Link>
            </p>
            <h1 class="mt-1 text-h1">New dashboard</h1>
            <p class="mt-1 text-body text-text-muted">
                Add up to {{ builder.maxWidgets }} widgets from saved reports.
            </p>

            <form class="mt-6 space-y-6" @submit.prevent="submit">
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <InputLabel for="name" value="Name" required />
                        <TextInput id="name" v-model="form.name" class="mt-1 block w-full" required />
                        <InputError class="mt-1" :message="form.errors.name" />
                    </div>
                    <div>
                        <InputLabel for="description" value="Description" />
                        <TextInput
                            id="description"
                            v-model="form.description"
                            class="mt-1 block w-full"
                        />
                        <InputError class="mt-1" :message="form.errors.description" />
                    </div>
                </div>

                <div class="space-y-3">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h2 class="text-h2">Widgets</h2>
                        <button
                            type="button"
                            class="inline-flex min-h-11 items-center rounded-md border border-border bg-surface px-3 text-small"
                            :disabled="!canAddWidget || builder.reports.length === 0"
                            @click="addWidget"
                        >
                            Add widget
                        </button>
                    </div>
                    <p v-if="builder.reports.length === 0" class="text-small text-warning">
                        Save a report first, then add it as a dashboard widget.
                    </p>
                    <InputError class="mt-1" :message="form.errors.widgets" />

                    <div
                        v-for="(widget, index) in form.widgets"
                        :key="widget.id"
                        class="grid gap-3 border border-border bg-surface p-4 md:grid-cols-2"
                    >
                        <div>
                            <InputLabel :for="`widget-title-${index}`" value="Title" />
                            <TextInput
                                :id="`widget-title-${index}`"
                                v-model="widget.title"
                                class="mt-1 block w-full"
                                placeholder="Optional override"
                            />
                        </div>
                        <div>
                            <InputLabel :for="`widget-type-${index}`" value="Type" required />
                            <select
                                :id="`widget-type-${index}`"
                                v-model="widget.type"
                                class="mt-1 block w-full min-h-11 rounded-md border-border bg-surface text-body text-text"
                            >
                                <option
                                    v-for="type in builder.widgetTypes"
                                    :key="type.key"
                                    :value="type.key"
                                >
                                    {{ type.label }}
                                </option>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <InputLabel :for="`widget-report-${index}`" value="Report" required />
                            <select
                                :id="`widget-report-${index}`"
                                v-model="widget.report_id"
                                class="mt-1 block w-full min-h-11 rounded-md border-border bg-surface text-body text-text"
                            >
                                <option
                                    v-for="report in builder.reports"
                                    :key="report.id"
                                    :value="report.id"
                                >
                                    {{ report.name }}
                                </option>
                            </select>
                            <InputError
                                class="mt-1"
                                :message="form.errors[`widgets.${index}.report_id`]"
                            />
                        </div>
                        <div class="md:col-span-2">
                            <button
                                type="button"
                                class="text-small text-danger underline"
                                @click="removeWidget(index)"
                            >
                                Remove widget
                            </button>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap gap-3">
                    <button
                        type="submit"
                        class="inline-flex min-h-11 items-center rounded-md bg-primary px-4 py-2 text-small font-semibold uppercase tracking-widest text-surface"
                        :disabled="form.processing"
                    >
                        Save dashboard
                    </button>
                    <Link
                        :href="route('dashboards.index')"
                        class="inline-flex min-h-11 items-center rounded-md border border-border bg-surface px-4 text-small"
                    >
                        Cancel
                    </Link>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
