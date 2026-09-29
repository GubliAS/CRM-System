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
    industries: {
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

        <div class="crm-page">
            <h1 class="text-h1">New account</h1>
            <div class="mt-4 crm-panel">
                <AccountForm
                    :form="form"
                    :parent-accounts="parentAccounts"
                    :types="types"
                    :industries="industries"
                    :cancel-href="route('accounts.index')"
                    @submit="submit"
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
