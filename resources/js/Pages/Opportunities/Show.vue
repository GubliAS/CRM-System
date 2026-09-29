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
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { display, formatDay, formatMoney, formatWhen, personName } from '@/display';
import { stageToneClass } from '@/forms/opportunity';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    opportunity: { type: Object, required: true },
    stages: { type: Array, required: true },
    owners: { type: Array, default: () => [] },
    can: { type: Object, required: true },
});

const confirmingArchive = ref(false);
const showingOwner = ref(false);
const showingClone = ref(false);

const archiveForm = useForm({});
const ownerForm = useForm({
    owner_id: props.opportunity.owner_id ? String(props.opportunity.owner_id) : '',
    transfer_activities: false,
});
const cloneForm = useForm({
    include_related: false,
});

const ownerOptions = computed(() =>
    props.owners.map((owner) => ({
        value: String(owner.id),
        label: owner.name,
    })),
);

const currentStageIndex = computed(() =>
    props.stages.findIndex((stage) => stage === props.opportunity.stage),
);

function archive() {
    archiveForm.delete(route('opportunities.destroy', props.opportunity.id), {
        preserveScroll: true,
    });
}

function submitOwner() {
    ownerForm
        .transform((data) => ({
            ...data,
            transfer_activities: Boolean(data.transfer_activities),
        }))
        .post(route('opportunities.owner', props.opportunity.id), {
            preserveScroll: true,
            onSuccess: () => {
                showingOwner.value = false;
            },
        });
}

function submitClone() {
    cloneForm
        .transform((data) => ({
            include_related: Boolean(data.include_related),
        }))
        .post(route('opportunities.clone', props.opportunity.id), {
            preserveScroll: true,
            onSuccess: () => {
                showingClone.value = false;
            },
        });
}
</script>

