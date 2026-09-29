<script setup>
import AppSidebar from '@/Components/AppSidebar.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import GlobalSearch from '@/Components/GlobalSearch.vue';
import { Icon } from '@iconify/vue';
import { usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const page = usePage();
const success = computed(() => page.props.flash?.success ?? null);
const error = computed(() => page.props.flash?.error ?? null);
const missingRole = computed(
    () => !!page.props.auth?.user && !page.props.auth?.role_slug,
);
const userInitials = computed(() => {
    const parts = String(page.props.auth?.user?.name ?? '')
        .trim()
        .split(/\s+/)
        .filter(Boolean);

    return (parts[0]?.[0] ?? '?').concat(parts.length > 1 ? parts[parts.length - 1][0] : '').toUpperCase();
});
const mobileNavOpen = ref(false);
const notificationsOpen = ref(false);
const searchOpen = ref(false);
const notifyRoot = ref(null);

watch(
    () => page.url,
    () => {
        mobileNavOpen.value = false;
        notificationsOpen.value = false;
        searchOpen.value = false;
    },
);

onMounted(() => {
    document.addEventListener('click', onDocumentClick);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', onDocumentClick);
});

function onDocumentClick(event) {
    if (!notifyRoot.value?.contains(event.target)) {
        notificationsOpen.value = false;
    }
}

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
            class="fixed inset-0 z-40 bg-overlay lg:hidden"
            aria-hidden="true"
            @click="closeMobileNav"
        />

        <AppSidebar desktop />
        <AppSidebar
            :desktop="false"
            :expanded="mobileNavOpen"
            @navigate="closeMobileNav"
            @close="closeMobileNav"
        />

        <div class="crm-shell-main">
            <header class="crm-topbar sticky top-0 z-30 backdrop-blur-sm">
                <button
                    type="button"
                    class="crm-topbar-icon-btn lg:hidden"
                    :aria-expanded="mobileNavOpen"
                    aria-controls="mobile-primary-nav"
                    aria-label="Open navigation"
                    @click="mobileNavOpen = !mobileNavOpen"
                >
                    <Icon icon="lucide:menu" class="text-xl" />
                </button>

                <div class="min-w-0 flex-1 pe-2">
                    <slot name="header" />
                </div>

                <div class="crm-topbar-actions">
                    <div class="hidden min-w-0 max-w-xs flex-1 sm:block md:max-w-sm lg:max-w-md">
                        <GlobalSearch variant="header" />
                    </div>

                    <button
                        type="button"
                        class="crm-topbar-icon-btn sm:hidden"
                        aria-label="Search"
                        :aria-expanded="searchOpen"
                        @click="searchOpen = !searchOpen"
                    >
                        <Icon icon="lucide:search" class="text-xl" />
                    </button>

                    <div ref="notifyRoot" class="relative">
                        <button
                            type="button"
                            class="crm-topbar-icon-btn"
                            aria-label="Notifications"
                            :aria-expanded="notificationsOpen"
                            @click="notificationsOpen = !notificationsOpen"
                        >
                            <Icon icon="lucide:bell" class="text-xl" />
                        </button>

                        <div
                            v-if="notificationsOpen"
                            class="absolute end-0 top-full z-40 mt-2 w-72 rounded-card border border-border bg-surface p-4 shadow-dropdown"
                            role="dialog"
                            aria-label="Notifications"
                        >
                            <div class="flex items-center gap-2 text-text">
                                <Icon
                                    icon="lucide:bell-ring"
                                    class="text-lg text-secondary"
                                />
                                <p class="text-body font-semibold">Notifications</p>
                            </div>
                            <p class="mt-2 text-small text-text-muted">
                                No new notifications.
                            </p>
                        </div>
                    </div>

                    <Dropdown align="right" width="48">
                        <template #trigger>
                            <button
                                type="button"
                                class="crm-topbar-icon-btn overflow-hidden p-0"
                                aria-label="User menu"
                            >
                                <span
                                    class="inline-flex h-full w-full items-center justify-center bg-primary text-small font-bold text-on-primary"
                                >
                                    {{ userInitials }}
                                </span>
                            </button>
                        </template>

                        <template #content>
                            <div class="border-b border-border px-4 py-3">
                                <p class="truncate text-body font-semibold text-text">
                                    {{ $page.props.auth.user.name }}
                                </p>
                                <p class="truncate text-small text-text-muted">
                                    {{ $page.props.auth.user.email }}
                                </p>
                            </div>
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
            </header>

            <div
                v-if="searchOpen"
                class="border-b border-border bg-surface px-4 py-3 sm:hidden"
            >
                <GlobalSearch variant="header" />
            </div>

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

            <main id="main-content" class="flex-1">
                <slot />
            </main>
        </div>
    </div>
</template>
