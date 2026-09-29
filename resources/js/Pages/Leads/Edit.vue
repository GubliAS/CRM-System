<script setup>
import LeadForm from '@/Components/LeadForm.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { leadFormData } from '@/forms/lead';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    lead: { type: Object, required: true },
    salutations: { type: Array, required: true },
    statuses: { type: Array, required: true },
    sources: { type: Array, required: true },
    ratings: { type: Array, required: true },
    owners: { type: Array, default: () => [] },
    canReassign: { type: Boolean, default: false },
});

const form = useForm(leadFormData(props.lead));

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
    }).put(route('leads.update', props.lead.id));
}
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Edit lead" />

        <div class="crm-page">
            <h1 class="break-words text-h1">Edit lead</h1>
            <div class="mt-4 crm-panel">
                <LeadForm
                    :form="form"
                    :salutations="salutations"
                    :statuses="statuses"
                    :sources="sources"
                    :ratings="ratings"
                    :owners="owners"
                    :can-reassign="canReassign"
                    :cancel-href="route('leads.show', lead.id)"
                    @submit="submit"
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
