<script setup>
import ContactForm from '@/Components/ContactForm.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { contactFormData } from '@/forms/contact';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    contact: {
        type: Object,
        required: true,
    },
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
    owners: {
        type: Array,
        default: () => [],
    },
    canReassign: {
        type: Boolean,
        default: false,
    },
});

const form = useForm(contactFormData(props.contact));

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
    }).put(route('contacts.update', props.contact.id));
}
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Edit contact" />

        <div class="crm-page">
            <h1 class="break-words text-h1">Edit {{ contact.first_name }} {{ contact.last_name }}</h1>
            <div class="mt-4 crm-panel">
                <ContactForm
                    :form="form"
                    :accounts="accounts"
                    :reports-to="reportsTo"
                    :salutations="salutations"
                    :owners="owners"
                    :can-reassign="canReassign"
                    :cancel-href="route('contacts.index')"
                    @submit="submit"
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
