<script setup>
import { computed } from 'vue';

const props = defineProps({
    tone: {
        type: String,
        default: 'info',
        validator: (value) =>
            ['success', 'danger', 'warning', 'info', 'neutral'].includes(value),
    },
});

const classes = computed(() => {
    const map = {
        success: 'bg-success-soft text-success',
        danger: 'bg-danger-soft text-danger',
        warning: 'bg-warning-soft text-warning',
        info: 'bg-info-soft text-info',
        neutral: 'bg-bg text-text-muted',
    };

    return map[props.tone] ?? map.info;
});
</script>

<template>
    <span
        class="inline-flex max-w-full items-center truncate rounded-sm px-2 py-0.5 text-small font-medium"
        :class="classes"
    >
        <slot />
    </span>
</template>
