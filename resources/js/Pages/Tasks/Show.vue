<script setup>
import DangerButton from '@/Components/DangerButton.vue';
import DetailField from '@/Components/DetailField.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { display, formatDay, personName } from '@/display';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    task: {
        type: Object,
        required: true,
    },
    can: {
        type: Object,
        required: true,
    },
});

const confirming = ref(false);
const form = useForm({});

const reminderLabel = computed(() => {
    if (!props.task.reminder_set) {
        return '—';
    }

    const day = formatDay(props.task.reminder_date);
    const time = props.task.reminder_time || '';

    return time ? `${day} ${time}` : day;
});

function destroy() {
    form.delete(route('tasks.destroy', props.task.id));
}
</script>

<template>
    <AuthenticatedLayout>
        <Head :title="task.subject" />

        <div class="mx-auto max-w-7xl px-4 py-6">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <h1 class="break-words text-h1">{{ task.subject }}</h1>
                <div class="flex flex-wrap gap-2">
                    <Link
                        v-if="can.update"
                        :href="route('tasks.edit', task.id)"
                        class="inline-flex min-h-11 items-center rounded-md border border-border bg-surface px-4 py-2 text-small font-semibold uppercase tracking-widest text-text"
                    >
                        Edit
                    </Link>
                    <DangerButton v-if="can.delete" type="button" class="min-h-11" @click="confirming = true">
                        Delete
                    </DangerButton>
                </div>
            </div>

            <div class="mt-6 space-y-4">
                <section class="rounded-md border border-border bg-surface p-4">
                    <h2 class="text-h2">Task details</h2>
                    <dl class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <DetailField label="Subject" :value="task.subject" />
                        <DetailField label="Assigned to" :value="personName(task.assigned_to)" />
                        <DetailField label="Related to">
                            <Link
                                v-if="task.related_href"
                                :href="task.related_href"
                                class="text-secondary underline"
                            >
                                {{ task.related_label }}
                            </Link>
                            <span v-else>{{ display(task.related_label) }}</span>
                        </DetailField>
                        <DetailField label="Contact" :value="task.contact_label" />
                        <DetailField label="Due date" :value="formatDay(task.due_on)" />
                        <DetailField label="Status" :value="task.status" />
                        <DetailField label="Priority" :value="task.priority" />
                        <DetailField label="Reminder" :value="reminderLabel" />
                        <DetailField label="Comments" class="md:col-span-2">
                            <span class="whitespace-pre-wrap">{{ display(task.comments) }}</span>
                        </DetailField>
                        <DetailField label="Owner" :value="personName(task.owner)" />
                    </dl>
                </section>
            </div>
        </div>

        <Modal :show="confirming" max-width="md" @close="confirming = false">
            <div class="p-6">
                <h2 class="text-h2">Delete this task?</h2>
                <p class="mt-2 text-body text-text-muted">This removes the task. This cannot be undone.</p>
                <div class="mt-6 flex flex-wrap gap-2">
                    <DangerButton type="button" class="min-h-11" :disabled="form.processing" @click="destroy">
                        Delete
                    </DangerButton>
                    <SecondaryButton type="button" class="min-h-11" @click="confirming = false">
                        Cancel
                    </SecondaryButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
