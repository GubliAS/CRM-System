<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import DangerButton from '@/Components/DangerButton.vue';
import DetailField from '@/Components/DetailField.vue';
import FormField from '@/Components/FormField.vue';
import InputError from '@/Components/InputError.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import SelectInput from '@/Components/SelectInput.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { display, formatMoney, formatWhen, fullName, personName, websiteHref } from '@/display';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    lead: { type: Object, required: true },
    matchingAccounts: { type: Array, default: () => [] },
    statuses: { type: Array, required: true },
    owners: { type: Array, default: () => [] },
    opportunityStages: { type: Array, required: true },
    can: { type: Object, required: true },
});

const page = usePage();
const confirmingDelete = ref(false);
const showingConvert = ref(false);
const showingOwner = ref(false);
const showingStatus = ref(false);

const deleteForm = useForm({});
const statusForm = useForm({
    lead_status: props.lead.lead_status === 'Converted' ? 'Qualified' : props.lead.lead_status,
});
const ownerForm = useForm({
    owner_id: props.lead.owner_id ? String(props.lead.owner_id) : '',
    transfer_activities: false,
});
const convertForm = useForm({
    account_mode: props.matchingAccounts.length > 0 ? 'existing' : 'new',
    account_id: props.matchingAccounts[0] ? String(props.matchingAccounts[0].id) : '',
    create_opportunity: false,
    opportunity_name: `${props.lead.company} - Opportunity`,
    opportunity_amount: '',
    opportunity_close_date: '',
    opportunity_stage: props.opportunityStages[0] ?? 'Qualification',
    transfer_activities: true,
});

watch(
    () => props.lead.lead_status,
    (value) => {
        if (value !== 'Converted') {
            statusForm.lead_status = value;
        }
    },
);

const title = computed(() => fullName(props.lead));
const siteHref = computed(() => websiteHref(props.lead.website));

const statusTone = computed(() => {
    const value = String(props.lead.lead_status ?? '').toLowerCase();

    if (value === 'converted') {
        return 'success';
    }

    if (value === 'unqualified') {
        return 'neutral';
    }

    if (value === 'qualified') {
        return 'info';
    }

    return 'warning';
});

const deleteError = computed(() => {
    const value = deleteForm.errors.delete || page.props.errors?.delete || '';

    return Array.isArray(value) ? value[0] : value;
});

const statusOptions = computed(() =>
    props.statuses.map((status) => ({ value: status, label: status })),
);

const ownerOptions = computed(() =>
    props.owners.map((owner) => ({
        value: String(owner.id),
        label: owner.name,
    })),
);

const matchingAccountOptions = computed(() =>
    props.matchingAccounts.map((account) => ({
        value: String(account.id),
        label: account.name,
    })),
);

const stageOptions = computed(() =>
    props.opportunityStages.map((stage) => ({ value: stage, label: stage })),
);

function destroy() {
    deleteForm.delete(route('leads.destroy', props.lead.id), {
        preserveScroll: true,
    });
}

function submitStatus() {
    statusForm.post(route('leads.status', props.lead.id), {
        preserveScroll: true,
        onSuccess: () => {
            showingStatus.value = false;
        },
    });
}

function submitOwner() {
    ownerForm.transform((data) => ({
        ...data,
        transfer_activities: Boolean(data.transfer_activities),
    })).post(route('leads.owner', props.lead.id), {
        preserveScroll: true,
        onSuccess: () => {
            showingOwner.value = false;
        },
    });
}

function submitConvert() {
    convertForm
        .transform((data) => {
            const payload = {
                account_mode: data.account_mode,
                create_opportunity: Boolean(data.create_opportunity),
                transfer_activities: Boolean(data.transfer_activities),
            };

            if (data.account_mode === 'existing') {
                payload.account_id = data.account_id;
            }

            if (payload.create_opportunity) {
                payload.opportunity_name = data.opportunity_name;
                payload.opportunity_amount = data.opportunity_amount || null;
                payload.opportunity_close_date = data.opportunity_close_date;
                payload.opportunity_stage = data.opportunity_stage;
            }

            return payload;
        })
        .post(route('leads.convert', props.lead.id), {
            preserveScroll: true,
            onSuccess: () => {
                showingConvert.value = false;
            },
        });
}
</script>

