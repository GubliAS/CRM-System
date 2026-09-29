<script setup>
import AuthBrandLayout from '@/Layouts/AuthBrandLayout.vue';
import AuthPillField from '@/Components/AuthPillField.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <AuthBrandLayout
        variant="register"
        title="Register"
        subtitle="For your account"
    >
        <Head title="Register" />

        <form @submit.prevent="submit" class="auth-form">
            <AuthPillField
                id="name"
                v-model="form.name"
                type="text"
                icon="user"
                label="Name"
                placeholder="Name"
                required
                autofocus
                autocomplete="name"
                :error="form.errors.name"
            />

            <AuthPillField
                id="email"
                v-model="form.email"
                type="email"
                icon="email"
                label="Email"
                placeholder="someone@gmail.com"
                required
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
                autocomplete="new-password"
                :error="form.errors.password"
            />

            <AuthPillField
                id="password_confirmation"
                v-model="form.password_confirmation"
                type="password"
                icon="lock"
                label="Confirm Password"
                placeholder="Confirm password"
                required
                autocomplete="new-password"
                :error="form.errors.password_confirmation"
            />

            <button
                type="submit"
                class="auth-btn auth-btn--primary"
                :disabled="form.processing"
            >
                Register
            </button>

            <p class="auth-footer">
                Already have an account?
                <Link :href="route('login')" class="auth-footer-link">
                    Login
                </Link>
            </p>
        </form>
    </AuthBrandLayout>
</template>
