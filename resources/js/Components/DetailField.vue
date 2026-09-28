<script setup>
import { computed } from 'vue';

const props = defineProps({
    label: {
        type: String,
        required: true,
    },
    value: {
        type: [String, Number],
        default: '',
    },
});

const display = computed(() => {
    if (props.value === null || props.value === undefined || props.value === '') {
        return '—';
    }

    return String(props.value);
});
</script>

<template>
    <div class="min-w-0">
        <dt class="text-small text-text-muted">{{ label }}</dt>
        <dd class="break-words text-body text-text">
            <slot>{{ display }}</slot>
        </dd>
    </div>
</template>
