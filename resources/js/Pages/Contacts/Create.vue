<script setup>
import ContactForm from '@/Components/ContactForm.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { contactFormData } from '@/forms/contact';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    accounts: {
        type: Array,
        required: true,
    },
    reportsTo: {
        type: Array,
        required: true,
    },
    salutations: {
        type: Array,
        required: true,
    },
    selectedAccountId: {
        default: null,
    },
});

const form = useForm(contactFormData(null, props.selectedAccountId));

function submit(saveAndNew) {
    form.transform((data) => {
        const payload = {
            ...data,
            save_and_new: saveAndNew,
        };

        delete payload.owner_id;

        return payload;
    }).post(route('contacts.store'));
}
</script>

<template>
    <AuthenticatedLayout>
        <Head title="New contact" />

        <div class="mx-auto max-w-7xl px-4 py-6">
            <h1 class="text-h1">New contact</h1>
            <div class="mt-4 rounded-md border border-border bg-surface p-4">
                <ContactForm
                    :form="form"
                    :accounts="accounts"
                    :reports-to="reportsTo"
                    :salutations="salutations"
                    :cancel-href="route('contacts.index')"
                    @submit="submit"
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
