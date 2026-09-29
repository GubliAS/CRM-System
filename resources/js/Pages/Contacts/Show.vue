<script setup>
import DangerButton from '@/Components/DangerButton.vue';
import DetailField from '@/Components/DetailField.vue';
import InputError from '@/Components/InputError.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { formatDay, formatWhen, fullName, personName } from '@/display';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    contact: {
        type: Object,
        required: true,
    },
    can: {
        type: Object,
        required: true,
    },
});

const page = usePage();
const confirming = ref(false);
const form = useForm({});

const title = computed(() => fullName(props.contact));

const deleteError = computed(() => {
    const value = form.errors.delete || page.props.errors?.delete || '';

    return Array.isArray(value) ? value[0] : value;
});

function destroy() {
    form.delete(route('contacts.destroy', props.contact.id), {
        preserveScroll: true,
    });
}
</script>

<template>
    <AuthenticatedLayout>
        <Head :title="title" />

        <div class="crm-page">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <h1 class="break-words text-h1">{{ title }}</h1>
                <div class="flex flex-wrap gap-2">
                    <Link
                        v-if="can.update"
                        :href="route('contacts.edit', contact.id)"
                        class="crm-btn-secondary"
                    >
                        Edit
                    </Link>
                    <DangerButton v-if="can.delete" type="button" class="min-h-11" @click="confirming = true">
                        Delete
                    </DangerButton>
                </div>
            </div>

            <InputError class="mt-3" :message="deleteError" />

            <div class="mt-6 space-y-4">
                <section class="crm-panel">
                    <h2 class="text-h2">Contact details</h2>
                    <dl class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <DetailField label="Salutation" :value="contact.salutation" />
                        <DetailField label="First name" :value="contact.first_name" />
                        <DetailField label="Last name" :value="contact.last_name" />
                        <DetailField label="Account">
                            <Link
                                v-if="contact.account"
                                :href="route('accounts.show', contact.account.id)"
                                class="text-secondary underline"
                            >
                                {{ contact.account.name }}
                            </Link>
                            <span v-else>—</span>
                        </DetailField>
                        <DetailField label="Title" :value="contact.title" />
                        <DetailField label="Department" :value="contact.department" />
                        <DetailField label="Reports to">
                            <Link
                                v-if="contact.reports_to"
                                :href="route('contacts.show', contact.reports_to.id)"
                                class="text-secondary underline"
                            >
                                {{ fullName(contact.reports_to) }}
                            </Link>
                            <span v-else>—</span>
                        </DetailField>
                        <DetailField label="Owner" :value="contact.owner?.name" />
                    </dl>
                </section>

                <section class="crm-panel">
                    <h2 class="text-h2">Phone and email</h2>
                    <dl class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <DetailField label="Phone" :value="contact.phone" />
                        <DetailField label="Mobile" :value="contact.mobile" />
                        <DetailField label="Home phone" :value="contact.home_phone" />
                        <DetailField label="Other phone" :value="contact.other_phone" />
                        <DetailField label="Email" :value="contact.email" />
                        <DetailField label="Fax" :value="contact.fax" />
                    </dl>
                </section>

                <section class="crm-panel">
                    <h2 class="text-h2">Address</h2>
                    <dl class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <DetailField label="Mailing street" :value="contact.mailing_street" />
                        <DetailField label="Other street" :value="contact.other_street" />
                        <DetailField label="Mailing city" :value="contact.mailing_city" />
                        <DetailField label="Other city" :value="contact.other_city" />
                        <DetailField label="Mailing state" :value="contact.mailing_state" />
                        <DetailField label="Other state" :value="contact.other_state" />
                        <DetailField label="Mailing postal code" :value="contact.mailing_postal_code" />
                        <DetailField label="Other postal code" :value="contact.other_postal_code" />
                        <DetailField label="Mailing country" :value="contact.mailing_country" />
                        <DetailField label="Other country" :value="contact.other_country" />
                    </dl>
                </section>

                <section class="crm-panel">
                    <h2 class="text-h2">Additional information</h2>
                    <dl class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <DetailField label="Assistant" :value="contact.assistant" />
                        <DetailField label="Assistant phone" :value="contact.assistant_phone" />
                        <DetailField label="Lead source" :value="contact.lead_source" />
                        <DetailField label="Birthdate" :value="formatDay(contact.birthdate)" />
                        <DetailField label="Description" :value="contact.description" />
                    </dl>
                </section>

                <section class="crm-panel">
                    <h2 class="text-h2">System information</h2>
                    <dl class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <DetailField label="Created by" :value="personName(contact.created_by)" />
                        <DetailField label="Created at" :value="formatWhen(contact.created_at)" />
                        <DetailField label="Updated by" :value="personName(contact.updated_by)" />
                        <DetailField label="Updated at" :value="formatWhen(contact.updated_at)" />
                    </dl>
                </section>
            </div>
        </div>

        <Modal :show="confirming" max-width="md" @close="confirming = false">
            <div class="p-6">
                <h2 class="text-h2 text-text">Delete this contact?</h2>
                <p class="mt-2 text-body text-text-muted">
                    {{ title }} will be removed. This cannot be undone.
                </p>
                <div class="mt-6 flex flex-wrap gap-2">
                    <DangerButton type="button" class="min-h-11" :disabled="form.processing" @click="destroy">
                        Delete
                    </DangerButton>
                    <SecondaryButton type="button" class="min-h-11" @click="confirming = false">
                        Cancel
                    </SecondaryButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
