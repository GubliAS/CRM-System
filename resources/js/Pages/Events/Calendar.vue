<script setup>
import CrmSelect from '@/Components/CrmSelect.vue';
import EventPopup from '@/Components/EventPopup.vue';
import MiniCalendar from '@/Components/MiniCalendar.vue';
import PaginationBar from '@/Components/PaginationBar.vue';
import TableHeadIcon from '@/Components/TableHeadIcon.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    CALENDAR_TYPES,
    COLOR_MODES,
    DAY_NAMES,
    MONTH_NAMES,
    addDays,
    addMonths,
    calendarOf,
    categoriesFor,
    categoryOf,
    durationLabel,
    formatClock,
    formatDateLabel,
    hourLabel,
    initials,
    layoutDay,
    monthCells,
    occursOn,
    parseYmd,
    rescheduled,
    titleFor,
    visibleRange,
    weekDays,
    ymd,
} from '@/calendar/helpers';
import { display, formatDay } from '@/display';
import { Icon } from '@iconify/vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = defineProps({
    events: { type: Array, required: true },
    list: { type: Object, default: null },
    filters: { type: Object, required: true },
    title: { type: String, required: true },
    today: { type: String, required: true },
    days: { type: Array, required: true },
});

const VIEWS = [
    { key: 'day', label: 'Day', icon: 'lucide:calendar-1' },
    { key: 'week', label: 'Week', icon: 'lucide:calendar-range' },
    { key: 'month', label: 'Month', icon: 'lucide:calendar-days' },
    { key: 'list', label: 'List', icon: 'lucide:list' },
];
const HOUR_H = 56;
const HOURS = Array.from({ length: 24 }, (_, hour) => hour);
const PER_PAGE_OPTIONS = [25, 50, 100, 200].map((n) => ({ value: n, label: `${n} / page` }));

/* ── State: the browser owns view + date; the server only supplies events ── */

const view = ref(props.filters.view);
const cursor = ref(props.filters.date);
const selected = ref(props.filters.date);
const loading = ref(false);
const query = ref('');
const popupId = ref(null);
const timeGrid = ref(null);
const monthPickerOpen = ref(false);
const pickerYear = ref(parseYmd(props.filters.date).getFullYear());
const pickerRoot = ref(null);
const overrides = ref({});
const now = ref(new Date());
let clock = null;
let loadTimer = null;
let suppressClick = false;

const store = {
    get(key, fallback) {
        try {
            return JSON.parse(localStorage.getItem(key)) ?? fallback;
        } catch {
            return fallback;
        }
    },
    set(key, value) {
        try {
            localStorage.setItem(key, JSON.stringify(value));
        } catch {
            // Blocked storage only means the choice is not remembered.
        }
    },
};

const colorBy = ref(store.get('crm-cal-colorby', 'calendar'));
const hidden = ref(new Set(store.get('crm-cal-hidden', [])));

watch(colorBy, (value) => store.set('crm-cal-colorby', value));

function toggleCalendar(key) {
    const next = new Set(hidden.value);
    next.has(key) ? next.delete(key) : next.add(key);
    hidden.value = next;
    store.set('crm-cal-hidden', [...next]);
}

/* ── Loading: previous/current/next month arrive together (see EventController) ── */

const range = computed(() => visibleRange(view.value, cursor.value));

function covered() {
    const { loaded_from: from, loaded_to: to } = props.filters;

    return !!from && range.value[0] >= from && range.value[1] <= to;
}

function ensureLoaded() {
    clearTimeout(loadTimer);

    const serverMonth = props.filters.date.slice(0, 7);
    const listStale = view.value === 'list' && (props.list === null || serverMonth !== cursor.value.slice(0, 7));
    const gridStale = view.value !== 'list' && !covered();
    // Still inside the loaded window, but re-centre it so the next click stays instant.
    const recentre = view.value !== 'list' && serverMonth !== cursor.value.slice(0, 7);

    if (!listStale && !gridStale && !recentre) {
        return;
    }

    const blocking = listStale || gridStale;

    loadTimer = setTimeout(() => {
        router.get(
            route('events.index'),
            { view: view.value, date: cursor.value, per_page: props.filters.per_page },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                onStart: () => {
                    loading.value = blocking;
                },
                onFinish: () => {
                    loading.value = false;
                },
            },
        );
    }, 180);
}

