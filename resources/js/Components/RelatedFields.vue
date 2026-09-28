<script setup>
import FormField from '@/Components/FormField.vue';
import SelectInput from '@/Components/SelectInput.vue';
import { computed } from 'vue';

const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    types: {
        type: Array,
        required: true,
    },
    records: {
        type: Object,
        required: true,
    },
});

const recordOptions = computed(() => {
    const rows = props.records[props.form.related_type] ?? [];

    return rows.map((row) => ({
        value: String(row.id),
        label: row.label,
    }));
});

function onType(value) {
    props.form.related_type = value ?? '';
    props.form.related_id = '';
}
</script>

<template>
    <FormField label="Related to" :error="form.errors.related_type">
        <SelectInput
            id="related_type"
            :model-value="form.related_type"
            :options="types"
            placeholder="Select a type"
            @update:model-value="onType"
        />
    </FormField>

    <FormField label="Related record" :error="form.errors.related_id">
        <SelectInput
            id="related_id"
            v-model="form.related_id"
            :options="recordOptions"
            :disabled="!form.related_type"
            placeholder="Select a record"
        />
    </FormField>
</template>
