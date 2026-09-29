<script setup>
defineProps({
    tone: {
        type: String,
        default: 'default',
    },
});
</script>

<template>
    <span
        class="inline-flex items-center gap-2 font-sans text-h2 font-bold"
        :class="tone === 'on-primary' ? 'text-on-primary' : 'text-primary'"
    >
        <span
            class="inline-flex h-8 w-8 items-center justify-center rounded-sm text-small font-bold"
            :class="
                tone === 'on-primary'
                    ? 'bg-surface text-primary'
                    : 'bg-primary text-on-primary'
            "
            aria-hidden="true"
        >
            C
        </span>
        <span :class="tone === 'on-primary' ? 'text-on-primary' : null"
            >CRM</span
        >
    </span>
</template>
