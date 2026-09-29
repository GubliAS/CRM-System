<script setup>
/* Tiny illustration of a widget type: used by the builder canvas and the
   layout thumbnails on the dashboard list. Uses currentColor. */
defineProps({
    type: { type: String, required: true },
});
</script>

<template>
    <svg viewBox="0 0 48 32" fill="none" aria-hidden="true" class="crm-glyph">
        <template v-if="type === 'chart'">
            <rect x="4" y="16" width="6" height="12" rx="2" fill="currentColor" opacity="0.45" />
            <rect x="14" y="8" width="6" height="20" rx="2" fill="currentColor" opacity="0.9" />
            <rect x="24" y="12" width="6" height="16" rx="2" fill="currentColor" opacity="0.55" />
            <rect x="34" y="4" width="6" height="24" rx="2" fill="currentColor" opacity="0.75" />
        </template>
        <template v-else-if="type === 'table'">
            <rect x="4" y="4" width="40" height="5" rx="2.5" fill="currentColor" opacity="0.9" />
            <rect x="4" y="13" width="40" height="4" rx="2" fill="currentColor" opacity="0.4" />
            <rect x="4" y="20" width="40" height="4" rx="2" fill="currentColor" opacity="0.4" />
            <rect x="4" y="27" width="26" height="3" rx="1.5" fill="currentColor" opacity="0.3" />
        </template>
        <template v-else-if="type === 'gauge'">
            <circle cx="24" cy="16" r="11" stroke="currentColor" stroke-width="5" opacity="0.25" />
            <path
                d="M24 5a11 11 0 0 1 10.4 14.6"
                stroke="currentColor"
                stroke-width="5"
                stroke-linecap="round"
            />
        </template>
        <template v-else>
            <rect x="6" y="6" width="26" height="12" rx="4" fill="currentColor" opacity="0.9" />
            <rect x="6" y="23" width="18" height="4" rx="2" fill="currentColor" opacity="0.4" />
            <circle cx="40" cy="12" r="3" fill="currentColor" opacity="0.5" />
        </template>
    </svg>
</template>
