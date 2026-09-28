<script setup>
const model = defineModel({
    type: String,
    default: '',
});
</script>

<template>
    <textarea
        v-model="model"
        class="w-full rounded-md border-border text-body text-text shadow-sm focus:border-secondary focus:ring-secondary"
    />
</template>
