<script setup>
/*
 * Event detail popup (FR-CAL-003): view, quick edit in place, delete with
 * confirmation. Full edit and the record page are one click away.
 */
import CrmSelect from '@/Components/CrmSelect.vue';
import Modal from '@/Components/Modal.vue';
import { durationLabel, formatClock, formatDateLabel, initials } from '@/calendar/helpers';
import { display } from '@/display';
import { Icon } from '@iconify/vue';
import { Link, router } from '@inertiajs/vue3';
import { computed, reactive, ref, watch } from 'vue';

const props = defineProps({
    event: { type: Object, default: null },
    color: { type: String, default: '#0176d3' },
    calendarLabel: { type: String, default: '' },
});

const emit = defineEmits(['close']);

const SHOW_OPTIONS = ['Busy', 'Free', 'Out of Office'].map((value) => ({ value, label: value }));

const mode = ref('view'); // view | edit | delete
const busy = ref(false);
const errors = ref({});
const form = reactive({ subject: '', starts: '', ends: '', location: '', show_time_as: 'Busy', description: '' });

const toInput = (stamp, allDay) => (allDay ? stamp.slice(0, 10) : `${stamp.slice(0, 10)}T${stamp.slice(11, 16)}`);

watch(
    () => props.event?.id,
    () => {
        mode.value = 'view';
        errors.value = {};
    },
);

function startEdit() {
    const e = props.event;

    Object.assign(form, {
        subject: e.subject,
        starts: toInput(e.starts_at, e.all_day),
        ends: toInput(e.ends_at, e.all_day),
        location: e.location ?? '',
        show_time_as: e.show_time_as ?? 'Busy',
        description: e.description ?? '',
    });
    errors.value = {};
    mode.value = 'edit';
}

const when = computed(() => {
    const e = props.event;

    if (!e) {
        return '';
    }

    const sameDay = e.starts_at.slice(0, 10) === e.ends_at.slice(0, 10);

    if (e.all_day) {
        return sameDay
            ? `${formatDateLabel(e.starts_at)} · All day`
            : `${formatDateLabel(e.starts_at)} – ${formatDateLabel(e.ends_at)} · All day`;
    }

    return sameDay
        ? `${formatDateLabel(e.starts_at)} · ${formatClock(e.starts_at)} – ${formatClock(e.ends_at)}`
        : `${formatDateLabel(e.starts_at)} ${formatClock(e.starts_at)} – ${formatDateLabel(e.ends_at)} ${formatClock(e.ends_at)}`;
});

function save() {
    const e = props.event;

    busy.value = true;
    router.put(
        route('events.update', e.id),
        {
            from_calendar: 1,
            subject: form.subject,
            assigned_to_id: e.assigned_to_id,
            related_type: e.related_key,
            related_id: e.related_id,
            contact_id: e.contact_id,
            all_day: e.all_day,
            starts_at: form.starts,
            ends_at: form.ends,
            location: form.location,
            show_time_as: form.show_time_as,
            is_private: e.is_private,
            description: form.description,
        },
        {
            preserveScroll: true,
            preserveState: true,
            onError: (bag) => {
                errors.value = bag;
            },
            onSuccess: () => emit('close'),
            onFinish: () => {
                busy.value = false;
            },
        },
    );
}

function destroy() {
    busy.value = true;
    router.delete(route('events.destroy', props.event.id), {
        data: { from_calendar: 1 },
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => emit('close'),
        onFinish: () => {
            busy.value = false;
            mode.value = 'view';
        },
    });
}
</script>

