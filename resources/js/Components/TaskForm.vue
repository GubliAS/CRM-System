<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import FormField from '@/Components/FormField.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import RelatedFields from '@/Components/RelatedFields.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import SelectInput from '@/Components/SelectInput.vue';
import TextArea from '@/Components/TextArea.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    form: {
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
    cancelHref: {
        type: String,
        required: true,
    },
});

const emit = defineEmits(['submit']);

const assigneeOptions = computed(() =>
    props.assignees.map((person) => ({
        value: String(person.id),
        label: person.name,
    })),
);

const contactOptions = computed(() =>
    props.contacts.map((contact) => ({
        value: String(contact.id),
        label: contact.label,
    })),
);

const statusOptions = computed(() =>
    props.statuses.map((status) => ({
        value: status,
        label: status,
    })),
);

const priorityOptions = computed(() =>
    props.priorities.map((priority) => ({
        value: priority,
        label: priority,
    })),
);
</script>

<template>
    <form class="grid grid-cols-1 gap-4 md:grid-cols-2" @submit.prevent="emit('submit', false)">
        <h2 class="text-h2 md:col-span-2">Task details</h2>

        <FormField label="Subject" required span :error="form.errors.subject">
            <TextInput id="subject" v-model="form.subject" class="block w-full" required maxlength="255" />
        </FormField>

        <FormField label="Assigned to" required :error="form.errors.assigned_to_id">
            <SelectInput
                id="assigned_to_id"
                v-model="form.assigned_to_id"
                :options="assigneeOptions"
                placeholder="Select a person"
                required
            />
        </FormField>

        <FormField label="Due date" :error="form.errors.due_on">
            <TextInput id="due_on" v-model="form.due_on" type="date" class="block w-full" />
        </FormField>

        <RelatedFields :form="form" :types="relatedTypes" :records="relatedRecords" />

        <FormField label="Contact" :error="form.errors.contact_id">
            <SelectInput
                id="contact_id"
                v-model="form.contact_id"
                :options="contactOptions"
                placeholder="Select a contact"
            />
        </FormField>

        <FormField label="Status" :error="form.errors.status">
            <SelectInput id="status" v-model="form.status" :options="statusOptions" placeholder="Select a status" />
        </FormField>

        <FormField label="Priority" :error="form.errors.priority">
            <SelectInput
                id="priority"
                v-model="form.priority"
                :options="priorityOptions"
                placeholder="Select a priority"
            />
        </FormField>

        <FormField label="Comments" span :error="form.errors.comments">
            <TextArea id="comments" v-model="form.comments" rows="4" />
        </FormField>

        <div class="md:col-span-2">
            <label class="flex min-h-11 items-center gap-2 text-body text-text">
                <Checkbox v-model:checked="form.reminder_set" />
                Reminder set
            </label>
        </div>

        <FormField v-if="form.reminder_set" label="Reminder date" :error="form.errors.reminder_date">
            <TextInput id="reminder_date" v-model="form.reminder_date" type="date" class="block w-full" />
        </FormField>

        <FormField v-if="form.reminder_set" label="Reminder time" :error="form.errors.reminder_time">
            <TextInput id="reminder_time" v-model="form.reminder_time" type="time" class="block w-full" />
        </FormField>

        <div class="flex flex-wrap items-center gap-2 md:col-span-2">
            <PrimaryButton class="min-h-11" :disabled="form.processing">Save</PrimaryButton>
            <SecondaryButton type="button" class="min-h-11" :disabled="form.processing" @click="emit('submit', true)">
                Save & New
            </SecondaryButton>
            <Link :href="cancelHref" class="inline-flex min-h-11 items-center px-3 text-body text-secondary">
                Cancel
            </Link>
        </div>
    </form>
</template>
