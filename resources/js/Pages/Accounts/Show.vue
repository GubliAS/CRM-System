<script setup>
import DangerButton from '@/Components/DangerButton.vue';
import DetailField from '@/Components/DetailField.vue';
import InputError from '@/Components/InputError.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { display, formatMoney, formatWhen, fullName, personName, websiteHref } from '@/display';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    account: {
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

const deleteError = computed(() => {
    const value = form.errors.delete || page.props.errors?.delete || '';

    return Array.isArray(value) ? value[0] : value;
});

const siteHref = computed(() => websiteHref(props.account.website));

function destroy() {
    form.delete(route('accounts.destroy', props.account.id), {
        preserveScroll: true,
    });
}
</script>

<template>
    <AuthenticatedLayout>
        <Head :title="account.name" />

        <div class="mx-auto max-w-7xl px-4 py-6">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <h1 class="break-words text-h1">{{ account.name }}</h1>
                <div class="flex flex-wrap gap-2">
                    <Link
                        v-if="can.update"
                        :href="route('accounts.edit', account.id)"
                        class="inline-flex min-h-11 items-center rounded-md border border-border bg-surface px-4 py-2 text-small font-semibold uppercase tracking-widest text-text"
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
                <section class="rounded-md border border-border bg-surface p-4">
                    <h2 class="text-h2">Account details</h2>
                    <dl class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <DetailField label="Account name" :value="account.name" />
                        <DetailField label="Parent account">
                            <Link
                                v-if="account.parent"
                                :href="route('accounts.show', account.parent.id)"
                                class="text-secondary underline"
                            >
                                {{ account.parent.name }}
                            </Link>
                            <span v-else>—</span>
                        </DetailField>
                        <DetailField label="Phone" :value="account.phone" />
                        <DetailField label="Fax" :value="account.fax" />
                        <DetailField label="Website">
                            <a
                                v-if="siteHref"
                                :href="siteHref"
                                class="text-secondary underline"
                            >
                                {{ account.website }}
                            </a>
                            <span v-else>{{ display(account.website) }}</span>
                        </DetailField>
                        <DetailField label="Type" :value="account.type" />
                        <DetailField label="Industry" :value="account.industry" />
                        <DetailField label="Owner" :value="account.owner?.name" />
                    </dl>
                </section>

                <section class="rounded-md border border-border bg-surface p-4">
                    <h2 class="text-h2">Address</h2>
                    <dl class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <DetailField label="Billing street" :value="account.billing_street" />
                        <DetailField label="Shipping street" :value="account.shipping_street" />
                        <DetailField label="Billing city" :value="account.billing_city" />
                        <DetailField label="Shipping city" :value="account.shipping_city" />
                        <DetailField label="Billing state" :value="account.billing_state" />
                        <DetailField label="Shipping state" :value="account.shipping_state" />
                        <DetailField label="Billing postal code" :value="account.billing_postal_code" />
                        <DetailField label="Shipping postal code" :value="account.shipping_postal_code" />
                        <DetailField label="Billing country" :value="account.billing_country" />
                        <DetailField label="Shipping country" :value="account.shipping_country" />
                    </dl>
                </section>

                <section class="rounded-md border border-border bg-surface p-4">
                    <h2 class="text-h2">Additional information</h2>
                    <dl class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <DetailField label="Employees" :value="account.employees" />
                        <DetailField label="Annual revenue" :value="formatMoney(account.annual_revenue)" />
                        <DetailField label="Description" :value="account.description" />
                    </dl>
                </section>

                <section class="rounded-md border border-border bg-surface p-4">
                    <h2 class="text-h2">System information</h2>
                    <dl class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <DetailField label="Created by" :value="personName(account.created_by)" />
                        <DetailField label="Created at" :value="formatWhen(account.created_at)" />
                        <DetailField label="Updated by" :value="personName(account.updated_by)" />
                        <DetailField label="Updated at" :value="formatWhen(account.updated_at)" />
                    </dl>
                </section>

                <section class="rounded-md border border-border bg-surface p-4">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <h2 class="text-h2">Contacts</h2>
                        <Link
                            v-if="can.createContact"
                            :href="route('contacts.create', { account_id: account.id })"
                            class="inline-flex min-h-11 items-center text-body text-secondary underline"
                        >
                            New contact
                        </Link>
                    </div>

                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full text-left text-body">
                            <thead class="text-small text-text-muted">
                                <tr>
                                    <th scope="col" class="px-3 py-2">Name</th>
                                    <th scope="col" class="px-3 py-2">Title</th>
                                    <th scope="col" class="px-3 py-2">Email</th>
                                    <th scope="col" class="px-3 py-2">Phone</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="account.contacts.length === 0">
                                    <td colspan="4" class="px-3 py-6 text-body text-text-muted">
                                        No contacts yet.
                                    </td>
                                </tr>
                                <tr
                                    v-for="contact in account.contacts"
                                    :key="contact.id"
                                    class="border-t border-border"
                                >
                                    <td class="px-3 py-2">
                                        <Link
                                            :href="route('contacts.show', contact.id)"
                                            class="text-secondary underline"
                                        >
                                            {{ fullName(contact) }}
                                        </Link>
                                    </td>
                                    <td class="px-3 py-2">{{ display(contact.title) }}</td>
                                    <td class="px-3 py-2">{{ display(contact.email) }}</td>
                                    <td class="px-3 py-2">{{ display(contact.phone) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>

        <Modal :show="confirming" max-width="md" @close="confirming = false">
            <div class="p-6">
                <h2 class="text-h2 text-text">Delete this account?</h2>
                <p class="mt-2 text-body text-text-muted">
                    {{ account.name }} will be removed. This cannot be undone.
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
