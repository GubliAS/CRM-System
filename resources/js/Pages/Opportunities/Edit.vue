<script setup>
import OpportunityForm from '@/Components/OpportunityForm.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { opportunityFormData } from '@/forms/opportunity';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    opportunity: {
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
});

const form = useForm(opportunityFormData(props.opportunity));

function submit(saveAndNew) {
    form.transform((data) => ({
        ...data,
        save_and_new: saveAndNew,
    })).put(route('opportunities.update', props.opportunity.id));
}
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Edit opportunity" />

        <div class="mx-auto max-w-7xl px-4 py-6">
            <h1 class="break-words text-h1">Edit {{ opportunity.name }}</h1>
            <div class="mt-4 rounded-md border border-border bg-surface p-4">
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
