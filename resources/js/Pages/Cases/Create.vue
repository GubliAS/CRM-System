<script setup>
import CaseForm from '@/Components/CaseForm.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { caseFormData } from '@/forms/case';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({
    statuses: { type: Array, required: true },
    origins: { type: Array, required: true },
    priorities: { type: Array, required: true },
    types: { type: Array, required: true },
    reasons: { type: Array, required: true },
    accounts: { type: Array, required: true },
    contacts: { type: Array, required: true },
});

const form = useForm(caseFormData());

function submit(saveAndNew) {
    form.transform((data) => {
        const payload = {
            ...data,
            save_and_new: saveAndNew,
        };

        delete payload.owner_id;

        return payload;
    }).post(route('cases.store'));
}
</script>

<template>
    <AuthenticatedLayout>
        <Head title="New case" />

        <div class="mx-auto max-w-7xl px-4 py-6">
            <h1 class="text-h1">New case</h1>
            <div class="mt-4 rounded-md border border-border bg-surface p-4">
                <CaseForm
                    :form="form"
                    :statuses="statuses"
                    :origins="origins"
                    :priorities="priorities"
                    :types="types"
                    :reasons="reasons"
                    :accounts="accounts"
                    :contacts="contacts"
                    :cancel-href="route('cases.index')"
                    @submit="submit"
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
