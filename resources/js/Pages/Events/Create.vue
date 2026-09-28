<script setup>
import EventForm from '@/Components/EventForm.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { eventFormData } from '@/forms/event';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    currentUserId: {
        type: Number,
        required: true,
    },
    defaults: {
        type: Object,
        required: true,
    },
    assignees: {
        type: Array,
        required: true,
    },
    contacts: {
        type: Array,
        required: true,
    },
    relatedTypes: {
        type: Array,
        required: true,
    },
    relatedRecords: {
        type: Object,
        required: true,
    },
    showTimeAs: {
        type: Array,
        required: true,
    },
});

const form = useForm(eventFormData(null, props.defaults, props.currentUserId));

function submit(saveAndNew) {
    form.transform((data) => ({
        ...data,
        save_and_new: saveAndNew,
    })).post(route('events.store'));
}
</script>

<template>
    <AuthenticatedLayout>
        <Head title="New event" />

        <div class="mx-auto max-w-7xl px-4 py-6">
            <h1 class="text-h1">New event</h1>
            <div class="mt-4 rounded-md border border-border bg-surface p-4">
                <EventForm
                    :form="form"
                    :assignees="assignees"
                    :contacts="contacts"
                    :related-types="relatedTypes"
                    :related-records="relatedRecords"
                    :show-time-as="showTimeAs"
                    :cancel-href="route('events.index')"
                    @submit="submit"
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
