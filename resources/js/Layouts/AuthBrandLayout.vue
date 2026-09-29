<script setup>
defineProps({
    variant: {
        type: String,
        required: true,
        validator: (value) => ['login', 'register'].includes(value),
    },
    title: {
        type: String,
        required: true,
    },
    subtitle: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <div class="auth-page">
        <div class="auth-frame" :data-auth-variant="variant">
            <div class="auth-frame-body">
                <header class="auth-heading">
                    <h1 class="auth-heading-title">{{ title }}</h1>
                    <p class="auth-heading-sub">{{ subtitle }}</p>
                </header>

                <slot />
            </div>

            <div class="auth-waves" aria-hidden="true">
                <svg viewBox="0 0 360 120" preserveAspectRatio="none">
                    <path
                        class="auth-wave-1"
                        d="M0 48 C 60 18, 120 78, 180 48 C 240 18, 300 72, 360 42 L 360 120 L 0 120 Z"
                    />
                    <path
                        class="auth-wave-2"
                        d="M0 68 C 70 42, 130 92, 190 68 C 250 44, 310 88, 360 62 L 360 120 L 0 120 Z"
                    />
                    <path
                        class="auth-wave-3"
                        d="M0 88 C 80 68, 140 108, 200 88 C 260 68, 320 104, 360 84 L 360 120 L 0 120 Z"
                    />
                </svg>
            </div>
        </div>
    </div>
</template>
