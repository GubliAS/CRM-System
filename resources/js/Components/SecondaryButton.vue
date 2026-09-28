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
        class="inline-flex items-center rounded-md border border-border bg-surface px-4 py-2 text-small font-semibold uppercase tracking-widest text-text shadow-sm transition duration-150 ease-in-out hover:bg-bg focus:outline-none focus:ring-2 focus:ring-secondary focus:ring-offset-2 disabled:opacity-25"
    >
        <slot />
    </button>
</template>
