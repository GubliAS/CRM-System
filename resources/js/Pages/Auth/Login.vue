<script setup>
import AuthBrandLayout from '@/Layouts/AuthBrandLayout.vue';
import AuthPillField from '@/Components/AuthPillField.vue';
import Checkbox from '@/Components/Checkbox.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <AuthBrandLayout
        variant="login"
        title="Login"
        subtitle="To continue"
    >
        <Head title="Log in" />

        <div v-if="status" class="auth-status">
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="auth-form">
            <AuthPillField
                id="email"
                v-model="form.email"
                type="email"
                icon="email"
                label="Email"
                placeholder="someone@gmail.com"
                required
                autofocus
                autocomplete="username"
                :error="form.errors.email"
            />

            <AuthPillField
                id="password"
                v-model="form.password"
                type="password"
                icon="lock"
                label="Password"
                placeholder="············"
                required
                autocomplete="current-password"
                :error="form.errors.password"
            />

            <label class="auth-remember">
                <Checkbox name="remember" v-model:checked="form.remember" />
                <span>Remember me</span>
            </label>

            <button
                type="submit"
                class="auth-btn auth-btn--primary"
                :disabled="form.processing"
            >
                Login
            </button>

            <Link
                v-if="canResetPassword"
                :href="route('password.request')"
                class="auth-meta-link"
            >
                Forgot your password?
            </Link>

            <p class="auth-footer">
                Don't have an account?
                <Link :href="route('register')" class="auth-footer-link">
                    Register
                </Link>
            </p>
        </form>
    </AuthBrandLayout>
</template>
