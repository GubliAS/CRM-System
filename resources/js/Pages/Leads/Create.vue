<script setup>
import LeadForm from '@/Components/LeadForm.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { leadFormData } from '@/forms/lead';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({
    salutations: { type: Array, required: true },
    statuses: { type: Array, required: true },
    sources: { type: Array, required: true },
    ratings: { type: Array, required: true },
});

const form = useForm(leadFormData());

function submit(saveAndNew) {
    form.transform((data) => {
        const payload = {
            ...data,
            save_and_new: saveAndNew,
        };

        delete payload.owner_id;

        return payload;
    }).post(route('leads.store'));
}
</script>

<template>
    <AuthenticatedLayout>
        <Head title="New lead" />

        <div class="mx-auto max-w-7xl px-4 py-6">
            <h1 class="text-h1">New lead</h1>
            <div class="mt-4 rounded-md border border-border bg-surface p-4">
                <LeadForm
                    :form="form"
                    :salutations="salutations"
                    :statuses="statuses"
                    :sources="sources"
                    :ratings="ratings"
                    :cancel-href="route('leads.index')"
                    @submit="submit"
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
