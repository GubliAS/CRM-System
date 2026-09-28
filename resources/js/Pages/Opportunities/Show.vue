<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import DangerButton from '@/Components/DangerButton.vue';
import DetailField from '@/Components/DetailField.vue';
import FormField from '@/Components/FormField.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import SelectInput from '@/Components/SelectInput.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { display, formatDay, formatMoney, formatWhen, personName } from '@/display';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    opportunity: {
        type: Object,
        required: true,
    },
    stagePath: {
        type: Array,
        required: true,
    },
    closeDateInPast: {
        type: Boolean,
        required: true,
    },
    owners: {
        type: Array,
        default: () => [],
    },
    can: {
        type: Object,
        required: true,
    },
});

const confirmingArchive = ref(false);
const changingOwner = ref(false);
const cloning = ref(false);

const archiveForm = useForm({});
const ownerForm = useForm({
    owner_id: props.opportunity.owner_id ? String(props.opportunity.owner_id) : '',
});
const cloneForm = useForm({
    include_related: false,
    close_date: '',
});

const ownerOptions = computed(() =>
    props.owners.map((owner) => ({
        value: String(owner.id),
        label: owner.name,
    })),
);

const stepClasses = {
    danger: 'border-border bg-danger text-surface',
    primary: 'border-border bg-primary text-surface',
    success: 'border-border bg-bg text-success',
    muted: 'border-border bg-surface text-text-muted',
};

const stateLabels = {
    completed: 'Completed',
    current: 'Current',
    future: 'Upcoming',
};

function stageClass(stage) {
    if (stage === 'Closed Won') {
        return 'text-success';
    }

    if (stage === 'Closed Lost') {
        return 'text-danger';
    }

    return 'text-secondary';
}

function archive() {
    archiveForm.delete(route('opportunities.destroy', props.opportunity.id), {
        onSuccess: () => {
            confirmingArchive.value = false;
        },
    });
}

function submitOwner() {
    ownerForm.patch(route('opportunities.owner', props.opportunity.id), {
        onSuccess: () => {
            changingOwner.value = false;
        },
    });
}

function submitClone() {
    cloneForm
        .transform((data) => {
            const payload = {
                include_related: data.include_related,
            };

            if (props.closeDateInPast) {
                payload.close_date = data.close_date;
            }

            return payload;
        })
        .post(route('opportunities.clone', props.opportunity.id), {
            onSuccess: () => {
                cloning.value = false;
            },
        });
}
</script>

