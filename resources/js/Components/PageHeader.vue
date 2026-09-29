<script setup>
defineProps({
    title: {
        type: String,
        required: true,
    },
    subtitle: {
        type: String,
        default: '',
    },
});
</script>

<template>
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div class="min-w-0">
            <h1 class="break-words text-h1 font-bold text-text">{{ title }}</h1>
            <p v-if="subtitle || $slots.subtitle" class="mt-1 text-small text-text-muted">
                <slot name="subtitle">{{ subtitle }}</slot>
            </p>
        </div>
        <div v-if="$slots.actions" class="flex flex-wrap items-center gap-2">
            <slot name="actions" />
        </div>
    </div>
</template>
