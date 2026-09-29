<script setup>
import Modal from '@/Components/Modal.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { DAY_NAMES, MONTH_NAMES, addDays, initials, parseYmd, ymd } from '@/calendar/helpers';
import { display, formatDay, formatWhen, personName } from '@/display';
import { PRIORITY, STATUS, dueInfo } from '@/tasks/helpers';
import { Icon } from '@iconify/vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    task: { type: Object, required: true },
    can: { type: Object, required: true },
});

const STATUSES = ['Not Started', 'In Progress', 'Completed', 'Deferred'];

const confirming = ref(false);
const form = useForm({});
const shown = ref(props.task.status);
const today = ymd(new Date());

const status = computed(() => STATUS[shown.value] ?? STATUS['Not Started']);
const priority = computed(() => PRIORITY[props.task.priority] ?? PRIORITY.Normal);
const completed = computed(() => shown.value === 'Completed');
const due = computed(() =>
    dueInfo({ due_on: props.task.due_on, completed: completed.value }, today, { addDays, parseYmd }),
);
const dueDate = computed(() => (props.task.due_on ? String(props.task.due_on).slice(0, 10) : null));
const tile = computed(() => {
    if (!dueDate.value) {
        return null;
    }

    const d = parseYmd(dueDate.value);

    return { month: MONTH_NAMES[d.getMonth()].slice(0, 3), day: d.getDate(), weekday: DAY_NAMES[d.getDay()].slice(0, 3) };
});

const reminder = computed(() => {
    if (!props.task.reminder_set) {
        return null;
    }

    const day = formatDay(props.task.reminder_date);

    return props.task.reminder_time ? `${day} at ${props.task.reminder_time}` : day;
});

function setStatus(next) {
    if (!props.can.update || next === shown.value) {
        return;
    }

    const before = shown.value;

    shown.value = next;
    router.patch(
        route('tasks.status', props.task.id),
        { status: next },
        {
            preserveScroll: true,
            preserveState: true,
            onError: () => {
                shown.value = before;
            },
        },
    );
}

function destroy() {
    form.delete(route('tasks.destroy', props.task.id));
}
</script>

