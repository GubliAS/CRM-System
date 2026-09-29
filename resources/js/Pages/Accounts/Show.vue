<script setup>
import DangerButton from '@/Components/DangerButton.vue';
import DetailField from '@/Components/DetailField.vue';
import InputError from '@/Components/InputError.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { display, formatMoney, formatWhen, fullName, personName, websiteHref } from '@/display';
import { Icon } from '@iconify/vue';
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

const locationLine = computed(() => {
    const parts = [
        props.account.billing_city,
        props.account.billing_state,
        props.account.billing_country,
    ].filter(Boolean);

    return parts.length ? parts.join(', ') : null;
});

const TYPE_TAGS = {
    Customer: 'crm-account-status-active',
    Prospect: 'crm-account-status-inactive',
    Partner: 'crm-account-status-partner',
    Other: 'crm-account-status-other',
};

function typeTagClass(type) {
    return `crm-account-status ${TYPE_TAGS[type] ?? 'crm-account-status-other'}`;
}

function initials(name) {
    if (!name) {
        return '?';
    }

    return name
        .split(/\s+/)
        .filter(Boolean)
        .map((part) => part.charAt(0).toUpperCase())
        .join('')
        .slice(0, 2);
}

function avatarTone(id) {
    const tones = ['a', 'b', 'c', 'd', 'e', 'f'];

    return tones[Number(id) % tones.length];
}

function destroy() {
    form.delete(route('accounts.destroy', props.account.id), {
        preserveScroll: true,
    });
}
</script>

