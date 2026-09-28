<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import FormField from '@/Components/FormField.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import SelectInput from '@/Components/SelectInput.vue';
import TextArea from '@/Components/TextArea.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    parentAccounts: {
        type: Array,
        required: true,
    },
    types: {
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

const copyBilling = ref(false);

const parentOptions = computed(() =>
    props.parentAccounts.map((account) => ({
        value: String(account.id),
        label: account.name,
    })),
);

const typeOptions = computed(() =>
    props.types.map((type) => ({
        value: type,
        label: type,
    })),
);

const ownerOptions = computed(() =>
    props.owners.map((owner) => ({
        value: String(owner.id),
        label: owner.name,
    })),
);

const billingPairs = [
    ['billing_street', 'shipping_street'],
    ['billing_city', 'shipping_city'],
    ['billing_state', 'shipping_state'],
    ['billing_postal_code', 'shipping_postal_code'],
    ['billing_country', 'shipping_country'],
];

function syncShipping() {
    if (!copyBilling.value) {
        return;
    }

    billingPairs.forEach(([from, to]) => {
        props.form[to] = props.form[from];
    });
}

watch(copyBilling, syncShipping);

watch(
    () => billingPairs.map(([from]) => props.form[from]),
    syncShipping,
);
</script>

<template>
    <form class="grid grid-cols-1 gap-4 md:grid-cols-2" @submit.prevent="emit('submit', false)">
        <h2 class="text-h2 md:col-span-2">Account details</h2>

        <FormField label="Account name" required :error="form.errors.name">
            <TextInput id="name" v-model="form.name" class="mt-0 block w-full" required maxlength="255" />
        </FormField>

        <FormField label="Parent account" :error="form.errors.parent_account_id">
            <SelectInput
                id="parent_account_id"
                v-model="form.parent_account_id"
                :options="parentOptions"
                placeholder="None"
            />
        </FormField>

        <FormField label="Phone" :error="form.errors.phone">
            <TextInput id="phone" v-model="form.phone" class="block w-full" maxlength="40" />
        </FormField>

        <FormField label="Fax" :error="form.errors.fax">
            <TextInput id="fax" v-model="form.fax" class="block w-full" maxlength="40" />
        </FormField>

        <FormField label="Website" :error="form.errors.website">
            <TextInput id="website" v-model="form.website" class="block w-full" maxlength="255" />
        </FormField>

        <FormField label="Type" :error="form.errors.type">
            <SelectInput id="type" v-model="form.type" :options="typeOptions" placeholder="Select a type" />
        </FormField>

        <FormField label="Industry" :error="form.errors.industry">
            <TextInput id="industry" v-model="form.industry" class="block w-full" maxlength="80" />
        </FormField>

        <FormField v-if="canReassign" label="Owner" :error="form.errors.owner_id">
            <SelectInput id="owner_id" v-model="form.owner_id" :options="ownerOptions" placeholder="Select an owner" />
        </FormField>

        <h2 class="mt-2 text-h2 md:col-span-2">Address</h2>

        <FormField label="Billing street" span :error="form.errors.billing_street">
            <TextInput id="billing_street" v-model="form.billing_street" class="block w-full" maxlength="255" />
        </FormField>

        <FormField label="Billing city" :error="form.errors.billing_city">
            <TextInput id="billing_city" v-model="form.billing_city" class="block w-full" maxlength="80" />
        </FormField>

        <FormField label="Billing state" :error="form.errors.billing_state">
            <TextInput id="billing_state" v-model="form.billing_state" class="block w-full" maxlength="80" />
        </FormField>

        <FormField label="Billing postal code" :error="form.errors.billing_postal_code">
            <TextInput
                id="billing_postal_code"
                v-model="form.billing_postal_code"
                class="block w-full"
                maxlength="80"
            />
        </FormField>

        <FormField label="Billing country" :error="form.errors.billing_country">
            <TextInput id="billing_country" v-model="form.billing_country" class="block w-full" maxlength="80" />
        </FormField>

        <label class="flex min-h-11 items-center gap-2 text-body text-text md:col-span-2">
            <Checkbox v-model:checked="copyBilling" />
            Copy billing address to shipping address
        </label>

        <FormField label="Shipping street" span :error="form.errors.shipping_street">
            <TextInput
                id="shipping_street"
                v-model="form.shipping_street"
                class="block w-full"
                maxlength="255"
                :disabled="copyBilling"
            />
        </FormField>

        <FormField label="Shipping city" :error="form.errors.shipping_city">
            <TextInput
                id="shipping_city"
                v-model="form.shipping_city"
                class="block w-full"
                maxlength="80"
                :disabled="copyBilling"
            />
        </FormField>

        <FormField label="Shipping state" :error="form.errors.shipping_state">
            <TextInput
                id="shipping_state"
                v-model="form.shipping_state"
                class="block w-full"
                maxlength="80"
                :disabled="copyBilling"
            />
        </FormField>

        <FormField label="Shipping postal code" :error="form.errors.shipping_postal_code">
            <TextInput
                id="shipping_postal_code"
                v-model="form.shipping_postal_code"
                class="block w-full"
                maxlength="80"
                :disabled="copyBilling"
            />
        </FormField>

        <FormField label="Shipping country" :error="form.errors.shipping_country">
            <TextInput
                id="shipping_country"
                v-model="form.shipping_country"
                class="block w-full"
                maxlength="80"
                :disabled="copyBilling"
            />
        </FormField>

        <h2 class="mt-2 text-h2 md:col-span-2">Additional information</h2>

        <FormField label="Employees" :error="form.errors.employees">
            <TextInput
                id="employees"
                v-model="form.employees"
                type="number"
                min="0"
                class="block w-full"
            />
        </FormField>

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
