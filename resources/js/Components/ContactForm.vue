<script setup>
import FormField from '@/Components/FormField.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import SelectInput from '@/Components/SelectInput.vue';
import TextArea from '@/Components/TextArea.vue';
import TextInput from '@/Components/TextInput.vue';
import { contactLabel } from '@/forms/contact';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    accounts: {
        type: Array,
        required: true,
    },
    reportsTo: {
        type: Array,
        required: true,
    },
    salutations: {
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

const accountOptions = computed(() =>
    props.accounts.map((account) => ({
        value: String(account.id),
        label: account.name,
    })),
);

const reportOptions = computed(() =>
    props.reportsTo.map((contact) => ({
        value: String(contact.id),
        label: contactLabel(contact),
    })),
);

const salutationOptions = computed(() =>
    props.salutations.map((salutation) => ({
        value: salutation,
        label: salutation,
    })),
);

const ownerOptions = computed(() =>
    props.owners.map((owner) => ({
        value: String(owner.id),
        label: owner.name,
    })),
);
</script>

<template>
    <form class="grid grid-cols-1 gap-4 md:grid-cols-2" @submit.prevent="emit('submit', false)">
        <h2 class="text-h2 md:col-span-2">Contact details</h2>

        <FormField label="Salutation" :error="form.errors.salutation">
            <SelectInput
                id="salutation"
                v-model="form.salutation"
                :options="salutationOptions"
                placeholder="None"
            />
        </FormField>

        <FormField label="First name" :error="form.errors.first_name">
            <TextInput id="first_name" v-model="form.first_name" class="block w-full" maxlength="40" />
        </FormField>

        <FormField label="Last name" required :error="form.errors.last_name">
            <TextInput id="last_name" v-model="form.last_name" class="block w-full" required maxlength="80" />
        </FormField>

        <FormField label="Account" required :error="form.errors.account_id">
            <SelectInput
                id="account_id"
                v-model="form.account_id"
                :options="accountOptions"
                placeholder="Select an account"
                required
            />
        </FormField>

        <FormField label="Title" :error="form.errors.title">
            <TextInput id="title" v-model="form.title" class="block w-full" maxlength="128" />
        </FormField>

        <FormField label="Department" :error="form.errors.department">
            <TextInput id="department" v-model="form.department" class="block w-full" maxlength="80" />
        </FormField>

        <FormField label="Reports to" :error="form.errors.reports_to_id">
            <SelectInput
                id="reports_to_id"
                v-model="form.reports_to_id"
                :options="reportOptions"
                placeholder="None"
            />
        </FormField>

        <FormField v-if="canReassign" label="Owner" :error="form.errors.owner_id">
            <SelectInput id="owner_id" v-model="form.owner_id" :options="ownerOptions" placeholder="Select an owner" />
        </FormField>

        <h2 class="mt-2 text-h2 md:col-span-2">Phone and email</h2>

        <FormField label="Phone" :error="form.errors.phone">
            <TextInput id="phone" v-model="form.phone" class="block w-full" maxlength="40" />
        </FormField>

        <FormField label="Mobile" :error="form.errors.mobile">
            <TextInput id="mobile" v-model="form.mobile" class="block w-full" maxlength="40" />
        </FormField>

        <FormField label="Home phone" :error="form.errors.home_phone">
            <TextInput id="home_phone" v-model="form.home_phone" class="block w-full" maxlength="40" />
        </FormField>

        <FormField label="Other phone" :error="form.errors.other_phone">
            <TextInput id="other_phone" v-model="form.other_phone" class="block w-full" maxlength="40" />
        </FormField>

        <FormField label="Email" :error="form.errors.email">
            <TextInput id="email" v-model="form.email" type="email" class="block w-full" maxlength="80" />
        </FormField>

        <FormField label="Fax" :error="form.errors.fax">
            <TextInput id="fax" v-model="form.fax" class="block w-full" maxlength="40" />
        </FormField>

        <h2 class="mt-2 text-h2 md:col-span-2">Address</h2>

        <FormField label="Mailing street" span :error="form.errors.mailing_street">
            <TextInput id="mailing_street" v-model="form.mailing_street" class="block w-full" maxlength="255" />
        </FormField>

        <FormField label="Mailing city" :error="form.errors.mailing_city">
            <TextInput id="mailing_city" v-model="form.mailing_city" class="block w-full" maxlength="80" />
        </FormField>

        <FormField label="Mailing state" :error="form.errors.mailing_state">
            <TextInput id="mailing_state" v-model="form.mailing_state" class="block w-full" maxlength="80" />
        </FormField>

        <FormField label="Mailing postal code" :error="form.errors.mailing_postal_code">
            <TextInput
                id="mailing_postal_code"
                v-model="form.mailing_postal_code"
                class="block w-full"
                maxlength="80"
            />
        </FormField>

        <FormField label="Mailing country" :error="form.errors.mailing_country">
            <TextInput id="mailing_country" v-model="form.mailing_country" class="block w-full" maxlength="80" />
        </FormField>

        <FormField label="Other street" span :error="form.errors.other_street">
            <TextInput id="other_street" v-model="form.other_street" class="block w-full" maxlength="255" />
        </FormField>

        <FormField label="Other city" :error="form.errors.other_city">
            <TextInput id="other_city" v-model="form.other_city" class="block w-full" maxlength="80" />
        </FormField>

        <FormField label="Other state" :error="form.errors.other_state">
            <TextInput id="other_state" v-model="form.other_state" class="block w-full" maxlength="80" />
        </FormField>

        <FormField label="Other postal code" :error="form.errors.other_postal_code">
            <TextInput
                id="other_postal_code"
                v-model="form.other_postal_code"
                class="block w-full"
                maxlength="80"
            />
        </FormField>

        <FormField label="Other country" :error="form.errors.other_country">
            <TextInput id="other_country" v-model="form.other_country" class="block w-full" maxlength="80" />
        </FormField>

        <h2 class="mt-2 text-h2 md:col-span-2">Additional information</h2>

        <FormField label="Assistant" :error="form.errors.assistant">
            <TextInput id="assistant" v-model="form.assistant" class="block w-full" maxlength="80" />
        </FormField>

        <FormField label="Assistant phone" :error="form.errors.assistant_phone">
            <TextInput id="assistant_phone" v-model="form.assistant_phone" class="block w-full" maxlength="40" />
        </FormField>

        <FormField label="Lead source" :error="form.errors.lead_source">
            <TextInput id="lead_source" v-model="form.lead_source" class="block w-full" maxlength="40" />
        </FormField>

        <FormField label="Birthdate" :error="form.errors.birthdate">
            <TextInput id="birthdate" v-model="form.birthdate" type="date" class="block w-full" />
        </FormField>

        <FormField label="Description" span :error="form.errors.description">
            <TextArea id="description" v-model="form.description" rows="4" />
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
