<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
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
    <GuestLayout title="Log in">
        <Head title="Log in" />

        <div v-if="status" class="mb-4 text-body font-medium text-success">
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="grid grid-cols-1 gap-4">
            <div>
                <InputLabel required for="email" value="Email" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div>
                <InputLabel required for="password" value="Password" />

                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="block">
                <label class="flex min-h-11 items-center">
                    <Checkbox name="remember" v-model:checked="form.remember" />
                    <span class="ms-2 text-body text-text-muted">Remember me</span>
                </label>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3">
                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="rounded-md text-body text-secondary underline hover:text-secondary-hover focus:outline-none focus:ring-2 focus:ring-secondary focus:ring-offset-2"
                >
                    Forgot your password?
                </Link>
                <Link
                    v-else
                    :href="route('register')"
                    class="rounded-md text-body text-secondary underline hover:text-secondary-hover focus:outline-none focus:ring-2 focus:ring-secondary focus:ring-offset-2"
                >
                    Register
                </Link>

                <PrimaryButton
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Log in
                </PrimaryButton>
            </div>

            <p v-if="canResetPassword" class="text-small text-text-muted">
                Need an account?
                <Link :href="route('register')" class="text-secondary underline">
                    Register
                </Link>
            </p>
        </form>
    </GuestLayout>
</template>
