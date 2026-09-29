<script setup>
import OpportunityForm from '@/Components/OpportunityForm.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { opportunityFormData } from '@/forms/opportunity';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    opportunity: { type: Object, required: true },
    accounts: { type: Array, required: true },
    stages: { type: Array, required: true },
    types: { type: Array, required: true },
    sources: { type: Array, required: true },
    owners: { type: Array, default: () => [] },
    canReassign: { type: Boolean, default: false },
});

const form = useForm(opportunityFormData(props.opportunity));

function submit(saveAndNew) {
    form.transform((data) => {
        const payload = {
            ...data,
            save_and_new: saveAndNew,
            amount: data.amount === '' ? null : data.amount,
            type: data.type || null,
            lead_source: data.lead_source || null,
        };

        if (!props.canReassign) {
            delete payload.owner_id;
        }

        return payload;
    }).put(route('opportunities.update', props.opportunity.id));
}
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Edit opportunity" />

        <div class="crm-page">
            <h1 class="break-words text-h1">Edit {{ opportunity.name }}</h1>
            <div class="mt-4 crm-panel">
                <OpportunityForm
                    :form="form"
                    :accounts="accounts"
                    :stages="stages"
                    :types="types"
                    :sources="sources"
                    :owners="owners"
                    :can-reassign="canReassign"
                    :cancel-href="route('opportunities.show', opportunity.id)"
                    @submit="submit"
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
