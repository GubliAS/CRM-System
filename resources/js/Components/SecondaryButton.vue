<script setup>
defineProps({
    type: {
        type: String,
        default: 'button',
    },
});
</script>

<template>
    <button
        :type="type"
        class="crm-btn-secondary focus:outline-none focus:ring-2 focus:ring-secondary focus:ring-offset-2 disabled:opacity-25"
    >
        <slot />
    </button>
</template>
