<script setup>
import TaskForm from '@/Components/TaskForm.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { taskFormData } from '@/forms/task';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    currentUserId: {
        type: Number,
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

const form = useForm(taskFormData(null, props.currentUserId));

function submit(saveAndNew) {
    form.transform((data) => ({
        ...data,
        save_and_new: saveAndNew,
    })).post(route('tasks.store'));
}
</script>

<template>
    <AuthenticatedLayout>
        <Head title="New task" />

        <div class="crm-page">
            <h1 class="text-h1">New task</h1>
            <div class="mt-4 crm-panel">
                <TaskForm
                    :form="form"
                    :assignees="assignees"
                    :contacts="contacts"
                    :related-types="relatedTypes"
                    :related-records="relatedRecords"
                    :statuses="statuses"
                    :priorities="priorities"
                    :cancel-href="route('tasks.index')"
                    @submit="submit"
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
