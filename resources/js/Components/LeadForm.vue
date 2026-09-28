<script setup>
import FormField from '@/Components/FormField.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
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
    salutations: {
        type: Array,
        required: true,
    },
    statuses: {
        type: Array,
        required: true,
    },
    sources: {
        type: Array,
        required: true,
    },
    ratings: {
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

const salutationOptions = computed(() => optionList(props.salutations));
const statusOptions = computed(() => optionList(props.statuses));
const sourceOptions = computed(() => optionList(props.sources));
const ratingOptions = computed(() => optionList(props.ratings));
const ownerOptions = computed(() =>
    props.owners.map((owner) => ({
        value: String(owner.id),
        label: owner.name,
    })),
);
</script>

<template>
    <form class="grid grid-cols-1 gap-4 md:grid-cols-2" @submit.prevent="emit('submit', false)">
        <h2 class="text-h2 md:col-span-2">Lead details</h2>

        <FormField label="Salutation" :error="form.errors.salutation">
            <SelectInput
                id="salutation"
                v-model="form.salutation"
                :options="salutationOptions"
                placeholder="Select"
            />
        </FormField>

        <FormField label="First name" :error="form.errors.first_name">
            <TextInput id="first_name" v-model="form.first_name" class="block w-full" maxlength="40" />
        </FormField>

        <FormField label="Last name" required :error="form.errors.last_name">
            <TextInput id="last_name" v-model="form.last_name" class="block w-full" required maxlength="80" />
        </FormField>

        <FormField label="Company" required :error="form.errors.company">
            <TextInput id="company" v-model="form.company" class="block w-full" required maxlength="255" />
        </FormField>

        <FormField label="Title" :error="form.errors.title">
            <TextInput id="title" v-model="form.title" class="block w-full" maxlength="128" />
        </FormField>

        <FormField label="Lead status" required :error="form.errors.lead_status">
            <SelectInput id="lead_status" v-model="form.lead_status" :options="statusOptions" required />
        </FormField>

        <FormField label="Phone" :error="form.errors.phone">
            <TextInput id="phone" v-model="form.phone" class="block w-full" maxlength="40" />
        </FormField>

        <FormField label="Mobile" :error="form.errors.mobile">
            <TextInput id="mobile" v-model="form.mobile" class="block w-full" maxlength="40" />
        </FormField>

        <FormField label="Email" :error="form.errors.email">
            <TextInput id="email" v-model="form.email" type="email" class="block w-full" maxlength="80" />
        </FormField>

        <FormField label="Website" :error="form.errors.website">
            <TextInput id="website" v-model="form.website" class="block w-full" maxlength="255" />
        </FormField>

        <FormField label="Lead source" :error="form.errors.lead_source">
            <SelectInput
                id="lead_source"
                v-model="form.lead_source"
                :options="sourceOptions"
                placeholder="Select"
            />
        </FormField>

        <FormField label="Rating" :error="form.errors.rating">
            <SelectInput id="rating" v-model="form.rating" :options="ratingOptions" placeholder="Select" />
        </FormField>

        <FormField label="Industry" :error="form.errors.industry">
            <TextInput id="industry" v-model="form.industry" class="block w-full" maxlength="80" />
        </FormField>

        <FormField v-if="canReassign" label="Owner" :error="form.errors.owner_id">
            <SelectInput id="owner_id" v-model="form.owner_id" :options="ownerOptions" placeholder="Select an owner" />
        </FormField>

        <h2 class="mt-2 text-h2 md:col-span-2">Address</h2>

        <FormField label="Street" span :error="form.errors.street">
            <TextInput id="street" v-model="form.street" class="block w-full" maxlength="255" />
        </FormField>

        <FormField label="City" :error="form.errors.city">
            <TextInput id="city" v-model="form.city" class="block w-full" maxlength="80" />
        </FormField>

        <FormField label="State" :error="form.errors.state">
            <TextInput id="state" v-model="form.state" class="block w-full" maxlength="80" />
        </FormField>

        <FormField label="Postal code" :error="form.errors.postal_code">
            <TextInput id="postal_code" v-model="form.postal_code" class="block w-full" maxlength="80" />
        </FormField>

        <FormField label="Country" :error="form.errors.country">
            <TextInput id="country" v-model="form.country" class="block w-full" maxlength="80" />
        </FormField>

        <h2 class="mt-2 text-h2 md:col-span-2">Additional information</h2>

        <FormField label="Annual revenue" :error="form.errors.annual_revenue">
            <TextInput
                id="annual_revenue"
                v-model="form.annual_revenue"
                type="number"
                min="0"
                step="0.01"
                class="block w-full"
            />
        </FormField>

        <FormField label="Number of employees" :error="form.errors.number_of_employees">
            <TextInput
                id="number_of_employees"
                v-model="form.number_of_employees"
                type="number"
                min="0"
                class="block w-full"
            />
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
