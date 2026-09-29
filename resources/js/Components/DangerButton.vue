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
        class="inline-flex min-h-11 items-center justify-center rounded-md border border-transparent bg-danger px-4 py-2 text-small font-semibold uppercase tracking-widest text-surface transition duration-fast ease-in-out hover:bg-danger focus:outline-none focus:ring-2 focus:ring-danger focus:ring-offset-2 active:bg-danger disabled:opacity-25"
    >
        <slot />
    </button>
</template>
