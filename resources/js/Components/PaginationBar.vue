<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    paginator: {
        type: Object,
        required: true,
    },
});

function label(value) {
    return String(value)
        .replace(/&laquo;\s*/g, '')
        .replace(/\s*&raquo;/g, '')
        .replace(/&amp;/g, '&');
}
</script>

<template>
    <nav
        v-if="paginator.last_page > 1"
        class="flex flex-wrap gap-2"
        aria-label="Pagination"
    >
        <template v-for="(link, index) in paginator.links" :key="index">
            <Link
                v-if="link.url"
                :href="link.url"
                class="inline-flex min-h-11 items-center rounded-md border px-3 text-small"
                :class="
                    link.active
                        ? 'border-secondary bg-surface text-primary'
                        : 'border-border bg-surface text-text'
                "
            >
                {{ label(link.label) }}
            </Link>
            <span
                v-else
                class="inline-flex min-h-11 items-center rounded-md border border-border bg-bg px-3 text-small text-text-muted"
            >
                {{ label(link.label) }}
            </span>
        </template>
    </nav>
</template>
