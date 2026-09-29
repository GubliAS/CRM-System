<script setup>
import OpportunityForm from '@/Components/OpportunityForm.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { opportunityFormData } from '@/forms/opportunity';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({
    accounts: { type: Array, required: true },
    stages: { type: Array, required: true },
    types: { type: Array, required: true },
    sources: { type: Array, required: true },
});

const form = useForm(opportunityFormData());

function submit(saveAndNew) {
    form.transform((data) => {
        const payload = {
            ...data,
            save_and_new: saveAndNew,
            amount: data.amount === '' ? null : data.amount,
            type: data.type || null,
            lead_source: data.lead_source || null,
        };

        delete payload.owner_id;

        return payload;
    }).post(route('opportunities.store'));
}
</script>

<template>
    <AuthenticatedLayout>
        <Head title="New opportunity" />

        <div class="mx-auto max-w-7xl px-4 py-6">
            <h1 class="text-h1">New opportunity</h1>
            <div class="mt-4 rounded-md border border-border bg-surface p-4">
                <OpportunityForm
                    :form="form"
                    :accounts="accounts"
                    :stages="stages"
                    :types="types"
                    :sources="sources"
                    :cancel-href="route('opportunities.index')"
                    @submit="submit"
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
