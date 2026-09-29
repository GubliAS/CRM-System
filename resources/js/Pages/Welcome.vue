<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';
import { computed } from 'vue';

const page = usePage();
const signedIn = computed(() => Boolean(page.props.auth?.user));

const steps = [
    {
        n: 1,
        label: 'Register your account',
        detail: 'Create your login',
        icon: 'lucide:user-plus',
        active: true,
    },
    {
        n: 2,
        label: 'Sign in to your workspace',
        detail: 'Secure access',
        icon: 'lucide:shield-check',
        active: false,
    },
    {
        n: 3,
        label: 'Open Home and start selling',
        detail: 'Pipeline ready',
        icon: 'lucide:rocket',
        active: false,
    },
];

const highlights = [
    { icon: 'lucide:git-branch', label: 'Pipeline' },
    { icon: 'lucide:headset', label: 'Service' },
    { icon: 'lucide:calendar-days', label: 'Calendar' },
];

const guestOptions = [
    {
        key: 'register',
        href: 'register',
        primary: true,
        icon: 'lucide:user-plus',
        title: 'Create an account',
        desc: 'Register to join your team workspace and start qualifying leads.',
        cta: 'Continue',
    },
    {
        key: 'login',
        href: 'login',
        primary: false,
        icon: 'lucide:log-in',
        title: 'Log in',
        desc: 'Already have access? Sign in to open your CRM workspace.',
        cta: 'Sign in',
    },
];

const signedInOptions = [
    {
        key: 'home',
        href: 'home',
        primary: true,
        icon: 'lucide:layout-dashboard',
        title: 'Go to Home',
        desc: 'Jump back into pipeline, tasks, and today’s suggestions.',
        cta: 'Open Home',
    },
];
</script>

<template>
    <div class="welcome-page">
        <Head title="Get started" />
        <div class="welcome-page-glow" aria-hidden="true" />

        <div class="welcome-shell">
            <aside class="welcome-hero" aria-label="Getting started">
                <div class="welcome-hero-orbs" aria-hidden="true">
                    <span class="welcome-orb welcome-orb-a" />
                    <span class="welcome-orb welcome-orb-b" />
                    <span class="welcome-orb welcome-orb-c" />
                </div>
                <div class="welcome-hero-grid" aria-hidden="true" />

                <div class="welcome-hero-top">
                    <div class="welcome-brand-row">
                        <Link href="/" class="welcome-brand">
                            <span class="welcome-brand-mark" aria-hidden="true">
                                <Icon icon="lucide:circle-dot" />
                            </span>
                            <span class="welcome-brand-name">CRM</span>
                        </Link>

                        <span class="welcome-badge">Join us to build</span>
                    </div>

                    <div class="welcome-hero-copy">
                        <p class="welcome-eyebrow">Sales &amp; service workspace</p>
                        <h1 class="welcome-hero-title">Start your Journey</h1>
                        <p class="welcome-hero-sub">
                            Follow these simple steps to set up your account and
                            open a shared pipeline for your team.
                        </p>
                    </div>

                    <div class="welcome-hero-visual" aria-hidden="true">
                        <img
                            src="/images/welcome-workspace.svg"
                            alt=""
                            class="welcome-hero-image"
                            width="520"
                            height="300"
                        />
                    </div>
                </div>

                <ol class="welcome-steps" aria-label="Setup steps">
                    <li
                        v-for="step in steps"
                        :key="step.n"
                        class="welcome-step"
                        :class="step.active ? 'welcome-step-active' : ''"
                    >
                        <div class="welcome-step-top">
                            <span class="welcome-step-num">{{ step.n }}</span>
                            <Icon :icon="step.icon" class="welcome-step-icon" aria-hidden="true" />
                        </div>
                        <span class="welcome-step-label">{{ step.label }}</span>
                        <span class="welcome-step-detail">{{ step.detail }}</span>
                    </li>
                </ol>
            </aside>

            <section class="welcome-panel" aria-labelledby="welcome-get-started">
                <div class="welcome-panel-inner">
                    <p class="welcome-panel-kicker">Welcome</p>
                    <h2 id="welcome-get-started" class="welcome-panel-title">
                        Get started
                    </h2>
                    <p class="welcome-panel-sub">
                        Choose how you want to enter the workspace. Create an
                        account if you are new, or sign in if you already have access.
                    </p>

                    <ul class="welcome-highlights" aria-label="Product highlights">
                        <li v-for="item in highlights" :key="item.label">
                            <Icon :icon="item.icon" aria-hidden="true" />
                            <span>{{ item.label }}</span>
                        </li>
                    </ul>

                    <div class="welcome-options">
                        <Link
                            v-for="option in signedIn ? signedInOptions : guestOptions"
                            :key="option.key"
                            :href="route(option.href)"
                            class="welcome-option"
                            :class="option.primary ? 'welcome-option-primary' : ''"
                        >
                            <span class="welcome-option-icon" aria-hidden="true">
                                <Icon :icon="option.icon" />
                            </span>
                            <span class="welcome-option-copy">
                                <span class="welcome-option-title">{{ option.title }}</span>
                                <span class="welcome-option-desc">{{ option.desc }}</span>
                            </span>
                            <span class="welcome-option-cta">
                                {{ option.cta }}
                                <Icon icon="lucide:arrow-right" aria-hidden="true" />
                            </span>
                        </Link>
                    </div>

                    <div class="welcome-panel-foot">
                        <Icon icon="lucide:lock-keyhole" aria-hidden="true" />
                        <p>
                            Sales pipeline and customer support in one place —
                            leads, accounts, opportunities, cases, and calendar.
                        </p>
                    </div>
                </div>
            </section>
        </div>
    </div>
</template>
