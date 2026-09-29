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
    accounts: {
        type: Array,
        required: true,
    },
    stages: {
        type: Array,
        required: true,
    },
    types: {
        type: Array,
        required: true,
    },
    sources: {
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

const stageOptions = computed(() =>
    props.stages.map((stage) => ({ value: stage, label: stage })),
);

const typeOptions = computed(() =>
    props.types.map((type) => ({ value: type, label: type })),
);

const sourceOptions = computed(() =>
    props.sources.map((source) => ({ value: source, label: source })),
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
        <h2 class="text-h2 md:col-span-2">Opportunity details</h2>

        <FormField label="Opportunity name" required :error="form.errors.name">
            <TextInput id="name" v-model="form.name" class="block w-full" required maxlength="120" />
        </FormField>

        <FormField label="Account" required :error="form.errors.account_id">
            <SelectInput
                id="account_id"
                v-model="form.account_id"
                :options="accountOptions"
                placeholder="Select an account"
            />
        </FormField>

        <FormField label="Close date" required :error="form.errors.close_date">
            <TextInput
                id="close_date"
                v-model="form.close_date"
                type="date"
                class="block w-full"
                required
            />
        </FormField>

        <FormField label="Stage" required :error="form.errors.stage">
            <SelectInput id="stage" v-model="form.stage" :options="stageOptions" />
        </FormField>

        <FormField label="Amount" :error="form.errors.amount">
            <TextInput
                id="amount"
                v-model="form.amount"
                type="number"
                min="0.01"
                step="0.01"
                class="block w-full"
            />
        </FormField>

        <FormField label="Type" :error="form.errors.type">
            <SelectInput
                id="type"
                v-model="form.type"
                :options="typeOptions"
                placeholder="None"
            />
        </FormField>

        <FormField label="Lead source" :error="form.errors.lead_source">
            <SelectInput
                id="lead_source"
                v-model="form.lead_source"
                :options="sourceOptions"
                placeholder="None"
            />
        </FormField>

        <FormField label="Next step" :error="form.errors.next_step">
            <TextInput id="next_step" v-model="form.next_step" class="block w-full" maxlength="255" />
        </FormField>

        <FormField
            v-if="canReassign"
            label="Owner"
            :error="form.errors.owner_id"
        >
            <SelectInput
                id="owner_id"
                v-model="form.owner_id"
                :options="ownerOptions"
                placeholder="Keep current owner"
            />
        </FormField>

        <FormField label="Description" span :error="form.errors.description">
            <TextArea id="description" v-model="form.description" class="block w-full" rows="4" />
        </FormField>

        <div class="flex flex-wrap gap-2 md:col-span-2">
            <PrimaryButton type="submit" class="min-h-11" :disabled="form.processing">
                Save
            </PrimaryButton>
            <SecondaryButton
                type="button"
                class="min-h-11"
                :disabled="form.processing"
                @click="emit('submit', true)"
            >
                Save &amp; New
            </SecondaryButton>
            <Link
                :href="cancelHref"
                class="inline-flex min-h-11 items-center rounded-md border border-border bg-surface px-4 py-2 text-small font-semibold uppercase tracking-widest text-text"
            >
                Cancel
            </Link>
        </div>
    </form>
</template>
