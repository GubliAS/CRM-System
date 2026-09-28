<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    href: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <Link
        :href="href"
        class="block w-full px-4 py-2 text-start text-body leading-5 text-text transition duration-150 ease-in-out hover:bg-bg focus:bg-bg focus:outline-none"
    >
        <slot />
    </Link>
</template>