<template>
    <AuthenticatedLayout>
        <Head :title="account.name" />

        <div class="crm-page max-w-none px-4 py-4 sm:px-5">
            <nav class="crm-record-crumb">
                <Link :href="route('accounts.index', { view: 'all' })">Accounts</Link>
                <Icon icon="lucide:chevron-right" aria-hidden="true" />
                <span>{{ account.name }}</span>
            </nav>

            <header class="crm-record-hero">
                <div class="crm-record-hero-main">
                    <span
                        class="crm-account-avatar crm-record-hero-avatar"
                        :class="`crm-account-avatar-${avatarTone(account.id)}`"
                        aria-hidden="true"
                    >
                        {{ initials(account.name) }}
                    </span>
                    <div class="min-w-0">
                        <div class="crm-record-hero-tags">
                            <span :class="typeTagClass(account.type)">
                                {{ display(account.type) }}
                            </span>
                            <span v-if="account.industry" class="crm-account-company-pill">
                                {{ account.industry }}
                            </span>
                        </div>
                        <h1 class="crm-record-hero-title">{{ account.name }}</h1>
                        <p class="crm-record-hero-sub">
                            <span v-if="locationLine" class="crm-record-hero-meta">
                                <Icon icon="lucide:map-pin" aria-hidden="true" />
                                {{ locationLine }}
                            </span>
                            <span class="crm-record-hero-meta">
                                <Icon icon="lucide:user-round" aria-hidden="true" />
                                {{ display(account.owner?.name) }}
                            </span>
                            <span v-if="account.phone" class="crm-record-hero-meta">
                                <Icon icon="lucide:phone" aria-hidden="true" />
                                {{ account.phone }}
                            </span>
                            <a
                                v-if="siteHref"
                                :href="siteHref"
                                class="crm-record-hero-meta crm-record-link"
                                rel="noopener noreferrer"
                                target="_blank"
                            >
                                <Icon icon="lucide:globe" aria-hidden="true" />
                                {{ account.website }}
                            </a>
                        </p>
                    </div>
                </div>

                <div class="crm-record-hero-actions">
                    <Link
                        v-if="can.update"
                        :href="route('accounts.edit', account.id)"
                        class="crm-btn-secondary"
                    >
                        <span class="inline-flex items-center gap-2">
                            <Icon icon="lucide:pencil" aria-hidden="true" />
                            Edit
                        </span>
                    </Link>
                    <DangerButton
                        v-if="can.delete"
                        type="button"
                        class="min-h-11"
                        @click="confirming = true"
                    >
                        Delete
                    </DangerButton>
                </div>
            </header>

            <InputError class="mt-3" :message="deleteError" />

            <div class="crm-record-grid mt-5">
                <section class="crm-record-card">
                    <div class="crm-record-card-head">
                        <h2>Account details</h2>
                        <Icon icon="lucide:building-2" aria-hidden="true" />
                    </div>
                    <dl class="crm-record-fields">
                        <DetailField label="Account name" :value="account.name" />
                        <DetailField label="Parent account">
                            <Link
                                v-if="account.parent"
                                :href="route('accounts.show', account.parent.id)"
                                class="crm-record-link"
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
                                class="crm-record-link"
                                rel="noopener noreferrer"
                                target="_blank"
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

                <section class="crm-record-card">
                    <div class="crm-record-card-head">
                        <h2>Address</h2>
                        <Icon icon="lucide:map" aria-hidden="true" />
                    </div>
                    <div class="crm-record-address">
                        <div>
                            <h3>Billing</h3>
                            <dl class="crm-record-fields crm-record-fields-stack">
                                <DetailField label="Street" :value="account.billing_street" />
                                <DetailField label="City" :value="account.billing_city" />
                                <DetailField label="State" :value="account.billing_state" />
                                <DetailField label="Postal code" :value="account.billing_postal_code" />
                                <DetailField label="Country" :value="account.billing_country" />
                            </dl>
                        </div>
                        <div>
                            <h3>Shipping</h3>
                            <dl class="crm-record-fields crm-record-fields-stack">
                                <DetailField label="Street" :value="account.shipping_street" />
                                <DetailField label="City" :value="account.shipping_city" />
                                <DetailField label="State" :value="account.shipping_state" />
                                <DetailField label="Postal code" :value="account.shipping_postal_code" />
                                <DetailField label="Country" :value="account.shipping_country" />
                            </dl>
                        </div>
                    </div>
                </section>

                <section class="crm-record-card">
                    <div class="crm-record-card-head">
                        <h2>Additional information</h2>
                        <Icon icon="lucide:info" aria-hidden="true" />
                    </div>
                    <dl class="crm-record-fields">
                        <DetailField label="Employees" :value="account.employees" />
                        <DetailField label="Annual revenue" :value="formatMoney(account.annual_revenue)" />
                        <DetailField
                            class="crm-record-field-span"
                            label="Description"
                            :value="account.description"
                        />
                    </dl>
                </section>

                <section class="crm-record-card">
                    <div class="crm-record-card-head">
                        <h2>System information</h2>
                        <Icon icon="lucide:clock-3" aria-hidden="true" />
                    </div>
                    <dl class="crm-record-fields">
                        <DetailField label="Created by" :value="personName(account.created_by)" />
                        <DetailField label="Created at" :value="formatWhen(account.created_at)" />
                        <DetailField label="Updated by" :value="personName(account.updated_by)" />
                        <DetailField label="Updated at" :value="formatWhen(account.updated_at)" />
                    </dl>
                </section>

                <section class="crm-record-card crm-record-card-wide">
                    <div class="crm-record-card-head">
                        <h2>Contacts</h2>
                        <Link
                            v-if="can.createContact"
                            :href="route('contacts.create', { account_id: account.id })"
                            class="crm-record-card-action"
                        >
                            <Icon icon="lucide:plus" aria-hidden="true" />
                            New contact
                        </Link>
                    </div>

                    <div
                        v-if="account.contacts.length === 0"
                        class="crm-record-empty"
                        role="status"
                    >
                        No contacts yet.
                    </div>

                    <div v-else class="crm-ov-tablewrap">
                        <table class="crm-ov-table">
                            <thead>
                                <tr>
                                    <th scope="col">Name</th>
                                    <th scope="col">Title</th>
                                    <th scope="col">Email</th>
                                    <th scope="col">Phone</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="contact in account.contacts"
                                    :key="contact.id"
                                >
                                    <td>
                                        <Link
                                            :href="route('contacts.show', contact.id)"
                                            class="crm-record-link crm-ov-cell-strong"
                                        >
                                            {{ fullName(contact) }}
                                        </Link>
                                    </td>
                                    <td>{{ display(contact.title) }}</td>
                                    <td>{{ display(contact.email) }}</td>
                                    <td>{{ display(contact.phone) }}</td>
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
                    <DangerButton
                        type="button"
                        class="min-h-11"
                        :disabled="form.processing"
                        @click="destroy"
                    >
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
