<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import GlobalSearch from '@/Components/GlobalSearch.vue';
import PrimaryTabs from '@/Components/PrimaryTabs.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const page = usePage();
const success = computed(() => page.props.flash?.success ?? null);
const error = computed(() => page.props.flash?.error ?? null);
const missingRole = computed(
    () => !!page.props.auth?.user && !page.props.auth?.role_slug,
);
const mobileNavOpen = ref(false);

watch(
    () => page.url,
    () => {
        mobileNavOpen.value = false;
    },
);

function closeMobileNav() {
    mobileNavOpen.value = false;
}
</script>

<template>
    <div class="min-h-screen bg-bg font-sans text-body text-text">
        <a
            href="#main-content"
            class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded-md focus:bg-surface focus:px-3 focus:py-2 focus:text-body focus:text-text"
        >
            Skip to content
        </a>

        <div
            v-if="mobileNavOpen"
            class="fixed inset-0 z-40 bg-overlay md:hidden"
            aria-hidden="true"
            @click="closeMobileNav"
        />

        <div class="flex min-h-screen">
            <aside
                id="mobile-primary-nav"
                class="fixed inset-y-0 start-0 z-50 flex w-[var(--sidebar-width)] shrink-0 flex-col border-e border-border bg-surface shadow-dropdown transition-transform duration-fast md:static md:z-0 md:translate-x-0 md:shadow-none"
                :class="
                    mobileNavOpen
                        ? 'translate-x-0'
                        : '-translate-x-full md:translate-x-0'
                "
                aria-label="Primary"
            >
                <div
                    class="flex h-14 shrink-0 items-center gap-2 border-b border-border px-4"
                >
                    <Link
                        :href="route('home')"
                        class="min-w-0"
                        @click="closeMobileNav"
                    >
                        <ApplicationLogo />
                    </Link>
                    <button
                        type="button"
                        class="ms-auto inline-flex min-h-11 min-w-11 items-center justify-center rounded-md text-text hover:bg-bg md:hidden"
                        aria-label="Close navigation"
                        @click="closeMobileNav"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.75"
                            class="h-5 w-5"
                            aria-hidden="true"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>

                <nav class="flex-1 overflow-y-auto px-3 py-3" aria-label="Modules">
                    <PrimaryTabs
                        stacked
                        tone="sidebar"
                        @navigate="closeMobileNav"
                    />
                </nav>

                <div class="shrink-0 border-t border-border p-3">
                    <p class="truncate px-3 text-small text-text-muted">
                        {{ $page.props.auth.user.name }}
                    </p>
                    <div class="mt-1 flex flex-col">
                        <Link
                            :href="route('profile.edit')"
                            class="inline-flex min-h-11 items-center rounded-md px-3 text-body text-text hover:bg-bg"
                            @click="closeMobileNav"
                        >
                            Profile
                        </Link>
                        <Link
                            :href="route('logout')"
                            method="post"
                            as="button"
                            class="inline-flex min-h-11 items-center rounded-md px-3 text-body text-text hover:bg-bg"
                        >
                            Log Out
                        </Link>
                    </div>
                </div>
            </aside>

            <div class="flex min-w-0 flex-1 flex-col">
                <header
                    class="sticky top-0 z-30 border-b border-border bg-surface shadow-panel"
                >
                    <div
                        class="flex h-14 items-center gap-2 px-3 sm:gap-3 sm:px-4"
                    >
                        <button
                            type="button"
                            class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-md text-text hover:bg-bg md:hidden"
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
                                class="h-5 w-5"
                                aria-hidden="true"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"
                                />
                            </svg>
                        </button>

                        <div class="min-w-0 flex-1">
                            <GlobalSearch variant="header" />
                        </div>

                        <div class="flex shrink-0 items-center gap-1 sm:gap-2">
                            <Dropdown align="right" width="48">
                                <template #trigger>
                                    <button
                                        type="button"
                                        class="inline-flex min-h-11 max-w-[10rem] items-center gap-2 rounded-md px-2 text-body text-text hover:bg-bg sm:max-w-none sm:px-3"
                                    >
                                        <span
                                            class="hidden h-8 w-8 shrink-0 items-center justify-center rounded-md bg-primary-soft text-small font-semibold text-primary sm:inline-flex"
                                            aria-hidden="true"
                                        >
                                            {{
                                                $page.props.auth.user.name
                                                    .charAt(0)
                                                    .toUpperCase()
                                            }}
                                        </span>
                                        <span class="truncate">{{
                                            $page.props.auth.user.name
                                        }}</span>
                                        <svg
                                            class="h-4 w-4 shrink-0 text-text-muted"
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
                </header>

                <div
                    v-if="missingRole"
                    class="crm-flash border-b border-warning bg-warning-soft text-warning"
                    role="status"
                >
                    <p class="px-4 py-3 text-body">
                        Your account has no role. Ask an administrator to assign
                        one before opening CRM modules.
                    </p>
                </div>
                <div
                    v-if="success"
                    class="crm-flash border-b border-success bg-success-soft text-success"
                    role="status"
                >
                    <p class="px-4 py-3 text-body">{{ success }}</p>
                </div>
                <div
                    v-if="error"
                    class="crm-flash border-b border-danger bg-danger-soft text-danger"
                    role="alert"
                >
                    <p class="px-4 py-3 text-body">{{ error }}</p>
                </div>

                <header
                    v-if="$slots.header"
                    class="border-b border-border bg-surface"
                >
                    <div class="mx-auto max-w-7xl px-4 py-4">
                        <slot name="header" />
                    </div>
                </header>

                <main id="main-content" class="flex-1">
                    <slot />
                </main>
            </div>
        </div>
    </div>
</template>
