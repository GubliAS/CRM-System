<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import PrimaryTabs from '@/Components/PrimaryTabs.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="min-h-screen bg-bg font-sans text-body text-text">
        <header class="border-b border-border bg-surface">
            <div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-3 px-4">
                <Link :href="route('home')" class="shrink-0">
                    <ApplicationLogo />
                </Link>

                <Dropdown align="right" width="48">
                    <template #trigger>
                        <button
                            type="button"
                            class="inline-flex min-h-11 items-center rounded-md border border-border bg-surface px-3 text-body text-text"
                        >
                            {{ $page.props.auth.user.name }}
                            <svg
                                class="ms-2 h-4 w-4"
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

            <nav aria-label="Primary" class="border-t border-border">
                <details class="md:hidden">
                    <summary
                        class="min-h-11 cursor-pointer px-4 py-3 text-body text-text"
                    >
                        Sections
                    </summary>
                    <PrimaryTabs stacked class="px-2 pb-2" />
                </details>

                <div class="mx-auto hidden max-w-7xl md:block">
                    <PrimaryTabs class="px-2" />
                </div>
            </nav>
        </header>

        <header class="bg-surface" v-if="$slots.header">
            <div class="mx-auto max-w-7xl px-4 py-6">
                <slot name="header" />
            </div>
        </header>

        <main>
            <slot />
        </main>
    </div>
</template>
