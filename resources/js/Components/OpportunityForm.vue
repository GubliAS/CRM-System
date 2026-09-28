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
    leadSources: {
        type: Array,
        required: true,
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
    props.stages.map((stage) => ({
        value: stage.name,
        label: stage.name,
    })),
);

const typeOptions = computed(() =>
    props.types.map((type) => ({
        value: type,
        label: type,
    })),
);

const leadSourceOptions = computed(() =>
    props.leadSources.map((source) => ({
        value: source,
        label: source,
    })),
);

const probabilityLabel = computed(() => {
    const match = props.stages.find((stage) => stage.name === props.form.stage);

    return match ? `${match.probability}%` : '—';
});
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
                required
            />
        </FormField>

        <FormField label="Close date" required :error="form.errors.close_date">
            <TextInput id="close_date" v-model="form.close_date" type="date" class="block w-full" required />
        </FormField>

        <FormField label="Stage" required :error="form.errors.stage">
            <SelectInput id="stage" v-model="form.stage" :options="stageOptions" placeholder="Select a stage" required />
        </FormField>

        <FormField label="Amount" :error="form.errors.amount">
            <TextInput id="amount" v-model="form.amount" type="number" min="0.01" step="0.01" class="block w-full" />
        </FormField>

        <FormField label="Probability">
            <p id="probability" class="rounded-md border border-border bg-bg px-3 py-2 text-body text-text">
                {{ probabilityLabel }}
            </p>
        </FormField>

        <FormField label="Type" :error="form.errors.type">
            <SelectInput id="type" v-model="form.type" :options="typeOptions" placeholder="Select a type" />
        </FormField>

        <FormField label="Lead source" :error="form.errors.lead_source">
            <SelectInput
                id="lead_source"
                v-model="form.lead_source"
                :options="leadSourceOptions"
                placeholder="Select a lead source"
            />
        </FormField>

        <FormField label="Next step" span :error="form.errors.next_step">
            <TextInput id="next_step" v-model="form.next_step" class="block w-full" maxlength="255" />
        </FormField>

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
