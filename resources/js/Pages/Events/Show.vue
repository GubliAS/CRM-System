<script setup>
import Modal from '@/Components/Modal.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { DAY_NAMES, MONTH_NAMES, durationLabel, formatClock, formatDateLabel, initials, parseYmd } from '@/calendar/helpers';
import { display, formatWhen, personName } from '@/display';
import { Icon } from '@iconify/vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps({
    event: { type: Object, required: true },
    can: { type: Object, required: true },
});

// Same status colours the calendar uses.
const STATUS = {
    Busy: { color: '#0176d3', icon: 'lucide:briefcase' },
    Free: { color: '#2e844a', icon: 'lucide:coffee' },
    'Out of Office': { color: '#c9691a', icon: 'lucide:plane' },
};

const confirming = ref(false);
const form = useForm({});
const now = ref(new Date());
let clock = null;

onMounted(() => {
    clock = setInterval(() => (now.value = new Date()), 30_000);
});
onBeforeUnmount(() => clearInterval(clock));

// The page receives the form-style inputs ("2026-10-14T09:30" or a bare date).
const stamp = (input, allDay) => (allDay ? `${input} 00:00:00` : `${String(input).replace('T', ' ')}:00`);
const shape = computed(() => ({
    all_day: props.event.all_day,
    starts_at: stamp(props.event.starts_input, props.event.all_day),
    ends_at: stamp(props.event.ends_input, props.event.all_day),
}));

const accent = computed(() => STATUS[props.event.show_time_as] ?? STATUS.Busy);
const startDate = computed(() => shape.value.starts_at.slice(0, 10));
const endDate = computed(() => shape.value.ends_at.slice(0, 10));
const multiDay = computed(() => startDate.value !== endDate.value);
const tile = computed(() => {
    const d = parseYmd(startDate.value);

    return { month: MONTH_NAMES[d.getMonth()].slice(0, 3), day: d.getDate(), weekday: DAY_NAMES[d.getDay()].slice(0, 3) };
});

const when = computed(() => {
    const s = shape.value;

    if (s.all_day) {
        return multiDay.value ? `${formatDateLabel(s.starts_at)} – ${formatDateLabel(s.ends_at)}` : formatDateLabel(s.starts_at);
    }

    return multiDay.value
        ? `${formatDateLabel(s.starts_at)} ${formatClock(s.starts_at)} – ${formatDateLabel(s.ends_at)} ${formatClock(s.ends_at)}`
        : `${formatDateLabel(s.starts_at)} · ${formatClock(s.starts_at)} – ${formatClock(s.ends_at)}`;
});

const duration = computed(() => durationLabel(shape.value));

// Where the event sits relative to right now.
const timing = computed(() => {
    const s = shape.value;
    const start = new Date(s.starts_at.replace(' ', 'T'));
    const end = s.all_day ? new Date(`${endDate.value}T23:59:59`) : new Date(s.ends_at.replace(' ', 'T'));
    const at = now.value;

    if (at >= start && at <= end) {
        return { label: 'Happening now', tone: 'live' };
    }

    const span = (ms) => {
        const mins = Math.max(Math.round(ms / 60000), 1);

        if (mins < 60) {
            return `${mins} min`;
        }
        if (mins < 60 * 24) {
            return `${Math.round(mins / 60)} h`;
        }

        const days = Math.round(mins / (60 * 24));

        return `${days} ${days === 1 ? 'day' : 'days'}`;
    };

    return at < start
        ? { label: `Starts in ${span(start - at)}`, tone: 'soon' }
        : { label: `Ended ${span(at - end)} ago`, tone: 'past' };
});

const relatedKind = computed(() => String(props.event.related_type ?? '').split('\\').pop() || null);
const assignee = computed(() => personName(props.event.assigned_to));
const owner = computed(() => personName(props.event.owner));
const dayLink = computed(() => route('events.index', { view: 'day', date: startDate.value }));

function destroy() {
    form.delete(route('events.destroy', props.event.id));
}
</script>

