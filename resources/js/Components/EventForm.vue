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
    showTimeAs: {
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

const showTimeOptions = computed(() =>
    props.showTimeAs.map((value) => ({
        value,
        label: value,
    })),
);

const whenType = computed(() => (props.form.all_day ? 'date' : 'datetime-local'));

function toggleAllDay(checked) {
    const startDate = String(props.form.starts_at).slice(0, 10);
    const endDate = String(props.form.ends_at).slice(0, 10);
    const startTime = String(props.form.starts_at).length > 10 ? String(props.form.starts_at).slice(11, 16) : '09:00';
    const endTime = String(props.form.ends_at).length > 10 ? String(props.form.ends_at).slice(11, 16) : '10:00';

    props.form.all_day = checked;

    if (checked) {
        props.form.starts_at = startDate;
        props.form.ends_at = endDate;

        return;
    }

    props.form.starts_at = startDate ? `${startDate}T${startTime}` : '';
    props.form.ends_at = endDate ? `${endDate}T${endTime}` : '';
}
</script>

<template>
    <form class="grid grid-cols-1 gap-4 md:grid-cols-2" @submit.prevent="emit('submit', false)">
        <h2 class="text-h2 md:col-span-2">Event details</h2>

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

        <div class="flex items-end">
            <label class="flex min-h-11 items-center gap-2 text-body text-text">
                <Checkbox :checked="form.all_day" @update:checked="toggleAllDay" />
                All day
            </label>
        </div>

        <FormField label="Start" required :error="form.errors.starts_at">
            <TextInput id="starts_at" v-model="form.starts_at" :type="whenType" class="block w-full" required />
        </FormField>

        <FormField label="End" required :error="form.errors.ends_at">
            <TextInput id="ends_at" v-model="form.ends_at" :type="whenType" class="block w-full" required />
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

        <FormField label="Location" :error="form.errors.location">
            <TextInput id="location" v-model="form.location" class="block w-full" maxlength="255" />
        </FormField>

        <FormField label="Show time as" :error="form.errors.show_time_as">
            <SelectInput
                id="show_time_as"
                v-model="form.show_time_as"
                :options="showTimeOptions"
                placeholder="Select availability"
            />
        </FormField>

        <div class="flex items-end">
            <label class="flex min-h-11 items-center gap-2 text-body text-text">
                <Checkbox v-model:checked="form.is_private" />
                Private
            </label>
        </div>

        <FormField label="Description" span :error="form.errors.description">
            <TextArea id="description" v-model="form.description" rows="4" />
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
