<script setup>
import DangerButton from '@/Components/DangerButton.vue';
import DetailField from '@/Components/DetailField.vue';
import InputError from '@/Components/InputError.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { display, formatDay, formatWhen, fullName, personName } from '@/display';
import { Icon } from '@iconify/vue';
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

const locationLine = computed(() => {
    const parts = [
        props.contact.mailing_city,
        props.contact.mailing_state,
        props.contact.mailing_country,
    ].filter(Boolean);

    return parts.length ? parts.join(', ') : null;
});

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
    form.delete(route('contacts.destroy', props.contact.id), {
        preserveScroll: true,
    });
}
</script>

<template>
    <AuthenticatedLayout>
        <Head :title="title" />

        <div class="crm-page max-w-none px-4 py-4 sm:px-5">
            <nav class="crm-record-crumb">
                <Link :href="route('contacts.index', { view: 'all' })">Contacts</Link>
                <Icon icon="lucide:chevron-right" aria-hidden="true" />
                <span>{{ title }}</span>
            </nav>

            <header class="crm-record-hero">
                <div class="crm-record-hero-main">
                    <span
                        class="crm-account-avatar crm-record-hero-avatar"
                        :class="`crm-account-avatar-${avatarTone(contact.id)}`"
                        aria-hidden="true"
                    >
                        {{ initials(title) }}
                    </span>
                    <div class="min-w-0">
                        <div class="crm-record-hero-tags">
                            <span v-if="contact.title" class="crm-account-company-pill">
                                {{ contact.title }}
                            </span>
                            <span v-if="contact.department" class="crm-account-company-pill">
                                {{ contact.department }}
                            </span>
                        </div>
                        <h1 class="crm-record-hero-title">{{ title }}</h1>
                        <p class="crm-record-hero-sub">
                            <Link
                                v-if="contact.account"
                                :href="route('accounts.show', contact.account.id)"
                                class="crm-record-hero-meta crm-record-link"
                            >
                                <Icon icon="lucide:building-2" aria-hidden="true" />
                                {{ contact.account.name }}
                            </Link>
                            <span class="crm-record-hero-meta">
                                <Icon icon="lucide:user-round" aria-hidden="true" />
                                {{ display(contact.owner?.name) }}
                            </span>
                            <span v-if="contact.phone" class="crm-record-hero-meta">
                                <Icon icon="lucide:phone" aria-hidden="true" />
                                {{ contact.phone }}
                            </span>
                            <a
                                v-if="contact.email"
                                :href="`mailto:${contact.email}`"
                                class="crm-record-hero-meta crm-record-link"
                            >
                                <Icon icon="lucide:mail" aria-hidden="true" />
                                {{ contact.email }}
                            </a>
                            <span v-if="locationLine" class="crm-record-hero-meta">
                                <Icon icon="lucide:map-pin" aria-hidden="true" />
                                {{ locationLine }}
                            </span>
                        </p>
                    </div>
                </div>

                <div class="crm-record-hero-actions">
                    <Link
                        v-if="can.update"
                        :href="route('contacts.edit', contact.id)"
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
                        <h2>Contact details</h2>
                        <Icon icon="lucide:user-round" aria-hidden="true" />
                    </div>
                    <dl class="crm-record-fields">
                        <DetailField label="Salutation" :value="contact.salutation" />
                        <DetailField label="Account">
                            <Link
                                v-if="contact.account"
                                :href="route('accounts.show', contact.account.id)"
                                class="crm-record-link"
                            >
                                {{ contact.account.name }}
                            </Link>
                            <span v-else>—</span>
                        </DetailField>
                        <DetailField label="First name" :value="contact.first_name" />
                        <DetailField label="Last name" :value="contact.last_name" />
                        <DetailField label="Title" :value="contact.title" />
                        <DetailField label="Department" :value="contact.department" />
                        <DetailField label="Reports to">
                            <Link
                                v-if="contact.reports_to"
                                :href="route('contacts.show', contact.reports_to.id)"
                                class="crm-record-link"
                            >
                                {{ fullName(contact.reports_to) }}
                            </Link>
                            <span v-else>—</span>
                        </DetailField>
                        <DetailField label="Owner" :value="contact.owner?.name" />
                    </dl>
                </section>

                <section class="crm-record-card">
                    <div class="crm-record-card-head">
                        <h2>Phone and email</h2>
                        <Icon icon="lucide:phone" aria-hidden="true" />
                    </div>
                    <dl class="crm-record-fields">
                        <DetailField label="Phone" :value="contact.phone" />
                        <DetailField label="Mobile" :value="contact.mobile" />
                        <DetailField label="Home phone" :value="contact.home_phone" />
                        <DetailField label="Other phone" :value="contact.other_phone" />
                        <DetailField label="Email">
                            <a
                                v-if="contact.email"
                                :href="`mailto:${contact.email}`"
                                class="crm-record-link"
                            >
                                {{ contact.email }}
                            </a>
                            <span v-else>—</span>
                        </DetailField>
                        <DetailField label="Fax" :value="contact.fax" />
                    </dl>
                </section>

                <section class="crm-record-card">
                    <div class="crm-record-card-head">
                        <h2>Address</h2>
                        <Icon icon="lucide:map" aria-hidden="true" />
                    </div>
                    <div class="crm-record-address">
                        <div>
                            <h3>Mailing</h3>
                            <dl class="crm-record-fields crm-record-fields-stack">
                                <DetailField label="Street" :value="contact.mailing_street" />
                                <DetailField label="City" :value="contact.mailing_city" />
                                <DetailField label="State" :value="contact.mailing_state" />
                                <DetailField label="Postal code" :value="contact.mailing_postal_code" />
                                <DetailField label="Country" :value="contact.mailing_country" />
                            </dl>
                        </div>
                        <div>
                            <h3>Other</h3>
                            <dl class="crm-record-fields crm-record-fields-stack">
                                <DetailField label="Street" :value="contact.other_street" />
                                <DetailField label="City" :value="contact.other_city" />
                                <DetailField label="State" :value="contact.other_state" />
                                <DetailField label="Postal code" :value="contact.other_postal_code" />
                                <DetailField label="Country" :value="contact.other_country" />
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
                        <DetailField label="Assistant" :value="contact.assistant" />
                        <DetailField label="Assistant phone" :value="contact.assistant_phone" />
                        <DetailField label="Lead source" :value="contact.lead_source" />
                        <DetailField label="Birthdate" :value="formatDay(contact.birthdate)" />
                        <DetailField
                            class="crm-record-field-span"
                            label="Description"
                            :value="contact.description"
                        />
                    </dl>
                </section>

                <section class="crm-record-card crm-record-card-wide">
                    <div class="crm-record-card-head">
                        <h2>System information</h2>
                        <Icon icon="lucide:clock-3" aria-hidden="true" />
                    </div>
                    <dl class="crm-record-fields">
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
