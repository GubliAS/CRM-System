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
import { display, formatWhen, fullName, personName } from '@/display';
import { priorityClass } from '@/forms/case';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    caseRecord: { type: Object, required: true },
    statuses: { type: Array, required: true },
    owners: { type: Array, default: () => [] },
    can: { type: Object, required: true },
});

const page = usePage();
const confirmingDelete = ref(false);
const confirmingClose = ref(false);
const showingOwner = ref(false);
const showingStatus = ref(false);

const deleteForm = useForm({});
const closeForm = useForm({});
const reopenForm = useForm({});
const statusForm = useForm({
    status: props.caseRecord.status === 'Closed' ? 'Working' : props.caseRecord.status,
});
const ownerForm = useForm({
    owner_id: props.caseRecord.owner_id ? String(props.caseRecord.owner_id) : '',
    transfer_activities: false,
});

watch(
    () => props.caseRecord.status,
    (value) => {
        if (value !== 'Closed') {
            statusForm.status = value;
        }
    },
);

const title = computed(() => props.caseRecord.case_number || 'Case');

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

function destroy() {
    deleteForm.delete(route('cases.destroy', props.caseRecord.id), {
        preserveScroll: true,
    });
}

function submitStatus() {
    statusForm.post(route('cases.status', props.caseRecord.id), {
        preserveScroll: true,
        onSuccess: () => {
            showingStatus.value = false;
        },
    });
}

function submitOwner() {
    ownerForm
        .transform((data) => ({
            ...data,
            transfer_activities: Boolean(data.transfer_activities),
        }))
        .post(route('cases.owner', props.caseRecord.id), {
            preserveScroll: true,
            onSuccess: () => {
                showingOwner.value = false;
            },
        });
}

function closeCase() {
    closeForm.post(route('cases.close', props.caseRecord.id), {
        preserveScroll: true,
        onSuccess: () => {
            confirmingClose.value = false;
        },
    });
}

function reopenCase() {
    reopenForm.post(route('cases.reopen', props.caseRecord.id), {
        preserveScroll: true,
    });
}
</script>

<template>
    <AuthenticatedLayout>
        <Head :title="title" />

        <div class="mx-auto max-w-7xl px-4 py-6">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h1 class="break-words text-h1">{{ title }}</h1>
                    <p class="mt-1 text-body text-text-muted">
                        {{ display(caseRecord.subject) }} · {{ display(caseRecord.status) }}
                        <span v-if="caseRecord.is_closed" class="text-warning"> · Closed (read-only)</span>
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
                        v-if="can.close"
                        type="button"
                        class="min-h-11"
                        @click="confirmingClose = true"
                    >
                        Close case
                    </PrimaryButton>
                    <PrimaryButton
                        v-if="can.reopen"
                        type="button"
                        class="min-h-11"
                        :disabled="reopenForm.processing"
                        @click="reopenCase"
                    >
                        Reopen
                    </PrimaryButton>
                    <Link
                        v-if="can.update"
                        :href="route('cases.edit', caseRecord.id)"
                        class="inline-flex min-h-11 items-center rounded-md border border-border bg-surface px-4 py-2 text-small font-semibold uppercase tracking-widest text-text"
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
                <section class="rounded-md border border-border bg-surface p-4">
                    <h2 class="text-h2">Case details</h2>
                    <dl class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <DetailField label="Case number" :value="caseRecord.case_number" />
                        <DetailField label="Subject" :value="caseRecord.subject" />
                        <DetailField label="Status" :value="caseRecord.status" />
                        <DetailField label="Priority">
                            <span :class="priorityClass(caseRecord.priority)">
                                {{ display(caseRecord.priority) }}
                            </span>
                        </DetailField>
                        <DetailField label="Origin" :value="caseRecord.origin" />
                        <DetailField label="Type" :value="caseRecord.type" />
                        <DetailField label="Reason" :value="caseRecord.reason" />
                        <DetailField label="Owner" :value="caseRecord.owner?.name" />
                        <DetailField label="Account">
                            <Link
                                v-if="caseRecord.account"
                                :href="route('accounts.show', caseRecord.account.id)"
                                class="text-secondary underline"
                            >
                                {{ caseRecord.account.name }}
                            </Link>
                            <span v-else>—</span>
                        </DetailField>
                        <DetailField label="Contact">
                            <Link
                                v-if="caseRecord.contact"
                                :href="route('contacts.show', caseRecord.contact.id)"
                                class="text-secondary underline"
                            >
                                {{ fullName(caseRecord.contact) }}
                            </Link>
                            <span v-else>—</span>
                        </DetailField>
                        <DetailField label="Description" :value="caseRecord.description" />
                        <DetailField label="Internal comments" :value="caseRecord.internal_comments" />
                    </dl>
                </section>

                <section class="rounded-md border border-border bg-surface p-4">
                    <h2 class="text-h2">Web information</h2>
                    <dl class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <DetailField label="Web name" :value="caseRecord.web_name" />
                        <DetailField label="Web email" :value="caseRecord.web_email" />
                        <DetailField label="Web company" :value="caseRecord.web_company" />
                        <DetailField label="Web phone" :value="caseRecord.web_phone" />
                    </dl>
                </section>

                <section class="rounded-md border border-border bg-surface p-4">
                    <h2 class="text-h2">System information</h2>
                    <dl class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <DetailField label="Opened" :value="formatWhen(caseRecord.created_at)" />
                        <DetailField label="Closed at" :value="formatWhen(caseRecord.closed_at)" />
                        <DetailField label="Created by" :value="personName(caseRecord.created_by)" />
                        <DetailField label="Updated by" :value="personName(caseRecord.updated_by)" />
                        <DetailField label="Updated at" :value="formatWhen(caseRecord.updated_at)" />
                    </dl>
                </section>
            </div>
        </div>

        <Modal :show="confirmingDelete" max-width="md" @close="confirmingDelete = false">
            <div class="p-6">
                <h2 class="text-h2 text-text">Delete this case?</h2>
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

        <Modal :show="confirmingClose" max-width="md" @close="confirmingClose = false">
            <div class="p-6">
                <h2 class="text-h2 text-text">Close this case?</h2>
                <p class="mt-2 text-body text-text-muted">
                    Closed cases become read-only until reopened.
                </p>
                <div class="mt-6 flex flex-wrap gap-2">
                    <PrimaryButton
                        type="button"
                        class="min-h-11"
                        :disabled="closeForm.processing"
                        @click="closeCase"
                    >
                        Close case
                    </PrimaryButton>
                    <SecondaryButton type="button" class="min-h-11" @click="confirmingClose = false">
                        Cancel
                    </SecondaryButton>
                </div>
            </div>
        </Modal>

        <Modal :show="showingStatus" max-width="md" @close="showingStatus = false">
            <form class="space-y-4 p-6" @submit.prevent="submitStatus">
                <h2 class="text-h2 text-text">Change status</h2>
                <FormField label="Status" required :error="statusForm.errors.status">
                    <SelectInput
                        id="change_case_status"
                        v-model="statusForm.status"
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
                        id="change_case_owner_id"
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
    </AuthenticatedLayout>
</template>