<template>
    <AuthenticatedLayout>
        <Head :title="opportunity.name" />

        <div class="crm-page">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="text-small text-text-muted">
                        <Link
                            :href="route('opportunities.index')"
                            class="text-secondary underline"
                        >
                            Opportunities
                        </Link>
                    </p>
                    <h1 class="mt-1 break-words text-h1">{{ opportunity.name }}</h1>
                    <p v-if="opportunity.archived_at" class="mt-1 text-body text-warning">
                        Archived {{ formatWhen(opportunity.archived_at) }}
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <SecondaryButton
                        v-if="can.changeOwner"
                        type="button"
                        class="min-h-11"
                        @click="showingOwner = true"
                    >
                        Change owner
                    </SecondaryButton>
                    <SecondaryButton
                        v-if="can.clone"
                        type="button"
                        class="min-h-11"
                        @click="showingClone = true"
                    >
                        Clone
                    </SecondaryButton>
                    <Link
                        v-if="can.update"
                        :href="route('opportunities.edit', opportunity.id)"
                        class="inline-flex min-h-11 items-center rounded-md border border-border bg-surface px-4 py-2 text-small font-semibold uppercase tracking-widest text-text"
                    >
                        Edit
                    </Link>
                    <DangerButton
                        v-if="can.delete"
                        type="button"
                        class="min-h-11"
                        @click="confirmingArchive = true"
                    >
                        Archive
                    </DangerButton>
                </div>
            </div>

            <section class="mt-6 crm-panel">
                <h2 class="text-h2">Stage path</h2>
                <ol class="mt-4 flex flex-wrap gap-2">
                    <li
                        v-for="(stage, index) in stages"
                        :key="stage"
                        class="inline-flex min-h-11 items-center rounded-md border px-3 text-small"
                        :class="
                            index === currentStageIndex
                                ? 'border-secondary bg-bg text-primary'
                                : index < currentStageIndex
                                  ? 'border-border bg-bg text-text-muted'
                                  : 'border-border bg-surface text-text-muted'
                        "
                    >
                        {{ stage }}
                    </li>
                </ol>
            </section>

            <div class="mt-4 space-y-4">
                <section class="crm-panel">
                    <h2 class="text-h2">Opportunity details</h2>
                    <dl class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <DetailField label="Opportunity name" :value="opportunity.name" />
                        <DetailField label="Account">
                            <Link
                                v-if="opportunity.account"
                                :href="route('accounts.show', opportunity.account.id)"
                                class="text-secondary underline"
                            >
                                {{ opportunity.account.name }}
                            </Link>
                            <span v-else>—</span>
                        </DetailField>
                        <DetailField
                            label="Amount"
                            :value="formatMoney(opportunity.amount)"
                        />
                        <DetailField
                            label="Close date"
                            :value="formatDay(opportunity.close_date)"
                        />
                        <DetailField label="Stage">
                            <span :class="stageToneClass(opportunity.stage)">
                                {{ opportunity.stage }}
                            </span>
                        </DetailField>
                        <DetailField
                            label="Probability"
                            :value="
                                opportunity.probability === null ||
                                opportunity.probability === undefined
                                    ? '—'
                                    : `${opportunity.probability}%`
                            "
                        />
                        <DetailField
                            label="Expected revenue"
                            :value="formatMoney(opportunity.expected_revenue)"
                        />
                        <DetailField
                            label="Lead source"
                            :value="display(opportunity.lead_source)"
                        />
                        <DetailField
                            label="Type"
                            :value="display(opportunity.type)"
                        />
                        <DetailField
                            label="Next step"
                            :value="display(opportunity.next_step)"
                        />
                        <DetailField
                            label="Owner"
                            :value="personName(opportunity.owner)"
                        />
                    </dl>
                </section>

                <section
                    v-if="opportunity.description"
                    class="crm-panel"
                >
                    <h2 class="text-h2">Description</h2>
                    <p class="mt-3 whitespace-pre-wrap text-body">
                        {{ opportunity.description }}
                    </p>
                </section>

                <section class="crm-panel">
                    <h2 class="text-h2">Stage history</h2>
                    <div
                        v-if="!opportunity.stage_histories?.length"
                        class="mt-3 text-body text-text-muted"
                    >
                        No stage history yet.
                    </div>
                    <ul v-else class="mt-3 divide-y divide-border">
                        <li
                            v-for="entry in opportunity.stage_histories"
                            :key="entry.id"
                            class="flex flex-wrap items-start justify-between gap-2 py-3 text-body"
                        >
                            <div>
                                <p>
                                    <span v-if="entry.from_stage">
                                        {{ entry.from_stage }} →
                                    </span>
                                    <span v-else>Opened as </span>
                                    {{ entry.to_stage }}
                                    <span class="text-text-muted">
                                        ({{ entry.probability }}%)
                                    </span>
                                </p>
                                <p class="text-small text-text-muted">
                                    {{ personName(entry.user) }}
                                </p>
                            </div>
                            <p class="text-small text-text-muted">
                                {{ formatWhen(entry.created_at) }}
                            </p>
                        </li>
                    </ul>
                </section>

                <section class="crm-panel">
                    <h2 class="text-h2">System information</h2>
                    <dl class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <DetailField
                            label="Created by"
                            :value="personName(opportunity.created_by)"
                        />
                        <DetailField
                            label="Created at"
                            :value="formatWhen(opportunity.created_at)"
                        />
                        <DetailField
                            label="Updated by"
                            :value="personName(opportunity.updated_by)"
                        />
                        <DetailField
                            label="Updated at"
                            :value="formatWhen(opportunity.updated_at)"
                        />
                    </dl>
                </section>
            </div>
        </div>

        <Modal :show="confirmingArchive" max-width="md" @close="confirmingArchive = false">
            <div class="p-6">
                <h2 class="text-h2 text-text">Archive this opportunity?</h2>
                <p class="mt-2 text-body text-text-muted">
                    {{ opportunity.name }} will be hidden from the default list. The
                    record is kept and can be shown with the archived filter.
                </p>
                <div class="mt-6 flex flex-wrap gap-2">
                    <DangerButton
                        type="button"
                        class="min-h-11"
                        :disabled="archiveForm.processing"
                        @click="archive"
                    >
                        Archive
                    </DangerButton>
                    <SecondaryButton
                        type="button"
                        class="min-h-11"
                        @click="confirmingArchive = false"
                    >
                        Cancel
                    </SecondaryButton>
                </div>
            </div>
        </Modal>

        <Modal :show="showingOwner" max-width="md" @close="showingOwner = false">
            <div class="p-6">
                <h2 class="text-h2 text-text">Change owner</h2>
                <form class="mt-4 space-y-4" @submit.prevent="submitOwner">
                    <FormField label="New owner" required :error="ownerForm.errors.owner_id">
                        <SelectInput
                            id="owner_id"
                            v-model="ownerForm.owner_id"
                            :options="ownerOptions"
                            placeholder="Select an owner"
                        />
                    </FormField>
                    <label class="flex items-center gap-2 text-body">
                        <Checkbox v-model:checked="ownerForm.transfer_activities" />
                        Transfer open tasks and future events
                    </label>
                    <InputError :message="ownerForm.errors.transfer_activities" />
                    <div class="flex flex-wrap gap-2">
                        <PrimaryButton
                            type="submit"
                            class="min-h-11"
                            :disabled="ownerForm.processing"
                        >
                            Save owner
                        </PrimaryButton>
                        <SecondaryButton
                            type="button"
                            class="min-h-11"
                            @click="showingOwner = false"
                        >
                            Cancel
                        </SecondaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <Modal :show="showingClone" max-width="md" @close="showingClone = false">
            <div class="p-6">
                <h2 class="text-h2 text-text">Clone opportunity</h2>
                <p class="mt-2 text-body text-text-muted">
                    Creates a copy owned by you. Past close dates are set to today.
                </p>
                <form class="mt-4 space-y-4" @submit.prevent="submitClone">
                    <label class="flex items-center gap-2 text-body">
                        <Checkbox v-model:checked="cloneForm.include_related" />
                        Include related tasks, events, and notes
                    </label>
                    <InputError :message="cloneForm.errors.include_related" />
                    <div class="flex flex-wrap gap-2">
                        <PrimaryButton
                            type="submit"
                            class="min-h-11"
                            :disabled="cloneForm.processing"
                        >
                            Clone
                        </PrimaryButton>
                        <SecondaryButton
                            type="button"
                            class="min-h-11"
                            @click="showingClone = false"
                        >
                            Cancel
                        </SecondaryButton>
                    </div>
                </form>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
