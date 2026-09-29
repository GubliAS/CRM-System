<script setup>
import OpportunityForm from '@/Components/OpportunityForm.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { opportunityFormData } from '@/forms/opportunity';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
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
});

const form = useForm(opportunityFormData(null, props.stages[0]?.name ?? ''));

function submit(saveAndNew) {
    form.transform((data) => ({
        ...data,
        save_and_new: saveAndNew,
    })).post(route('opportunities.store'));
}
</script>

<template>
    <AuthenticatedLayout>
        <Head title="New opportunity" />

        <div class="crm-page">
            <h1 class="text-h1">New opportunity</h1>
            <div class="mt-4 crm-panel">
                <OpportunityForm
                    :form="form"
                    :accounts="accounts"
                    :stages="stages"
                    :types="types"
                    :lead-sources="leadSources"
                    :cancel-href="route('opportunities.index')"
                    @submit="submit"
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
