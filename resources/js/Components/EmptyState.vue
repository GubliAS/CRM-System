<script setup>
defineProps({
    title: {
        type: String,
        required: true,
    },
    message: {
        type: String,
        default: '',
    },
});
</script>

<template>
    <div class="crm-empty">
        <div
            class="mb-3 flex h-11 w-11 items-center justify-center rounded-md bg-primary-soft text-primary"
            aria-hidden="true"
        >
            <slot name="icon">
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.75"
                    class="h-5 w-5"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"
                    />
                </svg>
            </slot>
        </div>
        <p class="text-h3 font-semibold text-text">{{ title }}</p>
        <p v-if="message" class="mt-1 max-w-sm text-body text-text-muted">
            {{ message }}
        </p>
        <div v-if="$slots.action" class="mt-4">
            <slot name="action" />
        </div>
    </div>
</template>
