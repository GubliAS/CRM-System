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
    event: {
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

const schedule = computed(() => {
    if (props.event.all_day) {
        const start = formatDay(props.event.starts_input);
        const end = formatDay(props.event.ends_input);

        return start === end ? `${start} · All day` : `${start} – ${end} · All day`;
    }

    const start = String(props.event.starts_input ?? '').replace('T', ' ');
    const end = String(props.event.ends_input ?? '').replace('T', ' ');

    return `${start} – ${end}`;
});

function destroy() {
    form.delete(route('events.destroy', props.event.id));
}
</script>

<template>
    <AuthenticatedLayout>
        <Head :title="event.subject" />

        <div class="crm-page">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h1 class="break-words text-h1">{{ event.subject }}</h1>
                    <p class="mt-1 text-body text-text-muted">{{ schedule }}</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Link
                        :href="route('events.index')"
                        class="inline-flex min-h-11 items-center px-3 text-body text-secondary"
                    >
                        Calendar
                    </Link>
                    <Link
                        v-if="can.update"
                        :href="route('events.edit', event.id)"
                        class="crm-btn-secondary"
                    >
                        Edit
                    </Link>
                    <DangerButton v-if="can.delete" type="button" class="min-h-11" @click="confirming = true">
                        Delete
                    </DangerButton>
                </div>
            </div>

            <div class="mt-6">
                <section class="crm-panel">
                    <h2 class="text-h2">Event details</h2>
                    <dl class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <DetailField label="Subject" :value="event.subject" />
                        <DetailField label="When" :value="schedule" />
                        <DetailField label="Assigned to" :value="personName(event.assigned_to)" />
                        <DetailField label="Related to">
                            <Link
                                v-if="event.related_href"
                                :href="event.related_href"
                                class="text-secondary underline"
                            >
                                {{ event.related_label }}
                            </Link>
                            <span v-else>{{ display(event.related_label) }}</span>
                        </DetailField>
                        <DetailField label="Contact" :value="event.contact_label" />
                        <DetailField label="Location" :value="event.location" />
                        <DetailField label="Show time as" :value="event.show_time_as" />
                        <DetailField label="Private" :value="event.is_private ? 'Yes' : 'No'" />
                        <DetailField label="Description">
                            <span class="whitespace-pre-wrap">{{ display(event.description) }}</span>
                        </DetailField>
                        <DetailField label="Owner" :value="personName(event.owner)" />
                    </dl>
                </section>
            </div>
        </div>

        <Modal :show="confirming" max-width="md" @close="confirming = false">
            <div class="p-6">
                <h2 class="text-h2">Delete this event?</h2>
                <p class="mt-2 text-body text-text-muted">This removes the event. This cannot be undone.</p>
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
