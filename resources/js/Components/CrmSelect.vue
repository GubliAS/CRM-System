<script setup>
/*
 * Custom dropdown (replaces the browser's native <select> popup).
 *
 *   <CrmSelect v-model="value" :options="[{ value: 1, label: 'One' }]" />
 *
 * Options: { value, label, icon?, hint?, disabled? }. Works with string or
 * number values. Keyboard: Enter/Space/ArrowDown open; arrows, Home/End move;
 * Enter selects; Escape closes; typing jumps to a matching option. The popup is
 * rendered in <body> and positioned from the trigger, so a scrolling or
 * overflow-hidden parent can never clip it.
 */
import { Icon } from '@iconify/vue';
import { computed, nextTick, onBeforeUnmount, ref, useId, watch } from 'vue';

const props = defineProps({
    modelValue: { type: [String, Number, null], default: null },
    options: { type: Array, required: true },
    placeholder: { type: String, default: 'Select…' },
    ariaLabel: { type: String, default: undefined },
    /** 'pill' rounded toolbar control, 'field' form input, 'bare' no chrome. */
    variant: { type: String, default: 'field' },
    icon: { type: String, default: undefined },
    disabled: { type: Boolean, default: false },
    /** Popup alignment relative to the trigger. */
    align: { type: String, default: 'start' },
    /** Minimum popup width in px (defaults to the trigger width). */
    minWidth: { type: Number, default: 0 },
});

const emit = defineEmits(['update:modelValue', 'change']);

const uid = useId();
const trigger = ref(null);
const popup = ref(null);
const open = ref(false);
const active = ref(-1);
const place = ref({ top: 0, bottom: 0, left: 0, width: 0, maxHeight: 280, above: false });
let typed = '';
let typedTimer = null;

const selectedIndex = computed(() => props.options.findIndex((option) => option.value === props.modelValue));
const selected = computed(() => props.options[selectedIndex.value] ?? null);
const optionId = (index) => `${uid}-opt-${index}`;
const enabled = (index) => !props.options[index]?.disabled;

function firstEnabled(from, step) {
    let index = from;

    while (index >= 0 && index < props.options.length) {
        if (enabled(index)) {
            return index;
        }
        index += step;
    }

    return -1;
}

function position() {
    const rect = trigger.value.getBoundingClientRect();
    const room = window.innerHeight - rect.bottom - 12;
    const above = room < 200 && rect.top > room;
    const width = Math.max(rect.width, props.minWidth);
    let left = props.align === 'end' ? rect.right - width : rect.left;

    left = Math.max(8, Math.min(left, window.innerWidth - width - 8));

    place.value = {
        above,
        left,
        width,
        maxHeight: Math.max(140, Math.min(320, (above ? rect.top : room) - 8)),
        top: rect.bottom + 6,
        bottom: window.innerHeight - rect.top + 6,
    };
}

async function openMenu() {
    if (props.disabled || open.value) {
        return;
    }

    position();
    open.value = true;
    active.value = selectedIndex.value >= 0 ? selectedIndex.value : firstEnabled(0, 1);
    await nextTick();
    scrollToActive();
}

function closeMenu({ refocus = false } = {}) {
    open.value = false;
    active.value = -1;

    if (refocus) {
        trigger.value?.focus();
    }
}

function choose(index) {
    const option = props.options[index];

    if (!option || option.disabled) {
        return;
    }

    if (option.value !== props.modelValue) {
        emit('update:modelValue', option.value);
        emit('change', option.value);
    }

    closeMenu({ refocus: true });
}

function scrollToActive() {
    popup.value?.querySelector(`#${CSS.escape(optionId(active.value))}`)?.scrollIntoView({ block: 'nearest' });
}

function move(step) {
    const next = firstEnabled(
        Math.min(Math.max(active.value + step, 0), props.options.length - 1),
        step >= 0 ? 1 : -1,
    );

    if (next !== -1) {
        active.value = next;
        nextTick(scrollToActive);
    }
}

function onKeydown(event) {
    if (props.disabled) {
        return;
    }

    if (!open.value) {
        if (['ArrowDown', 'ArrowUp', 'Enter', ' '].includes(event.key)) {
            event.preventDefault();
            openMenu();
        }

        return;
    }

    switch (event.key) {
        case 'ArrowDown':
            event.preventDefault();
            move(1);
            break;
        case 'ArrowUp':
            event.preventDefault();
            move(-1);
            break;
        case 'Home':
            event.preventDefault();
            active.value = firstEnabled(0, 1);
            nextTick(scrollToActive);
            break;
        case 'End':
            event.preventDefault();
            active.value = firstEnabled(props.options.length - 1, -1);
            nextTick(scrollToActive);
            break;
        case 'Enter':
        case ' ':
            event.preventDefault();
            choose(active.value);
            break;
        case 'Escape':
            event.preventDefault();
            closeMenu({ refocus: true });
            break;
        case 'Tab':
            closeMenu();
            break;
        default:
            if (event.key.length === 1 && !event.ctrlKey && !event.metaKey) {
                typeAhead(event.key);
            }
    }
}

