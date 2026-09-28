<script setup>
import AccountForm from '@/Components/AccountForm.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { accountFormData } from '@/forms/account';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    account: {
        type: Object,
        required: true,
    },
    parentAccounts: {
        type: Array,
        required: true,
    },
    types: {
        type: Array,
        required: true,
    },
    owners: {
        type: Array,
        default: () => [],
    },
    canReassign: {
        type: Boolean,
        default: false,
    },
});

const form = useForm(accountFormData(props.account));

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
    }).put(route('accounts.update', props.account.id));
}
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Edit account" />

        <div class="mx-auto max-w-7xl px-4 py-6">
            <h1 class="break-words text-h1">Edit {{ account.name }}</h1>
            <div class="mt-4 rounded-md border border-border bg-surface p-4">
                <AccountForm
                    :form="form"
                    :parent-accounts="parentAccounts"
                    :types="types"
                    :owners="owners"
                    :can-reassign="canReassign"
                    :cancel-href="route('accounts.index')"
                    @submit="submit"
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
