<script setup>
import TaskForm from '@/Components/TaskForm.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { taskFormData } from '@/forms/task';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    task: {
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
    statuses: {
        type: Array,
        required: true,
    },
    priorities: {
        type: Array,
        required: true,
    },
});

const form = useForm(taskFormData(props.task));

function submit(saveAndNew) {
    form.transform((data) => ({
        ...data,
        save_and_new: saveAndNew,
    })).put(route('tasks.update', props.task.id));
}
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Edit task" />

        <div class="mx-auto max-w-7xl px-4 py-6">
            <h1 class="break-words text-h1">Edit {{ task.subject }}</h1>
            <div class="mt-4 rounded-md border border-border bg-surface p-4">
                <TaskForm
                    :form="form"
                    :assignees="assignees"
                    :contacts="contacts"
                    :related-types="relatedTypes"
                    :related-records="relatedRecords"
                    :statuses="statuses"
                    :priorities="priorities"
                    :cancel-href="route('tasks.show', task.id)"
                    @submit="submit"
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