<template>
    <AuthenticatedLayout>
        <Head :title="task.subject" />

        <template #header>
            <div class="min-w-0">
                <h1 class="crm-page-title truncate">{{ task.subject }}</h1>
                <p class="crm-page-subtitle truncate">{{ shown }} · {{ due.label }}</p>
            </div>
        </template>

        <div class="crm-page max-w-none px-4 py-4 sm:px-5" :style="{ '--ev-c': status.color }">
            <section class="crm-ev-hero">
                <span class="crm-ov-spot-rings" aria-hidden="true" />

                <div v-if="tile" class="crm-ev-tile" aria-hidden="true">
                    <span class="crm-ev-tile-month">{{ tile.month }}</span>
                    <span class="crm-ev-tile-day">{{ tile.day }}</span>
                    <span class="crm-ev-tile-week">{{ tile.weekday }}</span>
                </div>
                <div v-else class="crm-ev-tile crm-tk-notile" aria-hidden="true">
                    <Icon icon="lucide:calendar-off" />
                    <span>No date</span>
                </div>

                <div class="crm-ev-hero-main">
                    <div class="crm-ev-tags">
                        <span class="crm-ev-tag"><Icon :icon="status.icon" aria-hidden="true" /> {{ shown }}</span>
                        <span class="crm-ev-tag"><Icon icon="lucide:flag" aria-hidden="true" /> {{ display(task.priority) }} priority</span>
                        <span class="crm-ev-tag crm-ev-timing" :class="due.tone === 'overdue' ? 'is-overdue' : ''">
                            <Icon icon="lucide:calendar-clock" aria-hidden="true" /> {{ due.label }}
                        </span>
                    </div>

                    <h2 class="crm-ev-title" :class="completed ? 'crm-tk-struck' : ''">{{ task.subject }}</h2>
                    <p class="crm-ev-when">
                        <Icon icon="lucide:user-round" aria-hidden="true" />
                        {{ personName(task.assigned_to) }}
                        <template v-if="task.related_label">
                            · <Icon icon="lucide:link-2" aria-hidden="true" /> {{ task.related_label }}
                        </template>
                    </p>
                </div>

                <div class="crm-ev-actions">
                    <button v-if="can.update && !completed" type="button" class="crm-ev-btn crm-ev-btn-solid" @click="setStatus('Completed')">
                        <Icon icon="lucide:check" aria-hidden="true" /> Mark complete
                    </button>
                    <button v-else-if="can.update && completed" type="button" class="crm-ev-btn" @click="setStatus('In Progress')">
                        <Icon icon="lucide:rotate-ccw" aria-hidden="true" /> Reopen
                    </button>
                    <Link v-if="can.update" :href="route('tasks.edit', task.id)" class="crm-ev-btn">
                        <Icon icon="lucide:pencil" aria-hidden="true" /> Edit
                    </Link>
                    <button v-if="can.delete" type="button" class="crm-ev-btn crm-ev-btn-danger" @click="confirming = true">
                        <Icon icon="lucide:trash-2" aria-hidden="true" /> Delete
                    </button>
                </div>
            </section>

            <!-- Status stepper -->
            <div class="crm-tk-steps" role="radiogroup" aria-label="Task status">
                <button
                    v-for="item in STATUSES"
                    :key="item"
                    type="button"
                    role="radio"
                    class="crm-tk-step"
                    :class="shown === item ? 'is-active' : ''"
                    :style="{ '--c': STATUS[item].color }"
                    :aria-checked="shown === item"
                    :disabled="!can.update"
                    @click="setStatus(item)"
                >
                    <Icon :icon="STATUS[item].icon" aria-hidden="true" />
                    {{ item }}
                </button>
            </div>

            <div class="crm-ev-grid">
                <section class="crm-ev-card crm-ev-details" aria-labelledby="tk-details">
                    <h2 id="tk-details" class="crm-ev-card-title">Details</h2>

                    <ul class="crm-ev-rows" role="list">
                        <li>
                            <span class="crm-ev-ico"><Icon icon="lucide:link-2" aria-hidden="true" /></span>
                            <div>
                                <p class="crm-ev-label">Related to</p>
                                <p class="crm-ev-value">
                                    <Link v-if="task.related_href" :href="task.related_href" class="crm-ev-link">
                                        {{ task.related_label }} <Icon icon="lucide:arrow-up-right" aria-hidden="true" />
                                    </Link>
                                    <template v-else>{{ display(task.related_label) }}</template>
                                </p>
                            </div>
                        </li>
                        <li>
                            <span class="crm-ev-ico"><Icon icon="lucide:contact-round" aria-hidden="true" /></span>
                            <div>
                                <p class="crm-ev-label">Contact</p>
                                <p class="crm-ev-value">{{ display(task.contact_label) }}</p>
                            </div>
                        </li>
                        <li>
                            <span class="crm-ev-ico"><Icon icon="lucide:calendar" aria-hidden="true" /></span>
                            <div>
                                <p class="crm-ev-label">Due date</p>
                                <p class="crm-ev-value">
                                    {{ dueDate ? formatDay(dueDate) : '—' }}
                                    <span v-if="dueDate" class="crm-tk-due" :class="`is-${due.tone}`">{{ due.label }}</span>
                                </p>
                            </div>
                        </li>
                        <li>
                            <span class="crm-ev-ico" :style="{ color: priority.color, background: `color-mix(in srgb, ${priority.color} 12%, transparent)` }">
                                <Icon icon="lucide:flag" aria-hidden="true" />
                            </span>
                            <div>
                                <p class="crm-ev-label">Priority</p>
                                <p class="crm-ev-value">{{ display(task.priority) }}</p>
                            </div>
                        </li>
                        <li>
                            <span class="crm-ev-ico"><Icon icon="lucide:bell-ring" aria-hidden="true" /></span>
                            <div>
                                <p class="crm-ev-label">Reminder</p>
                                <p class="crm-ev-value">{{ reminder ?? 'No reminder set' }}</p>
                            </div>
                        </li>
                    </ul>

                    <div class="crm-ev-desc">
                        <p class="crm-ev-label"><Icon icon="lucide:message-square-text" aria-hidden="true" /> Comments</p>
                        <p v-if="task.comments" class="crm-ev-desc-text">{{ task.comments }}</p>
                        <p v-else class="crm-ev-empty">No comments added.</p>
                    </div>
                </section>

                <div class="crm-ev-side">
                    <section class="crm-ev-card" aria-labelledby="tk-people">
                        <h2 id="tk-people" class="crm-ev-card-title">People</h2>
                        <ul class="crm-ev-people" role="list">
                            <li>
                                <span class="crm-ev-avatar" aria-hidden="true">{{ initials(personName(task.assigned_to)) }}</span>
                                <div>
                                    <p class="crm-ev-value">{{ personName(task.assigned_to) }}</p>
                                    <p class="crm-ev-label">Assigned to</p>
                                </div>
                            </li>
                            <li>
                                <span class="crm-ev-avatar crm-ev-avatar-alt" aria-hidden="true">{{ initials(personName(task.owner)) }}</span>
                                <div>
                                    <p class="crm-ev-value">{{ personName(task.owner) }}</p>
                                    <p class="crm-ev-label">Owner</p>
                                </div>
                            </li>
                        </ul>
                    </section>

                    <section class="crm-ev-card crm-ev-meta" aria-labelledby="tk-record">
                        <h2 id="tk-record" class="crm-ev-card-title">Record</h2>
                        <dl>
                            <div>
                                <dt><Icon icon="lucide:plus-circle" aria-hidden="true" /> Created</dt>
                                <dd>{{ formatWhen(task.created_at) }}<template v-if="task.created_by"> · {{ personName(task.created_by) }}</template></dd>
                            </div>
                            <div>
                                <dt><Icon icon="lucide:history" aria-hidden="true" /> Last updated</dt>
                                <dd>{{ formatWhen(task.updated_at) }}<template v-if="task.updated_by"> · {{ personName(task.updated_by) }}</template></dd>
                            </div>
                        </dl>
                    </section>
                </div>
            </div>
        </div>

        <Modal :show="confirming" max-width="md" @close="confirming = false">
            <div class="crm-ep-body crm-ep-confirm">
                <span class="crm-db-empty-icon crm-ep-warn" aria-hidden="true"><Icon icon="lucide:trash-2" /></span>
                <h2 class="crm-ep-title">Delete this task?</h2>
                <p class="crm-ep-when">“{{ task.subject }}” will be removed. This cannot be undone.</p>
                <div class="crm-ep-actions crm-ep-center">
                    <button type="button" class="crm-db-new crm-ep-danger" :disabled="form.processing" @click="destroy">
                        Delete task
                    </button>
                    <button type="button" class="crm-db-ghost" @click="confirming = false">Keep it</button>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
