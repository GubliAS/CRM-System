<script setup>
/* Month mini-calendar for quick date jumping (FR-CAL-001). */
import { DAY_NAMES, MONTH_NAMES, addMonths, monthCells, parseYmd } from '@/calendar/helpers';
import { Icon } from '@iconify/vue';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    /** Date the calendar is showing (YYYY-MM-DD). */
    modelValue: { type: String, required: true },
    today: { type: String, required: true },
    /** Dates that have at least one event; shown as a dot. */
    marked: { type: Object, default: () => new Set() },
    /** The visible week or day range, highlighted. */
    rangeFrom: { type: String, default: '' },
    rangeTo: { type: String, default: '' },
});

const emit = defineEmits(['select']);

// The mini calendar can be browsed on its own; it follows the main one when that moves.
const shown = ref(props.modelValue);

watch(
    () => props.modelValue,
    (value) => {
        if (value.slice(0, 7) !== shown.value.slice(0, 7)) {
            shown.value = value;
        }
    },
);

const cells = computed(() => monthCells(shown.value));
const title = computed(() => {
    const d = parseYmd(shown.value);

    return `${MONTH_NAMES[d.getMonth()]} ${d.getFullYear()}`;
});

const inRange = (date) => props.rangeFrom && date >= props.rangeFrom && date <= props.rangeTo;
</script>

<template>
    <div class="crm-mini" role="group" :aria-label="`Mini calendar, ${title}`">
        <div class="crm-mini-head">
            <span class="crm-mini-title">{{ title }}</span>
            <div class="crm-mini-nav">
                <button type="button" class="crm-mini-btn" aria-label="Previous month" @click="shown = addMonths(shown, -1)">
                    <Icon icon="lucide:chevron-left" />
                </button>
                <button type="button" class="crm-mini-btn" aria-label="Next month" @click="shown = addMonths(shown, 1)">
                    <Icon icon="lucide:chevron-right" />
                </button>
            </div>
        </div>

        <div class="crm-mini-grid">
            <span v-for="name in DAY_NAMES" :key="name" class="crm-mini-dow" aria-hidden="true">{{ name[0] }}</span>
            <button
                v-for="cell in cells"
                :key="cell.date"
                type="button"
                class="crm-mini-day"
                :class="{
                    'crm-mini-day-out': !cell.inMonth,
                    'crm-mini-day-today': cell.date === today,
                    'crm-mini-day-active': cell.date === modelValue,
                    'crm-mini-day-range': inRange(cell.date) && cell.date !== modelValue,
                }"
                :aria-label="cell.date"
                :aria-current="cell.date === today ? 'date' : undefined"
                @click="emit('select', cell.date)"
            >
                {{ cell.day }}
                <span v-if="marked.has(cell.date)" class="crm-mini-dot" aria-hidden="true" />
            </button>
        </div>
    </div>
</template>