function syncUrl() {
    const url = `${location.pathname}?view=${view.value}&date=${cursor.value}${
        view.value === 'list' ? `&per_page=${props.filters.per_page}` : ''
    }`;

    window.history.replaceState(window.history.state, '', url);
}

function go({ date = cursor.value, to = view.value, pick = true } = {}) {
    cursor.value = date;
    view.value = to;

    if (pick) {
        selected.value = date;
    }

    syncUrl();
    ensureLoaded();
}

const shift = (step) => {
    const unit = view.value === 'day' ? addDays(cursor.value, step) : view.value === 'week' ? addDays(cursor.value, step * 7) : addMonths(cursor.value, step);

    go({ date: unit });
};

const goToday = () => go({ date: props.today });

function setPerPage(value) {
    router.get(
        route('events.index'),
        { view: 'list', date: cursor.value, per_page: value },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

/* ── Data the views draw from ── */

const allEvents = computed(() =>
    props.events.map((event) => (overrides.value[event.id] ? { ...event, ...overrides.value[event.id] } : event)),
);

watch(
    () => props.events,
    () => {
        overrides.value = {};
    },
);

const terms = computed(() => query.value.trim().toLowerCase().split(/\s+/).filter(Boolean));

function matches(event) {
    if (hidden.value.has(calendarOf(event))) {
        return false;
    }

    if (terms.value.length === 0) {
        return true;
    }

    const haystack = [event.subject, event.location, event.related_label, event.contact_name, event.assignee, event.description]
        .filter(Boolean)
        .join(' ')
        .toLowerCase();

    return terms.value.every((term) => haystack.includes(term));
}

const events = computed(() => allEvents.value.filter(matches));
const listRows = computed(() => (props.list?.data ?? []).filter(matches));

const byDate = computed(() => {
    const map = new Map();
    const dates = new Set([selected.value]);
    const [from, to] = range.value;

    for (let d = from; d <= to; d = addDays(d, 1)) {
        dates.add(d);
    }

    for (const date of dates) {
        map.set(
            date,
            events.value
                .filter((event) => occursOn(event, date))
                .sort((a, b) => Number(b.all_day) - Number(a.all_day) || a.starts_at.localeCompare(b.starts_at) || a.id - b.id),
        );
    }

    return map;
});

const on = (date) => byDate.value.get(date) ?? [];
const monthGrid = computed(() => monthCells(cursor.value));
const columns = computed(() => (view.value === 'day' ? [{ date: cursor.value, day: parseYmd(cursor.value).getDate(), weekday: DAY_NAMES[parseYmd(cursor.value).getDay()].slice(0, 3) }] : weekDays(cursor.value)));
const markedDays = computed(() => new Set(events.value.flatMap((event) => {
    const out = [];

    for (let d = event.starts_at.slice(0, 10); d <= event.ends_at.slice(0, 10) && out.length < 62; d = addDays(d, 1)) {
        out.push(d);
    }

    return out;
})));

const inRangeEvents = computed(() =>
    events.value.filter((event) => event.starts_at.slice(0, 10) <= range.value[1] && event.ends_at.slice(0, 10) >= range.value[0]),
);

const legend = computed(() =>
    categoriesFor(colorBy.value).map((item) => ({
        ...item,
        count: inRangeEvents.value.filter((event) => categoryOf(event, colorBy.value).key === item.key).length,
    })),
);

const calendarCounts = computed(() =>
    Object.fromEntries(
        CALENDAR_TYPES.map((type) => [
            type.key,
            allEvents.value.filter((event) => calendarOf(event) === type.key && event.starts_at.slice(0, 10) <= range.value[1] && event.ends_at.slice(0, 10) >= range.value[0]).length,
        ]),
    ),
);

const colorOf = (event) => categoryOf(event, colorBy.value).color;
const popupEvent = computed(() => [...allEvents.value, ...(props.list?.data ?? [])].find((event) => event.id === popupId.value) ?? null);
const calendarLabel = computed(() => (popupEvent.value ? categoryOf(popupEvent.value, 'calendar').label : ''));

const headline = computed(() => titleFor(view.value, cursor.value));
const greeting = computed(() => {
    const h = now.value.getHours();

    return h < 12 ? 'Good morning!' : h < 17 ? 'Good afternoon!' : 'Good evening!';
});
const todayCount = computed(() => allEvents.value.filter((event) => occursOn(event, props.today) && !hidden.value.has(calendarOf(event))).length);
const subtitle = computed(() =>
    props.filters.loaded_from
        ? `${greeting.value} ${todayCount.value === 0 ? 'Nothing on your calendar today.' : `You have ${todayCount.value} ${todayCount.value === 1 ? 'event' : 'events'} today.`}`
        : `${greeting.value} Here is what is on your calendar.`,
);

/* ── Small formatters ── */

function chipTime(event) {
    if (event.all_day) {
        return '';
    }

    const h = Number(event.starts_at.slice(11, 13));
    const m = event.starts_at.slice(14, 16);

    return `${h % 12 === 0 ? 12 : h % 12}${m === '00' ? '' : `:${m}`}${h < 12 ? 'a' : 'p'} `;
}

const timeRange = (event) => (event.all_day ? 'All day' : `${formatClock(event.starts_at)} – ${formatClock(event.ends_at)}`);

/* ── Actions ── */

function createAt(date, hour = null) {
    if (suppressClick) {
        suppressClick = false;

        return;
    }

    if (hour === null) {
        router.get(route('events.create'), { starts_at: `${date} 00:00:00`, all_day: 1 });

        return;
    }

    router.get(route('events.create'), { starts_at: `${date} ${String(hour).padStart(2, '0')}:00:00`, all_day: 0 });
}

function pickMonth(index) {
    go({ date: ymd(new Date(pickerYear.value, index, 1)) });
    monthPickerOpen.value = false;
}

function toggleMonthPicker() {
    pickerYear.value = parseYmd(cursor.value).getFullYear();
    monthPickerOpen.value = !monthPickerOpen.value;
}

function onDragStart(domEvent, item) {
    if (!item.can_update) {
        domEvent.preventDefault();

        return;
    }

    domEvent.dataTransfer.setData('text/plain', String(item.id));
    domEvent.dataTransfer.effectAllowed = 'move';
}

function onDrop(domEvent, date, hour = null) {
    domEvent.preventDefault();
    suppressClick = true;
    const item = allEvents.value.find((event) => String(event.id) === domEvent.dataTransfer.getData('text/plain'));

    if (!item || !item.can_update) {
        return;
    }

    const next = rescheduled(item, date, hour);

    if (next.starts_at === item.starts_at) {
        return;
    }

    // Show the move at once; the server's answer replaces it (or undoes it on error).
    overrides.value = { ...overrides.value, [item.id]: next };
    router.patch(
        route('events.reschedule', item.id),
        { starts_at: next.starts_at },
        {
            preserveScroll: true,
            preserveState: true,
            onError: () => {
                const { [item.id]: _undo, ...rest } = overrides.value;
                overrides.value = rest;
            },
        },
    );
}

/* ── Lifecycle ── */

function closePicker(event) {
    if (monthPickerOpen.value && !pickerRoot.value?.contains(event.target)) {
        monthPickerOpen.value = false;
    }
}

function scrollToWork() {
    nextTick(() => {
        if (timeGrid.value) {
            timeGrid.value.scrollTop = HOUR_H * 7.5;
        }
    });
}

watch(view, (value) => {
    if (value === 'day' || value === 'week') {
        scrollToWork();
    }
});

onMounted(() => {
    document.addEventListener('mousedown', closePicker);
    clock = setInterval(() => (now.value = new Date()), 60_000);
    scrollToWork();
    syncUrl();
});

onBeforeUnmount(() => {
    document.removeEventListener('mousedown', closePicker);
    clearInterval(clock);
    clearTimeout(loadTimer);
});

const nowTop = computed(() => ((now.value.getHours() * 60 + now.value.getMinutes()) / 60) * HOUR_H);
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Calendar" />

        <template #header>
            <div class="min-w-0">
                <h1 class="crm-page-title">Calendar</h1>
                <p class="crm-page-subtitle truncate">{{ subtitle }}</p>
            </div>
        </template>

        <div class="crm-page max-w-none px-4 py-4 sm:px-5">
            <!-- Toolbar -->
            <div class="crm-cal-bar">
                <div class="crm-cal-bar-left">
                    <div ref="pickerRoot" class="crm-cal-picker">
                        <button
                            type="button"
                            class="crm-cal-month"
                            :aria-expanded="monthPickerOpen"
                            aria-haspopup="dialog"
                            title="Pick a month"
                            @click="toggleMonthPicker"
                        >
                            <span class="crm-cal-month-name">{{ headline }}</span>
                            <Icon icon="lucide:chevron-down" class="crm-cal-month-chev" :class="monthPickerOpen ? 'is-open' : ''" aria-hidden="true" />
                        </button>

                        <div v-if="monthPickerOpen" class="crm-cal-panel" role="dialog" aria-label="Pick a month">
                            <div class="crm-cal-panel-year">
                                <button type="button" class="crm-mini-btn" aria-label="Previous year" @click="pickerYear--">
                                    <Icon icon="lucide:chevron-left" />
                                </button>
                                <span>{{ pickerYear }}</span>
                                <button type="button" class="crm-mini-btn" aria-label="Next year" @click="pickerYear++">
                                    <Icon icon="lucide:chevron-right" />
                                </button>
                            </div>
                            <div class="crm-cal-panel-grid">
                                <button
                                    v-for="(name, index) in MONTH_NAMES"
                                    :key="name"
                                    type="button"
                                    class="crm-cal-panel-opt"
                                    :class="index === parseYmd(cursor).getMonth() && pickerYear === parseYmd(cursor).getFullYear() ? 'is-active' : ''"
                                    @click="pickMonth(index)"
                                >
                                    {{ name.slice(0, 3) }}
                                </button>
                            </div>
                            <button type="button" class="crm-cal-panel-today" @click="goToday(); monthPickerOpen = false">
                                Jump to today
                            </button>
                        </div>
                    </div>

                    <div class="crm-cal-nav">
                        <button type="button" class="crm-cal-navbtn" aria-label="Previous" @click="shift(-1)">
                            <Icon icon="lucide:chevron-left" />
                        </button>
                        <button type="button" class="crm-cal-navbtn" aria-label="Next" @click="shift(1)">
                            <Icon icon="lucide:chevron-right" />
                        </button>
                    </div>
                    <button type="button" class="crm-cal-today" @click="goToday">Today</button>
                    <Icon v-if="loading" icon="lucide:loader-circle" class="crm-db-spin crm-cal-spinner" aria-label="Loading" />
                </div>

                <div class="crm-cal-toggle" role="tablist" aria-label="Calendar view">
                    <button
                        v-for="item in VIEWS"
                        :key="item.key"
                        type="button"
                        role="tab"
                        class="crm-cal-toggle-btn"
                        :class="view === item.key ? 'is-active' : ''"
                        :aria-selected="view === item.key"
                        @click="go({ to: item.key, pick: false })"
                    >
                        <Icon :icon="item.icon" aria-hidden="true" />
                        {{ item.label }}
                    </button>
                </div>

                <div class="crm-cal-bar-right">
                    <label class="crm-db-search crm-cal-search">
                        <Icon icon="lucide:search" aria-hidden="true" />
                        <input v-model="query" type="search" placeholder="Search events…" aria-label="Search events" />
                    </label>
                    <Link :href="route('events.create')" class="crm-db-new">
                        <Icon icon="lucide:plus" aria-hidden="true" />
                        New event
                    </Link>
                </div>
            </div>

            <div class="crm-cal-body" :class="loading ? 'crm-cal-loading' : ''" :aria-busy="loading">
                <!-- Main -->
                <section class="crm-cal-main">
                    <!-- Month -->
                    <div v-if="view === 'month'" class="crm-cal-card">
                        <div class="crm-cal-dow">
                            <div v-for="name in DAY_NAMES" :key="name" class="crm-cal-dow-cell" :data-abbr="name.slice(0, 3)">{{ name }}</div>
                        </div>
                        <div class="crm-cal-grid">
                            <div
                                v-for="cell in monthGrid"
                                :key="cell.date"
                                role="button"
                                tabindex="0"
                                class="crm-cal-cell"
                                :class="{
                                    'crm-cal-cell-out': !cell.inMonth,
                                    'crm-cal-cell-today': cell.date === today,
                                    'crm-cal-cell-selected': cell.date === selected,
                                }"
                                :aria-label="`${formatDateLabel(cell.date)}, ${on(cell.date).length} events`"
                                :aria-pressed="cell.date === selected"
                                @click="selected = cell.date"
                                @keydown.enter.self="selected = cell.date"
                                @dragover.prevent
                                @drop="onDrop($event, cell.date)"
                            >
                                <div class="crm-cal-cell-top">
                                    <span class="crm-cal-num" :class="cell.date === today ? 'is-today' : ''">{{ cell.day }}</span>
                                    <button
                                        type="button"
                                        class="crm-cal-add"
                                        :aria-label="`Add event on ${formatDateLabel(cell.date)}`"
                                        @click.stop="createAt(cell.date)"
                                    >
                                        <Icon icon="lucide:plus" />
                                    </button>
                                </div>
                                <div class="crm-cal-chips">
                                    <button
                                        v-for="event in on(cell.date).slice(0, 3)"
                                        :key="event.id"
                                        type="button"
                                        class="crm-cal-chip"
                                        :class="event.all_day ? 'is-allday' : ''"
                                        :style="{ '--c': colorOf(event) }"
                                        :draggable="event.can_update"
                                        :title="`${event.subject} · ${timeRange(event)}`"
                                        @click.stop="popupId = event.id"
                                        @dragstart="onDragStart($event, event)"
                                    >
                                        <span class="crm-cal-chip-time">{{ chipTime(event) }}</span>{{ event.subject }}
                                    </button>
                                    <button
                                        v-if="on(cell.date).length > 3"
                                        type="button"
                                        class="crm-cal-more"
                                        @click.stop="selected = cell.date"
                                    >
                                        +{{ on(cell.date).length - 3 }} more
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Week / Day -->
                    <div v-else-if="view === 'week' || view === 'day'" class="crm-cal-card crm-cal-time" :style="{ '--cols': columns.length }">
                        <div class="crm-cal-time-head">
                            <div class="crm-cal-gutter-head" />
                            <div
                                v-for="col in columns"
                                :key="col.date"
                                class="crm-cal-colhead"
                                :class="col.date === today ? 'is-today' : ''"
                            >
                                <span class="crm-cal-colhead-day">{{ col.weekday }}</span>
                                <span class="crm-cal-colhead-num">{{ col.day }}</span>
                            </div>
                        </div>

                        <div class="crm-cal-allday">
                            <div class="crm-cal-gutter-head crm-cal-allday-label">All day</div>
                            <div
                                v-for="col in columns"
                                :key="col.date"
                                class="crm-cal-allday-cell"
                                @dragover.prevent
                                @drop="onDrop($event, col.date, null)"
                                @click="createAt(col.date)"
                            >
                                <button
                                    v-for="event in on(col.date).filter((e) => e.all_day)"
                                    :key="event.id"
                                    type="button"
                                    class="crm-cal-chip is-allday"
                                    :style="{ '--c': colorOf(event) }"
                                    :draggable="event.can_update"
                                    @click.stop="popupId = event.id"
                                    @dragstart="onDragStart($event, event)"
                                >
                                    {{ event.subject }}
                                </button>
                            </div>
                        </div>

                        <div ref="timeGrid" class="crm-cal-scroll">
                            <div class="crm-cal-hours">
                                <div class="crm-cal-gutter">
                                    <div v-for="hour in HOURS" :key="hour" class="crm-cal-hour-label" :style="{ height: `${HOUR_H}px` }">
                                        <span v-if="hour > 0">{{ hourLabel(hour) }}</span>
                                    </div>
                                </div>
                                <div v-for="col in columns" :key="col.date" class="crm-cal-daycol" :style="{ height: `${HOUR_H * 24}px` }">
                                    <div
                                        v-for="hour in HOURS"
                                        :key="hour"
                                        class="crm-cal-slot"
                                        :style="{ height: `${HOUR_H}px` }"
                                        @dragover.prevent
                                        @drop="onDrop($event, col.date, hour)"
                                        @click="createAt(col.date, hour)"
                                    />
                                    <button
                                        v-for="item in layoutDay(on(col.date), col.date)"
                                        :key="item.event.id"
                                        type="button"
                                        class="crm-cal-event"
                                        :style="{
                                            '--c': colorOf(item.event),
                                            top: `${(item.start / 60) * HOUR_H}px`,
                                            height: `${Math.max(((item.end - item.start) / 60) * HOUR_H - 2, 22)}px`,
                                            left: `${(item.lane / item.lanes) * 100}%`,
                                            width: `calc(${100 / item.lanes}% - 3px)`,
                                        }"
                                        :draggable="item.event.can_update"
                                        @click.stop="popupId = item.event.id"
                                        @dragstart="onDragStart($event, item.event)"
                                    >
                                        <span class="crm-cal-event-title">{{ item.event.subject }}</span>
                                        <span v-if="item.end - item.start >= 45" class="crm-cal-event-time">{{ timeRange(item.event) }}</span>
                                    </button>
                                    <span v-if="col.date === today" class="crm-cal-now" :style="{ top: `${nowTop}px` }" aria-hidden="true" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- List -->
                    <div v-else class="crm-cal-card crm-cal-listcard">
                        <div class="crm-ov-tablewrap crm-cal-listwrap">
                            <table class="crm-ov-table">
                                <thead>
                                    <tr>
                                        <th scope="col"><span class="crm-ov-th"><TableHeadIcon name="id" />Subject</span></th>
                                        <th scope="col"><span class="crm-ov-th"><TableHeadIcon name="due" />Start</span></th>
                                        <th scope="col">End</th>
                                        <th scope="col"><span class="crm-ov-th"><TableHeadIcon name="client" />Related to</span></th>
                                        <th scope="col">Location</th>
                                        <th scope="col"><span class="crm-ov-th"><TableHeadIcon name="status" />Show time as</span></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="!list || listRows.length === 0">
                                        <td colspan="6" class="crm-cal-empty-row">
                                            {{ list ? 'No events match in this month.' : 'Loading events…' }}
                                        </td>
                                    </tr>
                                    <tr v-for="event in listRows" :key="event.id" class="crm-ov-row-link" @click="popupId = event.id">
                                        <td class="crm-ov-cell-strong">
                                            <span class="crm-cal-swatch" :style="{ background: colorOf(event) }" />{{ event.subject }}
                                        </td>
                                        <td>{{ event.all_day ? `${formatDay(event.starts_at)} · All day` : `${formatDay(event.starts_at)} ${formatClock(event.starts_at)}` }}</td>
                                        <td>{{ event.all_day ? formatDay(event.ends_at) : `${formatDay(event.ends_at)} ${formatClock(event.ends_at)}` }}</td>
                                        <td>
                                            <Link v-if="event.related_href" :href="event.related_href" class="crm-ov-cell-link" @click.stop>
                                                {{ event.related_label }}
                                            </Link>
                                            <template v-else>{{ display(event.related_label) }}</template>
                                        </td>
                                        <td>{{ display(event.location) }}</td>
                                        <td>{{ display(event.show_time_as) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div v-if="list" class="crm-cal-listfoot">
                            <PaginationBar :paginator="list" />
                            <CrmSelect
                                variant="pill"
                                align="end"
                                aria-label="Rows per page"
                                :model-value="filters.per_page"
                                :options="PER_PAGE_OPTIONS"
                                @update:model-value="setPerPage"
                            />
                        </div>
                    </div>
                </section>

                <!-- Sidebar -->
                <aside class="crm-cal-side" aria-label="Calendar tools">
                    <div class="crm-cal-side-card">
                        <MiniCalendar
                            :model-value="cursor"
                            :today="today"
                            :marked="markedDays"
                            :range-from="view === 'list' ? '' : range[0]"
                            :range-to="view === 'list' ? '' : range[1]"
                            @select="go({ date: $event })"
                        />
                    </div>

                    <div class="crm-cal-side-card">
                        <h2 class="crm-cal-side-title">My calendars</h2>
                        <ul class="crm-cal-cals" role="list">
                            <li v-for="type in CALENDAR_TYPES" :key="type.key">
                                <label class="crm-cal-check">
                                    <input type="checkbox" :checked="!hidden.has(type.key)" @change="toggleCalendar(type.key)" />
                                    <span class="crm-cal-check-box" :style="{ '--c': type.color }" aria-hidden="true">
                                        <Icon icon="lucide:check" />
                                    </span>
                                    <span class="crm-cal-check-label">{{ type.label }}</span>
                                    <span class="crm-cal-count tabular-nums">{{ calendarCounts[type.key] }}</span>
                                </label>
                            </li>
                        </ul>

                        <p class="crm-cal-side-sub">Colour events by</p>
                        <div class="crm-cal-seg" role="radiogroup" aria-label="Colour events by">
                            <button
                                v-for="mode in COLOR_MODES"
                                :key="mode.key"
                                type="button"
                                role="radio"
                                :aria-checked="colorBy === mode.key"
                                class="crm-cal-seg-btn"
                                :class="colorBy === mode.key ? 'is-active' : ''"
                                @click="colorBy = mode.key"
                            >
                                {{ mode.label }}
                            </button>
                        </div>
                        <ul class="crm-cal-legend" role="list">
                            <li v-for="item in legend" :key="item.key">
                                <span class="crm-cal-swatch" :style="{ background: item.color }" />
                                {{ item.label }}
                                <span class="crm-cal-count tabular-nums">{{ item.count }}</span>
                            </li>
                        </ul>
                    </div>

                    <div v-if="view !== 'day' && view !== 'list'" class="crm-cal-side-card crm-cal-agenda">
                        <div class="crm-cal-agenda-head">
                            <div class="min-w-0">
                                <h2 class="crm-cal-side-title">Scheduled</h2>
                                <p class="crm-cal-agenda-date">{{ formatDateLabel(selected) }}</p>
                            </div>
                            <div class="crm-cal-agenda-nav">
                                <button type="button" class="crm-mini-btn" aria-label="Previous day" @click="selected = addDays(selected, -1)">
                                    <Icon icon="lucide:chevron-left" />
                                </button>
                                <button type="button" class="crm-mini-btn" aria-label="Next day" @click="selected = addDays(selected, 1)">
                                    <Icon icon="lucide:chevron-right" />
                                </button>
                                <button type="button" class="crm-mini-btn crm-mini-btn-add" aria-label="Add event on this day" @click="createAt(selected)">
                                    <Icon icon="lucide:plus" />
                                </button>
                            </div>
                        </div>

                        <div v-if="on(selected).length === 0" class="crm-cal-agenda-empty">
                            <Icon icon="lucide:calendar-days" aria-hidden="true" />
                            <span>{{ terms.length ? 'No matching events on this day.' : 'No events scheduled for this day.' }}</span>
                            <button type="button" class="crm-db-ghost" @click="createAt(selected)">
                                <Icon icon="lucide:plus" aria-hidden="true" /> Add event
                            </button>
                        </div>

                        <ul v-else class="crm-cal-agenda-list" role="list">
                            <li v-for="event in on(selected)" :key="event.id" class="crm-cal-slotrow">
                                <span class="crm-cal-slottime">{{ event.all_day ? 'All day' : formatClock(event.starts_at).replace(' ', ' ') }}</span>
                                <button type="button" class="crm-cal-card-event" @click="popupId = event.id">
                                    <span class="crm-cal-accent" :style="{ background: colorOf(event) }" />
                                    <span class="crm-cal-card-body">
                                        <span class="crm-cal-card-title">{{ event.subject }}</span>
                                        <span class="crm-cal-card-meta">
                                            {{ timeRange(event) }}
                                            <span class="crm-cal-pill">{{ durationLabel(event) }}</span>
                                        </span>
                                        <span v-if="event.location" class="crm-cal-card-line">
                                            <Icon icon="lucide:map-pin" aria-hidden="true" />{{ event.location }}
                                        </span>
                                        <span v-if="event.related_label" class="crm-cal-card-line">
                                            <Icon icon="lucide:link-2" aria-hidden="true" />{{ event.related_label }}
                                        </span>
                                        <span class="crm-cal-card-foot">
                                            <span class="crm-cal-avatar" :style="{ background: colorOf(event) }" :title="event.assignee">
                                                {{ initials(event.assignee) }}
                                            </span>
                                            <Icon v-if="event.is_private" icon="lucide:lock" class="crm-cal-lock" aria-label="Private" />
                                        </span>
                                    </span>
                                </button>
                            </li>
                        </ul>
                    </div>
                </aside>
            </div>
        </div>

        <EventPopup
            :event="popupEvent"
            :color="popupEvent ? colorOf(popupEvent) : '#0176d3'"
            :calendar-label="calendarLabel"
            @close="popupId = null"
        />
    </AuthenticatedLayout>
</template>
