<script setup>
defineProps({
    options: {
        type: Array,
        required: true,
    },
    placeholder: {
        type: String,
        default: '',
    },
});

const model = defineModel({
    type: String,
    default: '',
});
</script>

<template>
    <select
        v-model="model"
        class="w-full rounded-md border-border bg-surface text-body text-text shadow-sm focus:border-secondary focus:ring-secondary"
    >
        <option value="">{{ placeholder }}</option>
        <option
            v-for="option in options"
            :key="option.value"
            :value="String(option.value)"
        >
            {{ option.label }}
        </option>
    </select>
</template>
