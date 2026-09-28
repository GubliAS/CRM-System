<script setup>
import FormField from '@/Components/FormField.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import SelectInput from '@/Components/SelectInput.vue';
import TextArea from '@/Components/TextArea.vue';
import TextInput from '@/Components/TextInput.vue';
import { contactLabel } from '@/forms/case';
import { Link } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    statuses: {
        type: Array,
        required: true,
    },
    origins: {
        type: Array,
        required: true,
    },
    priorities: {
        type: Array,
        required: true,
    },
    types: {
        type: Array,
        required: true,
    },
    reasons: {
        type: Array,
        required: true,
    },
    accounts: {
        type: Array,
        required: true,
    },
    contacts: {
        type: Array,
        required: true,
    },
    owners: {
        type: Array,
        default: () => [],
    },
    canReassign: {
        type: Boolean,
        default: false,
    },
    cancelHref: {
        type: String,
        required: true,
    },
});

const emit = defineEmits(['submit']);

const optionList = (items) => items.map((item) => ({ value: item, label: item }));

const statusOptions = computed(() => optionList(props.statuses));
const originOptions = computed(() => optionList(props.origins));
const priorityOptions = computed(() => optionList(props.priorities));
const typeOptions = computed(() => optionList(props.types));
const reasonOptions = computed(() => optionList(props.reasons));

const accountOptions = computed(() =>
    props.accounts.map((account) => ({
        value: String(account.id),
        label: account.name,
    })),
);

const contactOptions = computed(() =>
    props.contacts.map((contact) => ({
        value: String(contact.id),
        label: contactLabel(contact),
    })),
);

const ownerOptions = computed(() =>
    props.owners.map((owner) => ({
        value: String(owner.id),
        label: owner.name,
    })),
);

watch(
    () => props.form.contact_id,
    (contactId) => {
        if (!contactId) {
            return;
        }

        const contact = props.contacts.find((item) => String(item.id) === String(contactId));

        if (contact?.account_id) {
            props.form.account_id = String(contact.account_id);
        }
    },
);
</script>

<template>
    <form class="grid grid-cols-1 gap-4 md:grid-cols-2" @submit.prevent="emit('submit', false)">
        <h2 class="text-h2 md:col-span-2">Case details</h2>

        <FormField label="Contact" :error="form.errors.contact_id">
            <SelectInput
                id="contact_id"
                v-model="form.contact_id"
                :options="contactOptions"
                placeholder="Select a contact"
            />
        </FormField>

        <FormField label="Account" :error="form.errors.account_id">
            <SelectInput
                id="account_id"
                v-model="form.account_id"
                :options="accountOptions"
                placeholder="Select an account"
            />
        </FormField>

        <FormField label="Subject" span :error="form.errors.subject">
            <TextInput id="subject" v-model="form.subject" class="block w-full" maxlength="255" />
        </FormField>

        <FormField label="Status" required :error="form.errors.status">
            <SelectInput id="status" v-model="form.status" :options="statusOptions" required />
        </FormField>

        <FormField label="Case origin" required :error="form.errors.origin">
            <SelectInput id="origin" v-model="form.origin" :options="originOptions" required />
        </FormField>

        <FormField label="Priority" :error="form.errors.priority">
            <SelectInput
                id="priority"
                v-model="form.priority"
                :options="priorityOptions"
                placeholder="Select"
            />
        </FormField>

        <FormField label="Type" :error="form.errors.type">
            <SelectInput id="type" v-model="form.type" :options="typeOptions" placeholder="Select" />
        </FormField>

        <FormField label="Case reason" :error="form.errors.reason">
            <SelectInput
                id="reason"
                v-model="form.reason"
                :options="reasonOptions"
                placeholder="Select"
            />
        </FormField>

        <FormField v-if="canReassign" label="Owner" :error="form.errors.owner_id">
            <SelectInput
                id="owner_id"
                v-model="form.owner_id"
                :options="ownerOptions"
                placeholder="Select an owner"
            />
        </FormField>

        <FormField label="Description" span :error="form.errors.description">
            <TextArea id="description" v-model="form.description" rows="4" />
        </FormField>

        <FormField label="Internal comments" span :error="form.errors.internal_comments">
            <TextArea id="internal_comments" v-model="form.internal_comments" rows="3" />
        </FormField>

        <h2 class="mt-2 text-h2 md:col-span-2">Web information</h2>

        <FormField label="Web name" :error="form.errors.web_name">
            <TextInput id="web_name" v-model="form.web_name" class="block w-full" maxlength="80" />
        </FormField>

        <FormField label="Web email" :error="form.errors.web_email">
            <TextInput
                id="web_email"
                v-model="form.web_email"
                type="email"
                class="block w-full"
                maxlength="80"
            />
        </FormField>

        <FormField label="Web company" :error="form.errors.web_company">
            <TextInput
                id="web_company"
                v-model="form.web_company"
                class="block w-full"
                maxlength="255"
            />
        </FormField>

        <FormField label="Web phone" :error="form.errors.web_phone">
            <TextInput id="web_phone" v-model="form.web_phone" class="block w-full" maxlength="40" />
        </FormField>

        <div class="flex flex-wrap items-center gap-2 md:col-span-2">
            <PrimaryButton class="min-h-11" :disabled="form.processing">Save</PrimaryButton>
            <SecondaryButton
                type="button"
                class="min-h-11"
                :disabled="form.processing"
                @click="emit('submit', true)"
            >
                Save & New
            </SecondaryButton>
            <Link
                :href="cancelHref"
                class="inline-flex min-h-11 items-center px-3 text-body text-secondary"
            >
                Cancel
            </Link>
        </div>
    </form>
</template>