<template>
    <AuthenticatedLayout>
        <Head :title="title" />

        <div class="crm-page">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="break-words text-h1">{{ title }}</h1>
                        <StatusBadge :tone="statusTone">{{ lead.lead_status }}</StatusBadge>
                    </div>
                    <p class="mt-1 text-body text-text-muted">
                        {{ display(lead.company) }}
                        <span v-if="lead.converted" class="text-warning"> · Converted (read-only)</span>
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <SecondaryButton
                        v-if="can.changeStatus"
                        type="button"
                        class="min-h-11"
                        @click="showingStatus = true"
                    >
                        Change status
                    </SecondaryButton>
                    <SecondaryButton
                        v-if="can.changeOwner"
                        type="button"
                        class="min-h-11"
                        @click="showingOwner = true"
                    >
                        Change owner
                    </SecondaryButton>
                    <PrimaryButton
                        v-if="can.convert"
                        type="button"
                        class="min-h-11"
                        @click="showingConvert = true"
                    >
                        Convert
                    </PrimaryButton>
                    <Link
                        v-if="can.update"
                        :href="route('leads.edit', lead.id)"
                        class="crm-btn-secondary"
                    >
                        Edit
                    </Link>
                    <DangerButton
                        v-if="can.delete"
                        type="button"
                        class="min-h-11"
                        @click="confirmingDelete = true"
                    >
                        Delete
                    </DangerButton>
                </div>
            </div>

            <InputError class="mt-3" :message="deleteError" />

            <div class="mt-6 space-y-4">
                <section class="crm-panel">
                    <h2 class="text-h2">Lead details</h2>
                    <dl class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <DetailField label="Salutation" :value="lead.salutation" />
                        <DetailField label="First name" :value="lead.first_name" />
                        <DetailField label="Last name" :value="lead.last_name" />
                        <DetailField label="Company" :value="lead.company" />
                        <DetailField label="Title" :value="lead.title" />
                        <DetailField label="Lead status" :value="lead.lead_status" />
                        <DetailField label="Phone" :value="lead.phone" />
                        <DetailField label="Mobile" :value="lead.mobile" />
                        <DetailField label="Email" :value="lead.email" />
                        <DetailField label="Website">
                            <a v-if="siteHref" :href="siteHref" class="text-secondary underline">
                                {{ lead.website }}
                            </a>
                            <span v-else>{{ display(lead.website) }}</span>
                        </DetailField>
                        <DetailField label="Lead source" :value="lead.lead_source" />
                        <DetailField label="Rating" :value="lead.rating" />
                        <DetailField label="Owner" :value="lead.owner?.name" />
                    </dl>
                </section>

                <section class="crm-panel">
                    <h2 class="text-h2">Address</h2>
                    <dl class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <DetailField label="Street" :value="lead.street" />
                        <DetailField label="City" :value="lead.city" />
                        <DetailField label="State" :value="lead.state" />
                        <DetailField label="Postal code" :value="lead.postal_code" />
                        <DetailField label="Country" :value="lead.country" />
                    </dl>
                </section>

                <section class="crm-panel">
                    <h2 class="text-h2">Additional information</h2>
                    <dl class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <DetailField label="Industry" :value="lead.industry" />
                        <DetailField label="Annual revenue" :value="formatMoney(lead.annual_revenue)" />
                        <DetailField label="Number of employees" :value="lead.number_of_employees" />
                        <DetailField label="Description" :value="lead.description" />
                    </dl>
                </section>

                <section v-if="lead.converted" class="crm-panel">
                    <h2 class="text-h2">Converted records</h2>
                    <dl class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <DetailField label="Account">
                            <Link
                                v-if="lead.converted_account"
                                :href="route('accounts.show', lead.converted_account.id)"
                                class="text-secondary underline"
                            >
                                {{ lead.converted_account.name }}
                            </Link>
                            <span v-else>—</span>
                        </DetailField>
                        <DetailField label="Contact">
                            <Link
                                v-if="lead.converted_contact"
                                :href="route('contacts.show', lead.converted_contact.id)"
                                class="text-secondary underline"
                            >
                                {{ fullName(lead.converted_contact) }}
                            </Link>
                            <span v-else>—</span>
                        </DetailField>
                        <DetailField label="Opportunity">
                            <span v-if="lead.converted_opportunity">
                                {{ lead.converted_opportunity.name }}
                            </span>
                            <span v-else>—</span>
                        </DetailField>
                    </dl>
                </section>

                <section class="crm-panel">
                    <h2 class="text-h2">System information</h2>
                    <dl class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <DetailField label="Created by" :value="personName(lead.created_by)" />
                        <DetailField label="Created at" :value="formatWhen(lead.created_at)" />
                        <DetailField label="Updated by" :value="personName(lead.updated_by)" />
                        <DetailField label="Updated at" :value="formatWhen(lead.updated_at)" />
                    </dl>
                </section>
            </div>
        </div>

        <Modal :show="confirmingDelete" max-width="md" @close="confirmingDelete = false">
            <div class="p-6">
                <h2 class="text-h2 text-text">Delete this lead?</h2>
                <p class="mt-2 text-body text-text-muted">
                    {{ title }} will be removed. This cannot be undone.
                </p>
                <div class="mt-6 flex flex-wrap gap-2">
                    <DangerButton
                        type="button"
                        class="min-h-11"
                        :disabled="deleteForm.processing"
                        @click="destroy"
                    >
                        Delete
                    </DangerButton>
                    <SecondaryButton type="button" class="min-h-11" @click="confirmingDelete = false">
                        Cancel
                    </SecondaryButton>
                </div>
            </div>
        </Modal>

        <Modal :show="showingStatus" max-width="md" @close="showingStatus = false">
            <form class="space-y-4 p-6" @submit.prevent="submitStatus">
                <h2 class="text-h2 text-text">Change status</h2>
                <FormField label="Lead status" required :error="statusForm.errors.lead_status">
                    <SelectInput
                        id="change_lead_status"
                        v-model="statusForm.lead_status"
                        :options="statusOptions"
                        required
                    />
                </FormField>
                <div class="flex flex-wrap gap-2">
                    <PrimaryButton class="min-h-11" :disabled="statusForm.processing">Save</PrimaryButton>
                    <SecondaryButton type="button" class="min-h-11" @click="showingStatus = false">
                        Cancel
                    </SecondaryButton>
                </div>
            </form>
        </Modal>

        <Modal :show="showingOwner" max-width="md" @close="showingOwner = false">
            <form class="space-y-4 p-6" @submit.prevent="submitOwner">
                <h2 class="text-h2 text-text">Change owner</h2>
                <FormField label="Owner" required :error="ownerForm.errors.owner_id">
                    <SelectInput
                        id="change_owner_id"
                        v-model="ownerForm.owner_id"
                        :options="ownerOptions"
                        required
                    />
                </FormField>
                <label class="flex min-h-11 items-center gap-2 text-body text-text">
                    <Checkbox v-model:checked="ownerForm.transfer_activities" />
                    Transfer open tasks and events to the new owner
                </label>
                <div class="flex flex-wrap gap-2">
                    <PrimaryButton class="min-h-11" :disabled="ownerForm.processing">Save</PrimaryButton>
                    <SecondaryButton type="button" class="min-h-11" @click="showingOwner = false">
                        Cancel
                    </SecondaryButton>
                </div>
            </form>
        </Modal>

        <Modal :show="showingConvert" max-width="lg" @close="showingConvert = false">
            <form class="space-y-4 p-6" @submit.prevent="submitConvert">
                <h2 class="text-h2 text-text">Convert lead</h2>
                <p class="text-body text-text-muted">
                    Creates a contact from this lead. Match an existing account or create one from
                    {{ lead.company }}.
                </p>

                <fieldset class="space-y-2">
                    <legend class="text-small text-text-muted">Account</legend>
                    <label class="flex min-h-11 items-center gap-2 text-body text-text">
                        <input
                            v-model="convertForm.account_mode"
                            type="radio"
                            value="new"
                            class="border-border text-primary"
                        />
                        Create new account
                    </label>
                    <label class="flex min-h-11 items-center gap-2 text-body text-text">
                        <input
                            v-model="convertForm.account_mode"
                            type="radio"
                            value="existing"
                            class="border-border text-primary"
                            :disabled="matchingAccounts.length === 0"
                        />
                        Match existing account by company name
                    </label>
                    <FormField
                        v-if="convertForm.account_mode === 'existing'"
                        label="Account"
                        required
                        :error="convertForm.errors.account_id"
                    >
                        <SelectInput
                            id="convert_account_id"
                            v-model="convertForm.account_id"
                            :options="matchingAccountOptions"
                            required
                        />
                    </FormField>
                    <InputError :message="convertForm.errors.account_mode" />
                </fieldset>

                <label class="flex min-h-11 items-center gap-2 text-body text-text">
                    <Checkbox v-model:checked="convertForm.create_opportunity" />
                    Create opportunity
                </label>

                <div
                    v-if="convertForm.create_opportunity"
                    class="grid grid-cols-1 gap-4 rounded-md border border-border p-4 md:grid-cols-2"
                >
                    <FormField
                        label="Opportunity name"
                        required
                        span
                        :error="convertForm.errors.opportunity_name"
                    >
                        <TextInput
                            id="opportunity_name"
                            v-model="convertForm.opportunity_name"
                            class="block w-full"
                            maxlength="120"
                            required
                        />
                    </FormField>
                    <FormField label="Amount" :error="convertForm.errors.opportunity_amount">
                        <TextInput
                            id="opportunity_amount"
                            v-model="convertForm.opportunity_amount"
                            type="number"
                            min="0.01"
                            step="0.01"
                            class="block w-full"
                        />
                    </FormField>
                    <FormField
                        label="Close date"
                        required
                        :error="convertForm.errors.opportunity_close_date"
                    >
                        <TextInput
                            id="opportunity_close_date"
                            v-model="convertForm.opportunity_close_date"
                            type="date"
                            class="block w-full"
                            required
                        />
                    </FormField>
                    <FormField label="Stage" required :error="convertForm.errors.opportunity_stage">
                        <SelectInput
                            id="opportunity_stage"
                            v-model="convertForm.opportunity_stage"
                            :options="stageOptions"
                            required
                        />
                    </FormField>
                </div>

                <label class="flex min-h-11 items-center gap-2 text-body text-text">
                    <Checkbox v-model:checked="convertForm.transfer_activities" />
                    Move open tasks and events to the new records
                </label>

                <div class="flex flex-wrap gap-2">
                    <PrimaryButton class="min-h-11" :disabled="convertForm.processing">
                        Convert
                    </PrimaryButton>
                    <SecondaryButton type="button" class="min-h-11" @click="showingConvert = false">
                        Cancel
                    </SecondaryButton>
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>
