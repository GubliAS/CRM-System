<script setup>
import PrimaryTabs from '@/Components/PrimaryTabs.vue';
import { Icon } from '@iconify/vue';
import { Link, router } from '@inertiajs/vue3';

defineProps({
    expanded: {
        type: Boolean,
        default: false,
    },
    desktop: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(['navigate', 'close']);

function logout() {
    router.post(route('logout'));
}
</script>

<template>
    <div
        :id="desktop ? undefined : 'mobile-primary-nav'"
        class="crm-rail"
        :class="[
            desktop ? 'crm-rail-desktop hidden lg:flex' : 'crm-rail-mobile flex lg:hidden',
            expanded ? 'crm-rail-expanded' : '',
            !desktop && !expanded ? 'crm-rail-mobile-closed' : '',
        ]"
        aria-label="Primary"
    >
        <!-- 1) Main floating pill: logo + module icons only -->
        <div class="crm-rail-pill">
            <div class="flex w-full items-center justify-center gap-1">
                <Link
                    :href="route('home')"
                    class="crm-rail-brand"
                    title="CRM Home"
                    @click="emit('navigate')"
                >
                    <span class="crm-rail-logo" aria-hidden="true">C</span>
                    <span class="crm-rail-label text-h3 font-bold">CRM</span>
                </Link>
                <button
                    v-if="!desktop"
                    type="button"
                    class="crm-rail-link ms-auto !w-11 shrink-0"
                    aria-label="Close navigation"
                    @click="emit('close')"
                >
                    <span class="crm-rail-icon" aria-hidden="true">
                        <Icon icon="lucide:x" />
                    </span>
                </button>
            </div>

            <div class="crm-rail-nav-track">
                <PrimaryTabs @navigate="emit('navigate')" />
            </div>
        </div>

        <!-- 2) Real gap — page background shows through (flex gap on .crm-rail) -->

        <!-- 3) Settings sits in the open gap, not inside the pill -->
        <div class="crm-rail-tools">
            <Link
                :href="route('profile.edit')"
                class="crm-rail-tool"
                title="Settings"
                @click="emit('navigate')"
            >
                <span class="crm-rail-icon" aria-hidden="true">
                    <Icon icon="lucide:settings" />
                </span>
                <span class="crm-rail-label">Settings</span>
            </Link>
        </div>

        <!-- 4) Detached logout square — own dark block -->
        <button
            type="button"
            class="crm-rail-logout"
            title="Log out"
            @click="logout"
        >
            <span class="crm-rail-icon" aria-hidden="true">
                <Icon icon="lucide:log-out" />
            </span>
            <span class="crm-rail-label">Log out</span>
        </button>
    </div>
</template>
