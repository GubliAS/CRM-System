<script setup>
import ReportBuilder from '@/Pages/Reports/Create.vue';

defineProps({
    builder: { type: Object, required: true },
    report: { type: Object, required: true },
});
</script>

<template>
    <ReportBuilder :builder="builder" :report="report" />
</template>
