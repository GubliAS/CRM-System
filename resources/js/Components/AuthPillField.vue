<script setup>
import { onMounted, ref } from 'vue';

defineProps({
    id: {
        type: String,
        required: true,
    },
    type: {
        type: String,
        default: 'text',
    },
    label: {
        type: String,
        required: true,
    },
    placeholder: {
        type: String,
        default: '',
    },
    autocomplete: {
        type: String,
        default: undefined,
    },
    required: {
        type: Boolean,
        default: false,
    },
    autofocus: {
        type: Boolean,
        default: false,
    },
    icon: {
        type: String,
        required: true,
        validator: (value) =>
            ['user', 'email', 'lock'].includes(value),
    },
    error: {
        type: String,
        default: undefined,
    },
});

const model = defineModel({
    type: String,
    required: true,
});

const input = ref(null);

onMounted(() => {
    if (input.value?.hasAttribute('autofocus')) {
        input.value.focus();
    }
});
</script>

<template>
    <div>
        <div class="auth-pill-field">
            <span class="auth-pill-icon" aria-hidden="true">
                <svg
                    v-if="icon === 'user'"
                    viewBox="0 0 24 24"
                    stroke-width="1.75"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >
                    <path d="M20 21a8 8 0 0 0-16 0" />
                    <circle cx="12" cy="8" r="4" />
                </svg>
                <svg
                    v-else-if="icon === 'email'"
                    viewBox="0 0 24 24"
                    stroke-width="1.75"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >
                    <rect x="3" y="5" width="18" height="14" rx="2" />
                    <path d="m3 7 9 7 9-7" />
                </svg>
                <svg
                    v-else
                    viewBox="0 0 24 24"
                    stroke-width="1.75"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >
                    <rect x="5" y="11" width="14" height="10" rx="2" />
                    <path d="M8 11V8a4 4 0 0 1 8 0v3" />
                </svg>
            </span>

            <label :for="id" class="sr-only">{{ label }}</label>
            <input
                :id="id"
                ref="input"
                class="auth-pill-input"
                :type="type"
                :placeholder="placeholder"
                :autocomplete="autocomplete"
                :required="required"
                :autofocus="autofocus"
                :aria-label="label"
                v-model="model"
            />
        </div>

        <p v-if="error" class="auth-error">{{ error }}</p>
    </div>
</template>