<template>
    <Modal :show="event !== null" max-width="lg" @close="emit('close')">
        <div v-if="event" class="crm-ep">
            <div class="crm-ep-bar" :style="{ background: color }" />

            <div class="crm-ep-body">
                <!-- View -->
                <template v-if="mode === 'view'">
                    <div class="crm-ep-top">
                        <div class="min-w-0">
                            <div class="crm-ep-tags">
                                <span class="crm-ep-tag" :style="{ '--tag': color }">
                                    <span class="crm-ep-dot" :style="{ background: color }" />
                                    {{ calendarLabel }}
                                </span>
                                <span v-if="event.is_private" class="crm-ep-tag">
                                    <Icon icon="lucide:lock" aria-hidden="true" /> Private
                                </span>
                            </div>
                            <h2 class="crm-ep-title">{{ event.subject }}</h2>
                            <p class="crm-ep-when">
                                <Icon icon="lucide:clock" aria-hidden="true" />
                                {{ when }}
                                <span class="crm-ep-dur">{{ durationLabel(event) }}</span>
                            </p>
                        </div>
                        <button type="button" class="crm-ep-x" aria-label="Close" @click="emit('close')">
                            <Icon icon="lucide:x" />
                        </button>
                    </div>

                    <dl class="crm-ep-grid">
                        <div>
                            <dt><Icon icon="lucide:link-2" aria-hidden="true" /> Related to</dt>
                            <dd>
                                <Link v-if="event.related_href" :href="event.related_href" class="crm-ep-link">
                                    {{ event.related_label }}
                                </Link>
                                <template v-else>{{ display(event.related_label) }}</template>
                            </dd>
                        </div>
                        <div>
                            <dt><Icon icon="lucide:user-round" aria-hidden="true" /> Contact</dt>
                            <dd>{{ display(event.contact_name) }}</dd>
                        </div>
                        <div>
                            <dt><Icon icon="lucide:map-pin" aria-hidden="true" /> Location</dt>
                            <dd>{{ display(event.location) }}</dd>
                        </div>
                        <div>
                            <dt><Icon icon="lucide:circle-dot" aria-hidden="true" /> Show time as</dt>
                            <dd>{{ display(event.show_time_as) }}</dd>
                        </div>
                        <div class="crm-ep-span">
                            <dt><Icon icon="lucide:users" aria-hidden="true" /> Assigned to</dt>
                            <dd class="crm-ep-assignee">
                                <span class="crm-ep-avatar" aria-hidden="true">{{ initials(event.assignee) }}</span>
                                {{ display(event.assignee) }}
                            </dd>
                        </div>
                    </dl>

                    <p v-if="event.description" class="crm-ep-desc">{{ event.description }}</p>

                    <div class="crm-ep-actions">
                        <Link :href="route('events.show', event.id)" class="crm-db-ghost">
                            <Icon icon="lucide:arrow-up-right" aria-hidden="true" /> Open
                        </Link>
                        <button v-if="event.can_update" type="button" class="crm-db-new" @click="startEdit">
                            <Icon icon="lucide:zap" aria-hidden="true" /> Quick edit
                        </button>
                        <Link v-if="event.can_update" :href="route('events.edit', event.id)" class="crm-db-ghost">
                            <Icon icon="lucide:pencil" aria-hidden="true" /> Full edit
                        </Link>
                        <button
                            v-if="event.can_delete"
                            type="button"
                            class="crm-db-ghost crm-db-ghost-danger crm-ep-push"
                            @click="mode = 'delete'"
                        >
                            <Icon icon="lucide:trash-2" aria-hidden="true" /> Delete
                        </button>
                    </div>
                </template>

                <!-- Quick edit -->
                <form v-else-if="mode === 'edit'" class="crm-ep-form" @submit.prevent="save">
                    <div class="crm-ep-top">
                        <div>
                            <h2 class="crm-ep-title">Quick edit</h2>
                            <p class="crm-ep-when">Need related records or an owner change? Use Full edit.</p>
                        </div>
                        <button type="button" class="crm-ep-x" aria-label="Cancel editing" @click="mode = 'view'">
                            <Icon icon="lucide:x" />
                        </button>
                    </div>

                    <div class="crm-bd-field">
                        <label for="qe-subject">Subject</label>
                        <input id="qe-subject" v-model="form.subject" type="text" required maxlength="255" />
                        <p v-if="errors.subject" class="crm-ep-err">{{ errors.subject }}</p>
                    </div>

                    <div class="crm-ep-two">
                        <div class="crm-bd-field">
                            <label for="qe-start">{{ event.all_day ? 'Start date' : 'Starts' }}</label>
                            <input id="qe-start" v-model="form.starts" :type="event.all_day ? 'date' : 'datetime-local'" required />
                            <p v-if="errors.starts_at" class="crm-ep-err">{{ errors.starts_at }}</p>
                        </div>
                        <div class="crm-bd-field">
                            <label for="qe-end">{{ event.all_day ? 'End date' : 'Ends' }}</label>
                            <input id="qe-end" v-model="form.ends" :type="event.all_day ? 'date' : 'datetime-local'" required />
                            <p v-if="errors.ends_at" class="crm-ep-err">{{ errors.ends_at }}</p>
                        </div>
                    </div>

                    <div class="crm-ep-two">
                        <div class="crm-bd-field">
                            <label for="qe-loc">Location</label>
                            <input id="qe-loc" v-model="form.location" type="text" maxlength="255" />
                        </div>
                        <div class="crm-bd-field">
                            <span class="crm-bd-label">Show time as</span>
                            <CrmSelect v-model="form.show_time_as" aria-label="Show time as" :options="SHOW_OPTIONS" />
                        </div>
                    </div>

                    <div class="crm-bd-field">
                        <label for="qe-desc">Description</label>
                        <textarea id="qe-desc" v-model="form.description" rows="3" class="crm-ep-textarea" />
                    </div>

                    <div class="crm-ep-actions">
                        <button type="submit" class="crm-db-new" :disabled="busy">
                            <Icon icon="lucide:check" aria-hidden="true" /> Save
                        </button>
                        <button type="button" class="crm-db-ghost" @click="mode = 'view'">Cancel</button>
                    </div>
                </form>

                <!-- Delete confirmation -->
                <div v-else class="crm-ep-confirm">
                    <span class="crm-db-empty-icon crm-ep-warn" aria-hidden="true"><Icon icon="lucide:trash-2" /></span>
                    <h2 class="crm-ep-title">Delete this event?</h2>
                    <p class="crm-ep-when">“{{ event.subject }}” will be removed. This cannot be undone.</p>
                    <div class="crm-ep-actions crm-ep-center">
                        <button type="button" class="crm-db-new crm-ep-danger" :disabled="busy" @click="destroy">
                            Delete event
                        </button>
                        <button type="button" class="crm-db-ghost" @click="mode = 'view'">Keep it</button>
                    </div>
                </div>
            </div>
        </div>
    </Modal>
</template>
