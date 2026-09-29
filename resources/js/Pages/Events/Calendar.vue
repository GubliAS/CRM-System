<script setup>
import DangerButton from '@/Components/DangerButton.vue';
import Modal from '@/Components/Modal.vue';
import PaginationBar from '@/Components/PaginationBar.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { display, formatDay } from '@/display';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

const props = defineProps({
    events: {
        type: Array,
        required: true,
    },
    list: {
        type: Object,
        default: null,
    },
    filters: {
        type: Object,
        required: true,
    },
    title: {
        type: String,
        required: true,
    },
    today: {
        type: String,
        required: true,
    },
    days: {
        type: Array,
        required: true,
    },
});

const views = [
    { key: 'day', label: 'Day' },
    { key: 'week', label: 'Week' },
    { key: 'month', label: 'Month' },
    { key: 'list', label: 'List' },
];

const hours = Array.from({ length: 24 }, (_, hour) => hour);
const selected = ref(null);
const confirming = ref(false);
const suppressClick = ref(false);
const timeGrid = ref(null);
const deleteForm = useForm({});

onMounted(() => {
    const row = timeGrid.value?.querySelector('[data-hour="8"]');

    if (timeGrid.value && row) {
        timeGrid.value.scrollTop = row.offsetTop;
    }
});

function visit(extra = {}) {
    router.get(
        route('events.index'),
        {
            view: props.filters.view,
            date: props.filters.date,
            per_page: props.filters.per_page,
            ...extra,
        },
        { preserveState: true, replace: true },
    );
}

function changeView(view) {
    visit({ view });
}

function jump(event) {
    if (event.target.value) {
        visit({ date: event.target.value });
    }
}

function shift(step) {
    const [year, month, day] = props.filters.date.split('-').map(Number);
    const date = new Date(year, month - 1, day);

    if (props.filters.view === 'day') {
        date.setDate(date.getDate() + step);
    } else if (props.filters.view === 'week') {
        date.setDate(date.getDate() + step * 7);
    } else {
        date.setMonth(date.getMonth() + step);
    }

    const next = [
        date.getFullYear(),
        String(date.getMonth() + 1).padStart(2, '0'),
        String(date.getDate()).padStart(2, '0'),
    ].join('-');

    visit({ date: next });
}

function changePerPage(event) {
    visit({ per_page: event.target.value });
}

function hourLabel(hour) {
    const suffix = hour < 12 ? 'AM' : 'PM';
    const value = hour % 12 === 0 ? 12 : hour % 12;

    return `${value} ${suffix}`;
}

function allDayOn(date) {
    return props.events.filter((item) => {
        if (!item.all_day) {
            return false;
        }

        const start = item.starts_at.slice(0, 10);
        const end = item.ends_at.slice(0, 10);

        return date >= start && date <= end;
    });
}

function timedAt(date, hour) {
    return props.events.filter((item) => {
        if (item.all_day) {
            return false;
        }

        return item.starts_at.slice(0, 10) === date && Number(item.starts_at.slice(11, 13)) === hour;
    });
}

function monthItems(date) {
    return props.events.filter((item) => {
        const start = item.starts_at.slice(0, 10);
        const end = item.ends_at.slice(0, 10);

        if (item.all_day) {
            return date >= start && date <= end;
        }

        return date === start;
    });
}

function chipLabel(item) {
    if (item.all_day) {
        return `All day · ${item.subject}`;
    }

    return `${item.starts_at.slice(11, 16)} ${item.subject}`;
}

function whenLabel(item) {
    if (!item) {
        return '';
    }

    if (item.all_day) {
        const start = formatDay(item.starts_at);
        const end = formatDay(item.ends_at);

        return start === end ? `${start} · All day` : `${start} – ${end} · All day`;
    }

    return `${formatDay(item.starts_at)} ${item.starts_at.slice(11, 16)} – ${item.ends_at.slice(11, 16)}`;
}

function createAt(date, hour) {
    if (suppressClick.value) {
        suppressClick.value = false;

        return;
    }

    if (hour === null) {
        router.get(route('events.create'), { starts_at: `${date} 00:00:00`, all_day: 1 });

        return;
    }

    const hh = String(hour).padStart(2, '0');
    router.get(route('events.create'), { starts_at: `${date} ${hh}:00:00`, all_day: 0 });
}