<template>
    <AuthenticatedLayout>
        <Head :title="opportunity.name" />

        <div class="mx-auto max-w-7xl px-4 py-6">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <h1 class="break-words text-h1">{{ opportunity.name }}</h1>
                <div class="flex flex-wrap gap-2">
                    <Link
                        v-if="can.update"
                        :href="route('opportunities.edit', opportunity.id)"
                        class="inline-flex min-h-11 items-center rounded-md border border-border bg-surface px-4 py-2 text-small font-semibold uppercase tracking-widest text-text"
                    >
                        Edit
                    </Link>
                    <DangerButton
                        v-if="can.delete && !opportunity.archived_at"
                        type="button"
                        class="min-h-11"
                        @click="confirmingArchive = true"
                    >
                        Archive
                    </DangerButton>
                    <SecondaryButton v-if="can.update" type="button" class="min-h-11" @click="changingOwner = true">
                        Change owner
                    </SecondaryButton>
                    <SecondaryButton v-if="can.clone" type="button" class="min-h-11" @click="cloning = true">
                        Clone
                    </SecondaryButton>
                </div>
            </div>

            <p
                v-if="opportunity.archived_at"
                class="mt-4 rounded-md border border-border bg-bg px-3 py-2 text-body text-text"
            >
                This opportunity was archived {{ formatWhen(opportunity.archived_at) }}.
            </p>

            <ol class="mt-6 flex flex-col gap-2 md:flex-row">
                <li
                    v-for="step in stagePath"
                    :key="step.name"
                    class="min-w-0 flex-1 rounded-md border px-3 py-2"
                    :class="stepClasses[step.emphasis]"
                    :aria-current="step.state === 'current' ? 'step' : undefined"
                >
                    <span class="block text-small">{{ stateLabels[step.state] }}</span>
                    <span class="block text-body">{{ step.name }}</span>
                </li>
            </ol>

            <div class="mt-6 space-y-4">
                <section class="rounded-md border border-border bg-surface p-4">
                    <h2 class="text-h2">Key metrics</h2>
                    <dl class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3">
                        <DetailField label="Amount" :value="formatMoney(opportunity.amount)" />
                        <DetailField label="Probability" :value="`${opportunity.probability}%`" />
                        <DetailField label="Expected revenue" :value="formatMoney(opportunity.expected_revenue)" />
                    </dl>
                </section>

                <section class="rounded-md border border-border bg-surface p-4">
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
                        <DetailField label="Close date" :value="formatDay(opportunity.close_date)" />
                        <DetailField label="Stage">
                            <span :class="stageClass(opportunity.stage)">{{ opportunity.stage }}</span>
                        </DetailField>
                        <DetailField label="Type" :value="opportunity.type" />
                        <DetailField label="Lead source" :value="opportunity.lead_source" />
                        <DetailField label="Next step" :value="opportunity.next_step" />
                        <DetailField label="Owner" :value="opportunity.owner?.name" />
                        <DetailField label="Description" :value="opportunity.description" />
                    </dl>
                </section>

                <section class="rounded-md border border-border bg-surface p-4">
                    <h2 class="text-h2">System information</h2>
                    <dl class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <DetailField label="Created by" :value="personName(opportunity.created_by)" />
                        <DetailField label="Created at" :value="formatWhen(opportunity.created_at)" />
                        <DetailField label="Updated by" :value="personName(opportunity.updated_by)" />
                        <DetailField label="Updated at" :value="formatWhen(opportunity.updated_at)" />
                    </dl>
                </section>

                <section class="rounded-md border border-border bg-surface p-4">
                    <h2 class="text-h2">Stage history</h2>
                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full text-left text-body">
                            <thead class="text-small text-text-muted">
                                <tr>
                                    <th scope="col" class="px-3 py-2">From</th>
                                    <th scope="col" class="px-3 py-2">To</th>
                                    <th scope="col" class="px-3 py-2">Probability</th>
                                    <th scope="col" class="px-3 py-2">When</th>
                                    <th scope="col" class="px-3 py-2">Who</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="opportunity.stage_histories.length === 0">
                                    <td colspan="5" class="px-3 py-6 text-body text-text-muted">No stage history yet.</td>
                                </tr>
                                <tr
                                    v-for="entry in opportunity.stage_histories"
                                    :key="entry.id"
                                    class="border-t border-border"
                                >
                                    <td class="px-3 py-2">{{ display(entry.from_stage) }}</td>
                                    <td class="px-3 py-2">{{ entry.to_stage }}</td>
                                    <td class="px-3 py-2">{{ entry.probability }}%</td>
                                    <td class="px-3 py-2">{{ formatWhen(entry.created_at) }}</td>
                                    <td class="px-3 py-2">{{ personName(entry.user) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>

        <Modal :show="confirmingArchive" max-width="md" @close="confirmingArchive = false">
            <div class="p-6">
                <h2 class="text-h2 text-text">Archive this opportunity?</h2>
                <p class="mt-2 text-body text-text-muted">
                    {{ opportunity.name }} will be hidden from the default list. The record is kept.
                </p>
                <div class="mt-6 flex flex-wrap gap-2">
                    <DangerButton type="button" class="min-h-11" :disabled="archiveForm.processing" @click="archive">
                        Archive
                    </DangerButton>
                    <SecondaryButton type="button" class="min-h-11" @click="confirmingArchive = false">
                        Cancel
                    </SecondaryButton>
                </div>
            </div>
        </Modal>

        <Modal :show="changingOwner" max-width="md" @close="changingOwner = false">
            <form class="p-6" @submit.prevent="submitOwner">
                <h2 class="text-h2 text-text">Change owner</h2>
                <div class="mt-4">
                    <FormField label="Owner" required :error="ownerForm.errors.owner_id">
                        <SelectInput
                            id="owner_id"
                            v-model="ownerForm.owner_id"
                            :options="ownerOptions"
                            placeholder="Select an owner"
                            required
                        />
                    </FormField>
                </div>
                <div class="mt-6 flex flex-wrap gap-2">
                    <PrimaryButton class="min-h-11" :disabled="ownerForm.processing">Save</PrimaryButton>
                    <SecondaryButton type="button" class="min-h-11" @click="changingOwner = false">
                        Cancel
                    </SecondaryButton>
                </div>
            </form>
        </Modal>

        <Modal :show="cloning" max-width="md" @close="cloning = false">
            <form class="p-6" @submit.prevent="submitClone">
                <h2 class="text-h2 text-text">Clone opportunity</h2>
                <p class="mt-2 text-body text-text-muted">
                    The copy is owned by you and is not archived.
                </p>
                <label class="mt-4 flex min-h-11 items-center gap-2 text-body text-text">
                    <Checkbox v-model:checked="cloneForm.include_related" />
                    Include related records
                </label>
                <div v-if="closeDateInPast" class="mt-4">
                    <FormField label="Close date" required :error="cloneForm.errors.close_date">
                        <TextInput
                            id="clone_close_date"
                            v-model="cloneForm.close_date"
                            type="date"
                            class="block w-full"
                            required
                        />
                    </FormField>
                </div>
                <div class="mt-6 flex flex-wrap gap-2">
                    <PrimaryButton class="min-h-11" :disabled="cloneForm.processing">Clone</PrimaryButton>
                    <SecondaryButton type="button" class="min-h-11" @click="cloning = false">Cancel</SecondaryButton>
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>
