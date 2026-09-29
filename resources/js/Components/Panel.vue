<script setup>
defineProps({
    title: {
        type: String,
        default: '',
    },
    flush: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <section :class="flush ? 'crm-panel-flush' : 'crm-panel'">
        <div
            v-if="title || $slots.header"
            class="mb-4 flex flex-wrap items-center justify-between gap-3"
            :class="flush ? 'border-b border-border px-4 py-3 mb-0' : ''"
        >
            <h2 v-if="title" class="text-h2 font-semibold text-text">{{ title }}</h2>
            <slot name="header" />
        </div>
        <div :class="flush && (title || $slots.header) ? 'p-4' : ''">
            <slot />
        </div>
    </section>
</template>
