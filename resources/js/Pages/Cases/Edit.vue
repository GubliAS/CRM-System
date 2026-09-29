<script setup>
import CaseForm from '@/Components/CaseForm.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { caseFormData } from '@/forms/case';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    caseRecord: { type: Object, required: true },
    statuses: { type: Array, required: true },
    origins: { type: Array, required: true },
    priorities: { type: Array, required: true },
    types: { type: Array, required: true },
    reasons: { type: Array, required: true },
    accounts: { type: Array, required: true },
    contacts: { type: Array, required: true },
    owners: { type: Array, default: () => [] },
    canReassign: { type: Boolean, default: false },
});

const form = useForm(caseFormData(props.caseRecord));

function submit(saveAndNew) {
    form.transform((data) => {
        const payload = {
            ...data,
            save_and_new: saveAndNew,
        };

        if (!props.canReassign) {
            delete payload.owner_id;
        }

        return payload;
    }).put(route('cases.update', props.caseRecord.id));
}
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Edit case" />

        <div class="crm-page">
            <h1 class="break-words text-h1">Edit case</h1>
            <div class="mt-4 crm-panel">
                <CaseForm
                    :form="form"
                    :statuses="statuses"
                    :origins="origins"
                    :priorities="priorities"
                    :types="types"
                    :reasons="reasons"
                    :accounts="accounts"
                    :contacts="contacts"
                    :owners="owners"
                    :can-reassign="canReassign"
                    :cancel-href="route('cases.show', caseRecord.id)"
                    @submit="submit"
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
