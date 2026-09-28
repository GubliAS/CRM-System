<script setup>
import AccountForm from '@/Components/AccountForm.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { accountFormData } from '@/forms/account';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({
    parentAccounts: {
        type: Array,
        required: true,
    },
    types: {
        type: Array,
        required: true,
    },
});

const form = useForm(accountFormData());

function submit(saveAndNew) {
    form.transform((data) => {
        const payload = {
            ...data,
            save_and_new: saveAndNew,
        };

        delete payload.owner_id;

        return payload;
    }).post(route('accounts.store'));
}
</script>

<template>
    <AuthenticatedLayout>
        <Head title="New account" />

        <div class="mx-auto max-w-7xl px-4 py-6">
            <h1 class="text-h1">New account</h1>
            <div class="mt-4 rounded-md border border-border bg-surface p-4">
                <AccountForm
                    :form="form"
                    :parent-accounts="parentAccounts"
                    :types="types"
                    :cancel-href="route('accounts.index')"
                    @submit="submit"
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
