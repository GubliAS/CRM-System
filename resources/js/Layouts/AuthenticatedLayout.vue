<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import GlobalSearch from '@/Components/GlobalSearch.vue';
import PrimaryTabs from '@/Components/PrimaryTabs.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const page = usePage();
const success = computed(() => page.props.flash?.success ?? null);
const error = computed(() => page.props.flash?.error ?? null);
const missingRole = computed(
    () => !!page.props.auth?.user && !page.props.auth?.role_slug,
);
const mobileNavOpen = ref(false);
</script>

<template>
    <div class="min-h-screen bg-bg font-sans text-body text-text">
        <a
            href="#main-content"
            class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded-md focus:bg-surface focus:px-3 focus:py-2 focus:text-body focus:text-text"
        >
            Skip to content
        </a>

        <header class="crm-chrome bg-primary text-on-primary shadow-panel">
            <div class="mx-auto flex h-14 max-w-7xl items-center gap-3 px-3 sm:px-4">
                <button
                    type="button"
                    class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-md text-on-primary md:hidden"
                    :aria-expanded="mobileNavOpen"
                    aria-controls="mobile-primary-nav"
                    aria-label="Open navigation"
                    @click="mobileNavOpen = !mobileNavOpen"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.75"
                        class="h-5 w-5 text-on-primary"
                        aria-hidden="true"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"
                        />
                    </svg>
                </button>

                <Link :href="route('home')" class="shrink-0 text-on-primary">
                    <ApplicationLogo tone="on-primary" />
                </Link>

                <div class="hidden min-w-0 flex-1 justify-center px-2 sm:flex">
                    <GlobalSearch variant="header" />
                </div>

                <div class="ms-auto flex shrink-0 items-center gap-1 sm:gap-2">
                    <button
                        type="button"
                        class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-md text-on-primary hover:bg-primary-hover"
                        aria-label="Notifications"
                        title="Notifications"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.75"
                            class="h-5 w-5 text-on-primary"
                            aria-hidden="true"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"
                            />
                        </svg>
                    </button>

                    <Dropdown align="right" width="48">
                        <template #trigger>
                            <button
                                type="button"
                                class="inline-flex min-h-11 max-w-[10rem] items-center gap-2 rounded-md px-2 text-body text-on-primary hover:bg-primary-hover sm:max-w-none sm:px-3"
                            >
                                <span class="truncate text-on-primary">{{
                                    $page.props.auth.user.name
                                }}</span>
                                <svg
                                    class="h-4 w-4 shrink-0 text-on-primary"
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 20 20"
                                    fill="currentColor"
                                    aria-hidden="true"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                            </button>
                        </template>

                        <template #content>
                            <DropdownLink :href="route('profile.edit')">
                                Profile
                            </DropdownLink>
                            <DropdownLink
                                :href="route('logout')"
                                method="post"
                                as="button"
                            >
                                Log Out
                            </DropdownLink>
                        </template>
                    </Dropdown>
                </div>
            </div>

            <div class="border-t border-secondary px-3 py-2 sm:hidden">
                <GlobalSearch variant="header" />
            </div>

            <nav aria-label="Primary" class="border-t border-secondary">
                <div
                    id="mobile-primary-nav"
                    class="md:hidden"
                    :class="mobileNavOpen ? 'block' : 'hidden'"
                >
                    <PrimaryTabs
                        stacked
                        tone="on-primary"
                        class="px-2 py-2"
                        @navigate="mobileNavOpen = false"
                    />
                </div>

                <div class="mx-auto hidden max-w-7xl overflow-x-auto md:block">
                    <PrimaryTabs tone="on-primary" class="px-2" />
                </div>
            </nav>
        </header>

        <div
            v-if="missingRole"
            class="crm-flash border-b border-warning bg-warning-soft text-warning"
            role="status"
        >
            <p class="mx-auto max-w-7xl px-4 py-3 text-body">
                Your account has no role. Ask an administrator to assign one
                before opening CRM modules.
            </p>
        </div>
        <div
            v-if="success"
            class="crm-flash border-b border-success bg-success-soft text-success"
            role="status"
        >
            <p class="mx-auto max-w-7xl px-4 py-3 text-body">{{ success }}</p>
        </div>
        <div
            v-if="error"
            class="crm-flash border-b border-danger bg-danger-soft text-danger"
            role="alert"
        >
            <p class="mx-auto max-w-7xl px-4 py-3 text-body">{{ error }}</p>
        </div>

        <header v-if="$slots.header" class="border-b border-border bg-surface shadow-panel">
            <div class="mx-auto max-w-7xl px-4 py-4">
                <slot name="header" />
            </div>
        </header>

        <main id="main-content">
            <slot />
        </main>
    </div>
</template>
