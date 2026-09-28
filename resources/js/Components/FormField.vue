<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';

defineProps({
    label: {
        type: String,
        required: true,
    },
    error: {
        type: String,
        default: '',
    },
    required: {
        type: Boolean,
        default: false,
    },
    span: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <div :class="span ? 'md:col-span-2' : ''">
        <InputLabel :value="label" :required="required" />
        <div class="mt-1">
            <slot />
        </div>
        <InputError class="mt-1" :message="error" />
    </div>
</template>