function openEvent(item) {
    confirming.value = false;
    selected.value = item;
}

function onDragStart(domEvent, item) {
    if (!item.can_update) {
        domEvent.preventDefault();

        return;
    }

    domEvent.dataTransfer.setData('text/plain', String(item.id));
    domEvent.dataTransfer.effectAllowed = 'move';
}

function onDrop(domEvent, date, hour) {
    domEvent.preventDefault();
    suppressClick.value = true;
    const id = domEvent.dataTransfer.getData('text/plain');
    const item = props.events.find((event) => String(event.id) === id);

    if (!item || !item.can_update) {
        return;
    }

    let startsAt = `${date} 00:00:00`;

    if (!item.all_day && hour === null) {
        startsAt = `${date} ${item.starts_at.slice(11, 19)}`;
    }

    if (!item.all_day && hour !== null) {
        startsAt = `${date} ${String(hour).padStart(2, '0')}:00:00`;
    }

    router.patch(route('events.reschedule', item.id), { starts_at: startsAt }, { preserveScroll: true });
}

function destroySelected() {
    if (!selected.value) {
        return;
    }

    deleteForm.delete(route('events.destroy', selected.value.id), {
        onSuccess: () => {
            selected.value = null;
            confirming.value = false;
        },
    });
}
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Calendar" />

        <div class="crm-page">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h1 class="text-h1">Calendar</h1>
                    <p class="mt-1 text-body text-text">{{ title }}</p>
                </div>
                <Link
                    :href="route('events.create')"
                    class="crm-btn-primary"
                >
                    New event
                </Link>
            </div>

            <div class="mt-4 flex flex-wrap items-end gap-2">
                <button
                    v-for="view in views"
                    :key="view.key"
                    type="button"
                    class="crm-view-tab"
                    :class="
                        filters.view === view.key
                            ? 'crm-view-tab-active'
                            : 'crm-view-tab-idle'
                    "
                    @click="changeView(view.key)"
                >
                    {{ view.label }}
                </button>
                <button
                    type="button"
                    class="inline-flex min-h-11 items-center rounded-md border border-border bg-surface px-3 text-small text-text"
                    @click="visit({ date: today })"
                >
                    Today
                </button>
                <button
                    type="button"
                    class="inline-flex min-h-11 items-center rounded-md border border-border bg-surface px-3 text-small text-text"
                    @click="shift(-1)"
                >
                    Previous
                </button>
                <button
                    type="button"
                    class="inline-flex min-h-11 items-center rounded-md border border-border bg-surface px-3 text-small text-text"
                    @click="shift(1)"
                >
                    Next
                </button>
                <div>
                    <label class="text-small text-text-muted" for="calendar-date">Jump to date</label>
                    <input
                        id="calendar-date"
                        type="date"
                        class="mt-1 block min-h-11 rounded-md border-border bg-surface text-body text-text"
                        :value="filters.date"
                        @change="jump"
                    />
                </div>
                <div v-if="filters.view === 'list'">
                    <label class="text-small text-text-muted" for="calendar-per-page">Rows</label>
                    <select
                        id="calendar-per-page"
                        class="mt-1 block min-h-11 rounded-md border-border bg-surface text-body text-text"
                        :value="filters.per_page"
                        @change="changePerPage"
                    >
                        <option :value="25">25</option>
                        <option :value="50">50</option>
                        <option :value="100">100</option>
                        <option :value="200">200</option>
                    </select>
                </div>
            </div>

            <div v-if="filters.view === 'list'" class="mt-4 crm-table-wrap">
                <table class="crm-table">
                    <thead>
                        <tr>
                            <th scope="col" class="px-3 py-2">Subject</th>
                            <th scope="col" class="px-3 py-2">Start</th>
                            <th scope="col" class="px-3 py-2">End</th>
                            <th scope="col" class="px-3 py-2">Related to</th>
                            <th scope="col" class="px-3 py-2">Location</th>
                            <th scope="col" class="px-3 py-2">Show time as</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="!list || list.data.length === 0">
                            <td colspan="6" class="px-4 py-8 text-center text-body text-text-muted">
                                No events in this month.
                            </td>
                        </tr>
                        <tr v-for="item in list?.data ?? []" :key="item.id" class="border-t border-border">
                            <td class="px-3 py-2">
                                <button type="button" class="min-h-11 text-left text-secondary underline" @click="openEvent(item)">
                                    {{ item.subject }}
                                </button>
                            </td>
                            <td class="px-3 py-2">
                                {{ item.all_day ? `${formatDay(item.starts_at)} · All day` : `${formatDay(item.starts_at)} ${item.starts_at.slice(11, 16)}` }}
                            </td>
                            <td class="px-3 py-2">
                                {{ item.all_day ? formatDay(item.ends_at) : `${formatDay(item.ends_at)} ${item.ends_at.slice(11, 16)}` }}
                            </td>
                            <td class="px-3 py-2">
                                <Link v-if="item.related_href" :href="item.related_href" class="text-secondary underline">
                                    {{ item.related_label }}
                                </Link>
                                <span v-else>{{ display(item.related_label) }}</span>
                            </td>
                            <td class="px-3 py-2">{{ display(item.location) }}</td>
                            <td class="px-3 py-2">{{ display(item.show_time_as) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div v-if="filters.view === 'list' && list" class="mt-4">
                <PaginationBar :paginator="list" />
            </div>

            <div v-if="filters.view === 'month'" class="mt-4 overflow-x-auto">
                <div class="min-w-[42rem] overflow-hidden rounded-md border border-border bg-surface">
                    <div class="grid grid-cols-7 bg-bg text-small text-text-muted">
                        <div v-for="name in ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']" :key="name" class="px-2 py-2">
                            {{ name }}
                        </div>
                    </div>
                    <div class="grid grid-cols-7">
                        <div
                            v-for="day in days"
                            :key="day.date"
                            class="min-h-28 border-t border-border p-1"
                            :class="day.date === today ? 'bg-bg' : ''"
                            @dragover.prevent
                            @drop="onDrop($event, day.date, null)"
                            @click="createAt(day.date, null)"
                        >
                            <p class="px-1 text-small" :class="day.in_month ? 'text-text' : 'text-text-muted'">
                                {{ day.day }}
                            </p>
                            <button
                                v-for="item in monthItems(day.date).slice(0, 3)"
                                :key="item.id"
                                type="button"
                                class="crm-chip mt-1 block w-full"
                                :draggable="item.can_update"
                                @click.stop="openEvent(item)"
                                @dragstart="onDragStart($event, item)"
                            >
                                {{ chipLabel(item) }}
                            </button>
                            <p v-if="monthItems(day.date).length > 3" class="px-1 text-small text-text-muted">
                                +{{ monthItems(day.date).length - 3 }} more
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="filters.view === 'day' || filters.view === 'week'" class="mt-4 overflow-x-auto">
                <div
                    class="overflow-hidden rounded-md border border-border bg-surface"
                    :class="filters.view === 'week' ? 'min-w-[48rem]' : ''"
                >
                    <div
                        class="grid bg-bg text-small text-text"
                        :class="filters.view === 'day' ? 'grid-cols-[4.5rem_minmax(0,1fr)]' : 'grid-cols-[4.5rem_repeat(7,minmax(0,1fr))]'"
                    >
                        <div class="px-2 py-2 text-text-muted">All day</div>
                        <div
                            v-for="day in days"
                            :key="day.date"
                            class="border-l border-border px-1 py-1"
                            :class="day.date === today ? 'bg-bg' : ''"
                            @dragover.prevent
                            @drop="onDrop($event, day.date, null)"
                            @click="createAt(day.date, null)"
                        >
                            <p class="text-small text-text">{{ day.weekday }} {{ day.day }}</p>
                            <button
                                v-for="item in allDayOn(day.date)"
                                :key="item.id"
                                type="button"
                                class="crm-chip mt-1 block w-full"
                                :draggable="item.can_update"
                                @click.stop="openEvent(item)"
                                @dragstart="onDragStart($event, item)"
                            >
                                All day · {{ item.subject }}
                            </button>
                        </div>
                    </div>
                    <div ref="timeGrid" class="max-h-[36rem] overflow-y-auto">
                        <div
                            v-for="hour in hours"
                            :key="hour"
                            class="grid border-t border-border"
                            :class="filters.view === 'day' ? 'grid-cols-[4.5rem_minmax(0,1fr)]' : 'grid-cols-[4.5rem_repeat(7,minmax(0,1fr))]'"
                            :data-hour="hour"
                        >
                            <div class="px-2 py-2 text-small text-text-muted">{{ hourLabel(hour) }}</div>
                            <div
                                v-for="day in days"
                                :key="`${day.date}-${hour}`"
                                class="min-h-12 border-l border-border p-1"
                                @dragover.prevent
                                @drop="onDrop($event, day.date, hour)"
                                @click="createAt(day.date, hour)"
                            >
                                <button
                                    v-for="item in timedAt(day.date, hour)"
                                    :key="item.id"
                                    type="button"
                                    class="crm-chip mb-1 block w-full"
                                    :draggable="item.can_update"
                                    @click.stop="openEvent(item)"
                                    @dragstart="onDragStart($event, item)"
                                >
                                    {{ chipLabel(item) }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <Modal :show="selected !== null && !confirming" max-width="lg" @close="selected = null">
            <div v-if="selected" class="p-6">
                <h2 class="break-words text-h2">{{ selected.subject }}</h2>
                <p class="mt-2 text-body text-text-muted">{{ whenLabel(selected) }}</p>
                <dl class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div>
                        <dt class="text-small text-text-muted">Related to</dt>
                        <dd class="text-body text-text">
                            <Link v-if="selected.related_href" :href="selected.related_href" class="text-secondary underline">
                                {{ selected.related_label }}
                            </Link>
                            <span v-else>{{ display(selected.related_label) }}</span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-small text-text-muted">Contact</dt>
                        <dd class="text-body text-text">{{ display(selected.contact_name) }}</dd>
                    </div>
                    <div>
                        <dt class="text-small text-text-muted">Location</dt>
                        <dd class="text-body text-text">{{ display(selected.location) }}</dd>
                    </div>
                    <div>
                        <dt class="text-small text-text-muted">Show time as</dt>
                        <dd class="text-body text-text">{{ display(selected.show_time_as) }}</dd>
                    </div>
                    <div>
                        <dt class="text-small text-text-muted">Assigned to</dt>
                        <dd class="text-body text-text">{{ display(selected.assignee) }}</dd>
                    </div>
                    <div>
                        <dt class="text-small text-text-muted">Private</dt>
                        <dd class="text-body text-text">{{ selected.is_private ? 'Yes' : 'No' }}</dd>
                    </div>
                </dl>
                <p v-if="selected.description" class="mt-4 whitespace-pre-wrap text-body text-text">
                    {{ selected.description }}
                </p>
                <div class="mt-6 flex flex-wrap gap-2">
                    <Link
                        :href="route('events.show', selected.id)"
                        class="crm-btn-secondary"
                    >
                        Open
                    </Link>
                    <Link
                        v-if="selected.can_update"
                        :href="route('events.edit', selected.id)"
                        class="crm-btn-secondary"
                    >
                        Edit
                    </Link>
                    <DangerButton v-if="selected.can_delete" type="button" class="min-h-11" @click="confirming = true">
                        Delete
                    </DangerButton>
                    <SecondaryButton type="button" class="min-h-11" @click="selected = null">Close</SecondaryButton>
                </div>
            </div>
        </Modal>

        <Modal :show="confirming" max-width="md" @close="confirming = false">
            <div class="p-6">
                <h2 class="text-h2">Delete this event?</h2>
                <p class="mt-2 text-body text-text-muted">This removes the event. This cannot be undone.</p>
                <div class="mt-6 flex flex-wrap gap-2">
                    <DangerButton type="button" class="min-h-11" :disabled="deleteForm.processing" @click="destroySelected">
                        Delete
                    </DangerButton>
                    <SecondaryButton type="button" class="min-h-11" @click="confirming = false">Cancel</SecondaryButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
