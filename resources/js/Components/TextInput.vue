<script setup>
import { onMounted, ref } from 'vue';

const model = defineModel({
    type: String,
    required: true,
});

const input = ref(null);

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <input
        class="rounded-md border-border bg-surface text-body text-text shadow-sm transition duration-fast focus:border-secondary focus:ring-2 focus:ring-secondary"
        v-model="model"
        ref="input"
    />
</template>
