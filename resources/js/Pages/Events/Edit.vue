<script setup>
import EventForm from '@/Components/EventForm.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { eventFormData } from '@/forms/event';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    event: {
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

const form = useForm(eventFormData(props.event));

function submit(saveAndNew) {
    form.transform((data) => ({
        ...data,
        save_and_new: saveAndNew,
    })).put(route('events.update', props.event.id));
}
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Edit event" />

        <div class="mx-auto max-w-7xl px-4 py-6">
            <h1 class="break-words text-h1">Edit {{ event.subject }}</h1>
            <div class="mt-4 rounded-md border border-border bg-surface p-4">
                <EventForm
                    :form="form"
                    :assignees="assignees"
                    :contacts="contacts"
                    :related-types="relatedTypes"
                    :related-records="relatedRecords"
                    :show-time-as="showTimeAs"
                    :cancel-href="route('events.show', event.id)"
                    @submit="submit"
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