<template>
    <AuthenticatedLayout>
        <Head :title="event.subject" />

        <template #header>
            <div class="min-w-0">
                <h1 class="crm-page-title truncate">{{ event.subject }}</h1>
                <p class="crm-page-subtitle truncate">{{ when }}</p>
            </div>
        </template>

        <div class="crm-page max-w-none px-4 py-4 sm:px-5" :style="{ '--ev-c': accent.color }">
            <!-- Hero -->
            <section class="crm-ev-hero">
                <span class="crm-ov-spot-rings" aria-hidden="true" />

                <div class="crm-ev-tile" aria-hidden="true">
                    <span class="crm-ev-tile-month">{{ tile.month }}</span>
                    <span class="crm-ev-tile-day">{{ tile.day }}</span>
                    <span class="crm-ev-tile-week">{{ tile.weekday }}</span>
                </div>

                <div class="crm-ev-hero-main">
                    <div class="crm-ev-tags">
                        <span class="crm-ev-tag">
                            <Icon :icon="accent.icon" aria-hidden="true" />
                            {{ event.show_time_as }}
                        </span>
                        <span v-if="event.all_day" class="crm-ev-tag">
                            <Icon icon="lucide:sun" aria-hidden="true" /> All day
                        </span>
                        <span v-if="event.is_private" class="crm-ev-tag">
                            <Icon icon="lucide:lock" aria-hidden="true" /> Private
                        </span>
                        <span class="crm-ev-tag crm-ev-timing" :class="`is-${timing.tone}`">
                            <span class="crm-ev-pulse" aria-hidden="true" />
                            {{ timing.label }}
                        </span>
                    </div>

                    <h2 class="crm-ev-title">{{ event.subject }}</h2>
                    <p class="crm-ev-when">
                        <Icon icon="lucide:clock" aria-hidden="true" />
                        {{ when }}
                        <span class="crm-ev-dur">{{ duration }}</span>
                    </p>
                </div>

                <div class="crm-ev-actions">
                    <Link :href="dayLink" class="crm-ev-btn">
                        <Icon icon="lucide:calendar-days" aria-hidden="true" /> View on calendar
                    </Link>
                    <Link v-if="can.update" :href="route('events.edit', event.id)" class="crm-ev-btn crm-ev-btn-solid">
                        <Icon icon="lucide:pencil" aria-hidden="true" /> Edit
                    </Link>
                    <button v-if="can.delete" type="button" class="crm-ev-btn crm-ev-btn-danger" @click="confirming = true">
                        <Icon icon="lucide:trash-2" aria-hidden="true" /> Delete
                    </button>
                </div>
            </section>

            <div class="crm-ev-grid">
                <!-- Details -->
                <section class="crm-ev-card crm-ev-details" aria-labelledby="ev-details">
                    <h2 id="ev-details" class="crm-ev-card-title">Details</h2>

                    <ul class="crm-ev-rows" role="list">
                        <li>
                            <span class="crm-ev-ico"><Icon icon="lucide:link-2" aria-hidden="true" /></span>
                            <div>
                                <p class="crm-ev-label">Related to</p>
                                <p class="crm-ev-value">
                                    <Link v-if="event.related_href" :href="event.related_href" class="crm-ev-link">
                                        {{ event.related_label }}
                                        <Icon icon="lucide:arrow-up-right" aria-hidden="true" />
                                    </Link>
                                    <template v-else>{{ display(event.related_label) }}</template>
                                    <span v-if="relatedKind && event.related_href" class="crm-ev-kind">{{ relatedKind }}</span>
                                </p>
                            </div>
                        </li>
                        <li>
                            <span class="crm-ev-ico"><Icon icon="lucide:contact-round" aria-hidden="true" /></span>
                            <div>
                                <p class="crm-ev-label">Contact</p>
                                <p class="crm-ev-value">{{ display(event.contact_label) }}</p>
                            </div>
                        </li>
                        <li>
                            <span class="crm-ev-ico"><Icon icon="lucide:map-pin" aria-hidden="true" /></span>
                            <div>
                                <p class="crm-ev-label">Location</p>
                                <p class="crm-ev-value">{{ display(event.location) }}</p>
                            </div>
                        </li>
                        <li>
                            <span class="crm-ev-ico"><Icon :icon="accent.icon" aria-hidden="true" /></span>
                            <div>
                                <p class="crm-ev-label">Show time as</p>
                                <p class="crm-ev-value">
                                    <span class="crm-ev-status"><span class="crm-ev-dot" />{{ event.show_time_as }}</span>
                                </p>
                            </div>
                        </li>
                        <li>
                            <span class="crm-ev-ico"><Icon :icon="event.is_private ? 'lucide:lock' : 'lucide:eye'" aria-hidden="true" /></span>
                            <div>
                                <p class="crm-ev-label">Visibility</p>
                                <p class="crm-ev-value">{{ event.is_private ? 'Private — only the owner can see it' : 'Visible to people who can view events' }}</p>
                            </div>
                        </li>
                    </ul>

                    <div class="crm-ev-desc">
                        <p class="crm-ev-label"><Icon icon="lucide:align-left" aria-hidden="true" /> Description</p>
                        <p v-if="event.description" class="crm-ev-desc-text">{{ event.description }}</p>
                        <p v-else class="crm-ev-empty">No description added.</p>
                    </div>
                </section>

                <!-- Side -->
                <div class="crm-ev-side">
                    <section class="crm-ev-card" aria-labelledby="ev-when">
                        <h2 id="ev-when" class="crm-ev-card-title">Schedule</h2>
                        <ol class="crm-ev-timeline" role="list">
                            <li>
                                <span class="crm-ev-node" />
                                <div>
                                    <p class="crm-ev-label">Starts</p>
                                    <p class="crm-ev-value">{{ formatDateLabel(shape.starts_at) }}</p>
                                    <p v-if="!event.all_day" class="crm-ev-time">{{ formatClock(shape.starts_at) }}</p>
                                </div>
                            </li>
                            <li class="crm-ev-between">
                                <span class="crm-ev-gap">{{ duration }}</span>
                            </li>
                            <li>
                                <span class="crm-ev-node crm-ev-node-end" />
                                <div>
                                    <p class="crm-ev-label">Ends</p>
                                    <p class="crm-ev-value">{{ formatDateLabel(shape.ends_at) }}</p>
                                    <p v-if="!event.all_day" class="crm-ev-time">{{ formatClock(shape.ends_at) }}</p>
                                </div>
                            </li>
                        </ol>
                    </section>

                    <section class="crm-ev-card" aria-labelledby="ev-people">
                        <h2 id="ev-people" class="crm-ev-card-title">People</h2>
                        <ul class="crm-ev-people" role="list">
                            <li>
                                <span class="crm-ev-avatar" aria-hidden="true">{{ initials(assignee) }}</span>
                                <div>
                                    <p class="crm-ev-value">{{ assignee }}</p>
                                    <p class="crm-ev-label">Assigned to</p>
                                </div>
                            </li>
                            <li>
                                <span class="crm-ev-avatar crm-ev-avatar-alt" aria-hidden="true">{{ initials(owner) }}</span>
                                <div>
                                    <p class="crm-ev-value">{{ owner }}</p>
                                    <p class="crm-ev-label">Owner</p>
                                </div>
                            </li>
                        </ul>
                    </section>

                    <section class="crm-ev-card crm-ev-meta" aria-labelledby="ev-record">
                        <h2 id="ev-record" class="crm-ev-card-title">Record</h2>
                        <dl>
                            <div>
                                <dt><Icon icon="lucide:plus-circle" aria-hidden="true" /> Created</dt>
                                <dd>{{ formatWhen(event.created_at) }}<template v-if="event.created_by"> · {{ personName(event.created_by) }}</template></dd>
                            </div>
                            <div>
                                <dt><Icon icon="lucide:history" aria-hidden="true" /> Last updated</dt>
                                <dd>{{ formatWhen(event.updated_at) }}<template v-if="event.updated_by"> · {{ personName(event.updated_by) }}</template></dd>
                            </div>
                        </dl>
                    </section>
                </div>
            </div>
        </div>

        <Modal :show="confirming" max-width="md" @close="confirming = false">
            <div class="crm-ep-body crm-ep-confirm">
                <span class="crm-db-empty-icon crm-ep-warn" aria-hidden="true"><Icon icon="lucide:trash-2" /></span>
                <h2 class="crm-ep-title">Delete this event?</h2>
                <p class="crm-ep-when">“{{ event.subject }}” will be removed from the calendar. This cannot be undone.</p>
                <div class="crm-ep-actions crm-ep-center">
                    <button type="button" class="crm-db-new crm-ep-danger" :disabled="form.processing" @click="destroy">
                        Delete event
                    </button>
                    <button type="button" class="crm-db-ghost" @click="confirming = false">Keep it</button>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
