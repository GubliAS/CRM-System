<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    href: {
        type: String,
        required: true,
    },
    active: {
        type: Boolean,
    },
});

const classes = computed(() =>
    props.active
        ? 'block w-full ps-3 pe-4 py-2 border-l-4 border-secondary text-start text-h3 font-medium text-primary bg-bg focus:outline-none focus:text-primary focus:bg-bg focus:border-primary transition duration-150 ease-in-out'
        : 'block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-h3 font-medium text-text-muted hover:text-text hover:bg-bg hover:border-border focus:outline-none focus:text-text focus:bg-bg focus:border-border transition duration-150 ease-in-out',
);
</script>

<template>
    <Link :href="href" :class="classes">
        <slot />
    </Link>
</template>