function typeAhead(char) {
    clearTimeout(typedTimer);
    typed += char.toLowerCase();
    typedTimer = setTimeout(() => (typed = ''), 600);

    const start = active.value >= 0 ? active.value : 0;
    const order = [...props.options.keys()];
    const rotated = [...order.slice(start + (typed.length === 1 ? 1 : 0)), ...order.slice(0, start + 1)];
    const hit = rotated.find(
        (index) => enabled(index) && String(props.options[index].label).toLowerCase().startsWith(typed),
    );

    if (hit !== undefined) {
        active.value = hit;
        nextTick(scrollToActive);
    }
}

function onOutside(event) {
    if (trigger.value?.contains(event.target) || popup.value?.contains(event.target)) {
        return;
    }

    closeMenu();
}

function onScrollOrResize(event) {
    // Scrolling the option list itself must not close it.
    if (popup.value?.contains(event.target)) {
        return;
    }

    closeMenu();
}

watch(open, (isOpen) => {
    const method = isOpen ? 'addEventListener' : 'removeEventListener';

    document[method]('pointerdown', onOutside, true);
    window[method]('scroll', onScrollOrResize, true);
    window[method]('resize', onScrollOrResize);
});

onBeforeUnmount(() => {
    document.removeEventListener('pointerdown', onOutside, true);
    window.removeEventListener('scroll', onScrollOrResize, true);
    window.removeEventListener('resize', onScrollOrResize);
    clearTimeout(typedTimer);
});
</script>

<template>
    <div class="crm-sel" :class="[`crm-sel-${variant}`, open ? 'crm-sel-open' : '']">
        <button
            ref="trigger"
            type="button"
            class="crm-sel-trigger"
            role="combobox"
            aria-haspopup="listbox"
            :aria-expanded="open"
            :aria-controls="`${uid}-list`"
            :aria-activedescendant="open && active >= 0 ? optionId(active) : undefined"
            :aria-label="ariaLabel"
            :disabled="disabled"
            @click="open ? closeMenu() : openMenu()"
            @keydown="onKeydown"
        >
            <Icon v-if="icon || selected?.icon" :icon="icon || selected.icon" class="crm-sel-lead" aria-hidden="true" />
            <span class="crm-sel-value" :class="selected ? '' : 'crm-sel-placeholder'">
                {{ selected ? selected.label : placeholder }}
            </span>
            <Icon icon="lucide:chevron-down" class="crm-sel-chevron" aria-hidden="true" />
        </button>

        <Teleport to="body">
            <Transition name="crm-sel-pop">
                <ul
                    v-if="open"
                    :id="`${uid}-list`"
                    ref="popup"
                    class="crm-sel-popup"
                    :class="place.above ? 'crm-sel-popup-above' : ''"
                    role="listbox"
                    :aria-label="ariaLabel"
                    :style="{
                        left: `${place.left}px`,
                        minWidth: `${place.width}px`,
                        maxHeight: `${place.maxHeight}px`,
                        ...(place.above ? { bottom: `${place.bottom}px` } : { top: `${place.top}px` }),
                    }"
                >
                    <li
                        v-for="(option, index) in options"
                        :id="optionId(index)"
                        :key="String(option.value)"
                        role="option"
                        class="crm-sel-option"
                        :class="{
                            'crm-sel-option-active': active === index,
                            'crm-sel-option-selected': selectedIndex === index,
                            'crm-sel-option-disabled': option.disabled,
                        }"
                        :aria-selected="selectedIndex === index"
                        :aria-disabled="option.disabled || undefined"
                        @pointermove="!option.disabled && (active = index)"
                        @click="choose(index)"
                    >
                        <Icon v-if="option.icon" :icon="option.icon" class="crm-sel-option-icon" aria-hidden="true" />
                        <span class="crm-sel-option-text">
                            {{ option.label }}
                            <span v-if="option.hint" class="crm-sel-option-hint">{{ option.hint }}</span>
                        </span>
                        <Icon
                            v-if="selectedIndex === index"
                            icon="lucide:check"
                            class="crm-sel-check"
                            aria-hidden="true"
                        />
                    </li>
                </ul>
            </Transition>
        </Teleport>
    </div>
</template>
