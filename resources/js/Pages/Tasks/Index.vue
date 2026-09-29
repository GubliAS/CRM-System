<script setup>
import CrmSelect from '@/Components/CrmSelect.vue';
import PaginationBar from '@/Components/PaginationBar.vue';
import TableHeadIcon from '@/Components/TableHeadIcon.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { addDays, initials, parseYmd, ymd } from '@/calendar/helpers';
import { display, formatDay } from '@/display';
import { PRIORITY, STATUS, dueInfo } from '@/tasks/helpers';
import { Icon } from '@iconify/vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, ref, watch } from 'vue';

const props = defineProps({
    tasks: { type: Object, default: null },
    board: { type: Array, default: null },
    counts: { type: Object, required: true },
    statuses: { type: Array, required: true },
    currentUserId: { type: Number, required: true },
    filters: { type: Object, required: true },
    can: { type: Object, required: true },
});

const VIEWS = [
    { key: 'open', label: 'Open', icon: 'lucide:circle-dot', tone: 'blue' },
    { key: 'today', label: 'Due today', icon: 'lucide:sun', tone: 'amber' },
    { key: 'overdue', label: 'Overdue', icon: 'lucide:alarm-clock', tone: 'red' },
    { key: 'completed', label: 'Completed', icon: 'lucide:circle-check', tone: 'green' },
];
const PER_PAGE = [25, 50, 100, 200].map((n) => ({ value: n, label: `${n} / page` }));
const SORTABLE = ['subject', 'due_on', 'status', 'priority'];

/* ── Instant feedback: the click shows at once, the server confirms later ── */

const viewShown = ref(props.filters.view);
const layoutShown = ref(props.filters.layout);
const pending = ref(false);
const draft = ref('');
const adding = ref(false);
const doneIds = ref(new Set());
const moved = ref({});
const dragOver = ref(null);
const dragging = ref(null);
const today = ymd(new Date());

watch(
    () => props.filters,
    (value) => {
        viewShown.value = value.view;
        layoutShown.value = value.layout;
    },
);

// Server data replaces every optimistic change.
watch(
    () => [props.tasks, props.board],
    () => {
        doneIds.value = new Set();
        moved.value = {};
    },
);

function query(extra = {}) {
    return {
        view: viewShown.value,
        layout: layoutShown.value,
        sort: props.filters.sort,
        direction: props.filters.direction,
        per_page: props.filters.per_page,
        ...extra,
    };
}

function visit(extra = {}) {
    router.get(route('tasks.index'), query(extra), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onStart: () => {
            pending.value = true;
        },
        onFinish: () => {
            pending.value = false;
        },
    });
}

// Each request is a round trip to a slow database: begin fetching on hover.
function prefetch(extra) {
    router.prefetch(route('tasks.index'), { method: 'get', data: query(extra) }, { cacheFor: '30s' });
}

function pickView(key) {
    // From the board a filter card means "show me the list of these".
    if (key === viewShown.value && layoutShown.value === 'list') {
        return;
    }

    viewShown.value = key;
    layoutShown.value = 'list';
    visit({ view: key, layout: 'list' });
}

function pickLayout(layout) {
    if (layout === layoutShown.value) {
        return;
    }

    layoutShown.value = layout;
    visit({ layout });
}

function sortBy(column) {
    const direction = props.filters.sort === column && props.filters.direction === 'asc' ? 'desc' : 'asc';

    visit({ sort: column, direction });
}

/* ── Actions ── */

function quickAdd() {
    const subject = draft.value.trim();

    if (!subject || adding.value) {
        return;
    }

    adding.value = true;
    router.post(
        route('tasks.store'),
        { from_list: 1, subject, assigned_to_id: props.currentUserId },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                draft.value = '';
            },
            onFinish: () => {
                adding.value = false;
            },
        },
    );
}

function complete(task) {
    if (!task.can_complete || doneIds.value.has(task.id)) {
        return;
    }

    doneIds.value = new Set([...doneIds.value, task.id]);
    router.post(route('tasks.complete', task.id), {}, { preserveScroll: true, preserveState: true });
}

const isDone = (task) => task.completed || doneIds.value.has(task.id);

function open(task, event) {
    if (event.target.closest('a, button, input')) {
        return;
    }

    router.visit(route('tasks.show', task.id));
}

/* ── Board ── */

const columns = computed(() => {
    const cards = (props.board ?? []).map((card) =>
        moved.value[card.id] ? { ...card, status: moved.value[card.id], completed: moved.value[card.id] === 'Completed' } : card,
    );

    return props.statuses.map((status) => ({
        status,
        ...STATUS[status],
        cards: cards.filter((card) => card.status === status),
    }));
});

/*
 * Pointer-driven drag. The browser's own drag image is a faded snapshot, so
 * instead a solid copy of the card follows the pointer (tilted, with a shadow),
 * the original stays behind dimmed as a placeholder, and the column under the
 * pointer lights up. Mouse and pen only: on touch, the card scrolls the page.
 */
let drag = null;
let swallowClick = false;

function onCardDown(event, card) {
    if (event.button !== 0 || event.pointerType === 'touch' || !card.can_update) {
        return;
    }

    const rect = event.currentTarget.getBoundingClientRect();

    drag = {
        card,
        el: event.currentTarget,
        startX: event.clientX,
        startY: event.clientY,
        offX: event.clientX - rect.left,
        offY: event.clientY - rect.top,
        width: rect.width,
        started: false,
        ghost: null,
    };

    window.addEventListener('pointermove', onDragMove);
    window.addEventListener('pointerup', onDragEnd);
    window.addEventListener('pointercancel', onDragEnd);
}

function onDragMove(event) {
    if (!drag) {
        return;
    }

    if (!drag.started) {
        // A click that wobbles a little is still a click.
        if (Math.hypot(event.clientX - drag.startX, event.clientY - drag.startY) < 6) {
            return;
        }

        const ghost = drag.el.cloneNode(true);

        ghost.classList.add('crm-tk-float');
        ghost.style.width = `${drag.width}px`;
        document.body.appendChild(ghost);
        document.body.classList.add('crm-tk-dragging');
        drag.ghost = ghost;
        drag.started = true;
        dragging.value = drag.card.id;
    }

    drag.ghost.style.transform = `translate(${event.clientX - drag.offX}px, ${event.clientY - drag.offY}px) rotate(2.5deg)`;
    dragOver.value = document.elementFromPoint(event.clientX, event.clientY)?.closest('[data-status]')?.dataset.status ?? null;
}

function endDrag() {
    window.removeEventListener('pointermove', onDragMove);
    window.removeEventListener('pointerup', onDragEnd);
    window.removeEventListener('pointercancel', onDragEnd);
    drag?.ghost?.remove();
    document.body.classList.remove('crm-tk-dragging');
    dragging.value = null;
    dragOver.value = null;
}

function onDragEnd(event) {
    const finished = drag;
    const target = dragOver.value;

    endDrag();
    drag = null;

    if (!finished?.started) {
        return;
    }

    // The mouse-up would otherwise "click" the card's link and open it.
    swallowClick = true;
    setTimeout(() => (swallowClick = false), 60);

    if (event.type === 'pointerup' && target) {
        moveCard(finished.card, target);
    }
}

function onCardClick(event) {
    if (swallowClick) {
        event.preventDefault();
        event.stopPropagation();
    }
}

onBeforeUnmount(() => {
    endDrag();
    drag = null;
});

function moveCard(card, status) {
    const current = moved.value[card.id] ?? card.status;

    if (current === status) {
        return;
    }

    moved.value = { ...moved.value, [card.id]: status };
    router.patch(
        route('tasks.status', card.id),
        { status },
        {
            preserveScroll: true,
            preserveState: true,
            onError: () => {
                const { [card.id]: _undo, ...rest } = moved.value;
                moved.value = rest;
            },
        },
    );
}

const subtitle = computed(() => {
    const { open: o, overdue, today: t } = props.counts;

    if (o === 0) {
        return 'Nothing open. Enjoy the quiet, or add something new.';
    }

    return `${o} open${overdue ? ` · ${overdue} overdue` : ''}${t ? ` · ${t} due today` : ''}`;
});

const rangeLabel = computed(() =>
    props.tasks ? `Showing ${props.tasks.from ?? 0}–${props.tasks.to ?? 0} of ${props.tasks.total}` : '',
);

const dueOf = (task) => dueInfo(task, today, { addDays, parseYmd });
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Tasks" />

        <template #header>
            <div class="min-w-0">
                <h1 class="crm-page-title">Tasks</h1>
                <p class="crm-page-subtitle truncate">{{ subtitle }}</p>
            </div>
        </template>

        <div class="crm-page max-w-none px-4 py-4 sm:px-5">
            <!-- Filter cards -->
            <div class="crm-tk-stats" role="tablist" aria-label="Task filters">
                <button
                    v-for="item in VIEWS"
                    :key="item.key"
                    type="button"
                    role="tab"
                    class="crm-tk-stat"
                    :class="[`crm-tk-tone-${item.tone}`, viewShown === item.key && layoutShown === 'list' ? 'is-active' : '']"
                    :aria-selected="viewShown === item.key && layoutShown === 'list'"
                    @pointerenter="prefetch({ view: item.key, layout: 'list' })"
                    @click="pickView(item.key)"
                >
                    <span class="crm-tk-stat-icon"><Icon :icon="item.icon" aria-hidden="true" /></span>
                    <span class="crm-tk-stat-body">
                        <span class="crm-tk-stat-num tabular-nums">{{ counts[item.key] }}</span>
                        <span class="crm-tk-stat-label">{{ item.label }}</span>
                    </span>
                </button>
            </div>

            <!-- Toolbar -->
            <div class="crm-tk-bar">
                <div class="crm-cal-toggle" role="tablist" aria-label="Layout">
                    <button
                        v-for="item in [{ key: 'list', label: 'List', icon: 'lucide:list-checks' }, { key: 'board', label: 'Board', icon: 'lucide:layout-grid' }]"
                        :key="item.key"
                        type="button"
                        role="tab"
                        class="crm-cal-toggle-btn"
                        :class="layoutShown === item.key ? 'is-active' : ''"
                        :aria-selected="layoutShown === item.key"
                        @pointerenter="prefetch({ layout: item.key })"
                        @click="pickLayout(item.key)"
                    >
                        <Icon :icon="item.icon" aria-hidden="true" />
                        {{ item.label }}
                    </button>
                </div>

                <form v-if="can.create" class="crm-tk-add" @submit.prevent="quickAdd">
                    <Icon icon="lucide:plus" class="crm-tk-add-icon" aria-hidden="true" />
                    <input
                        v-model="draft"
                        type="text"
                        maxlength="255"
                        placeholder="Add a task and press Enter…"
                        aria-label="New task subject"
                        :disabled="adding"
                    />
                    <button v-if="draft.trim()" type="submit" class="crm-tk-add-go" :disabled="adding">
                        <Icon :icon="adding ? 'lucide:loader-circle' : 'lucide:corner-down-left'" :class="adding ? 'crm-db-spin' : ''" aria-hidden="true" />
                        Add
                    </button>
                </form>

                <div class="crm-tk-bar-right">
                    <span v-if="pending" class="crm-db-loading" role="status">
                        <Icon icon="lucide:loader-circle" class="crm-db-spin" aria-hidden="true" /> Loading…
                    </span>
                    <CrmSelect
                        v-if="layoutShown === 'list'"
                        variant="pill"
                        align="end"
                        aria-label="Rows per page"
                        :model-value="filters.per_page"
                        :options="PER_PAGE"
                        @update:model-value="visit({ per_page: $event })"
                    />
                    <Link v-if="can.create" :href="route('tasks.create')" class="crm-db-new">
                        <Icon icon="lucide:square-pen" aria-hidden="true" />
                        New task
                    </Link>
                </div>
            </div>

            <!-- List -->
            <section v-if="layoutShown === 'list'" class="crm-tk-card" :class="pending ? 'crm-db-list-pending' : ''" :aria-busy="pending">
                <div class="crm-ov-tablewrap">
                    <table class="crm-ov-table crm-tk-table">
                        <thead>
                            <tr>
                                <th scope="col" class="crm-tk-th-check"><span class="sr-only">Done</span></th>
                                <th scope="col">
                                    <button type="button" class="crm-tk-sort" @click="sortBy('subject')">
                                        <TableHeadIcon name="id" /> Subject
                                        <Icon v-if="filters.sort === 'subject'" :icon="filters.direction === 'asc' ? 'lucide:arrow-up' : 'lucide:arrow-down'" />
                                    </button>
                                </th>
                                <th scope="col"><span class="crm-ov-th"><TableHeadIcon name="client" /> Related to</span></th>
                                <th scope="col"><span class="crm-ov-th"><Icon icon="lucide:contact-round" /> Contact</span></th>
                                <th scope="col">
                                    <button type="button" class="crm-tk-sort" @click="sortBy('due_on')">
                                        <TableHeadIcon name="due" /> Due date
                                        <Icon v-if="filters.sort === 'due_on'" :icon="filters.direction === 'asc' ? 'lucide:arrow-up' : 'lucide:arrow-down'" />
                                    </button>
                                </th>
                                <th scope="col">
                                    <button type="button" class="crm-tk-sort" @click="sortBy('status')">
                                        <TableHeadIcon name="status" /> Status
                                        <Icon v-if="filters.sort === 'status'" :icon="filters.direction === 'asc' ? 'lucide:arrow-up' : 'lucide:arrow-down'" />
                                    </button>
                                </th>
                                <th scope="col">
                                    <button type="button" class="crm-tk-sort" @click="sortBy('priority')">
                                        <Icon icon="lucide:flag" /> Priority
                                        <Icon v-if="filters.sort === 'priority'" :icon="filters.direction === 'asc' ? 'lucide:arrow-up' : 'lucide:arrow-down'" />
                                    </button>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="!tasks || tasks.data.length === 0">
                                <td colspan="7" class="crm-tk-empty">
                                    <span class="crm-db-empty-icon" aria-hidden="true"><Icon icon="lucide:check-check" /></span>
                                    <strong>{{ tasks ? 'No tasks in this view' : 'Loading tasks…' }}</strong>
                                    <span v-if="tasks">Use the box above to add one.</span>
                                </td>
                            </tr>
                            <tr
                                v-for="task in tasks?.data ?? []"
                                :key="task.id"
                                class="crm-ov-row-link"
                                :class="isDone(task) ? 'is-done' : ''"
                                @click="open(task, $event)"
                            >
                                <td class="crm-tk-td-check">
                                    <button
                                        type="button"
                                        class="crm-tk-check"
                                        :class="isDone(task) ? 'is-on' : ''"
                                        :disabled="!task.can_complete && !isDone(task)"
                                        :aria-pressed="isDone(task)"
                                        :aria-label="isDone(task) ? `${task.subject} is completed` : `Complete ${task.subject}`"
                                        @click.stop="complete(task)"
                                    >
                                        <Icon icon="lucide:check" aria-hidden="true" />
                                    </button>
                                </td>
                                <td class="crm-ov-cell-strong">
                                    <Link :href="route('tasks.show', task.id)" class="crm-tk-subject">{{ display(task.subject) }}</Link>
                                </td>
                                <td>
                                    <Link v-if="task.related_href" :href="task.related_href" class="crm-ov-cell-link" @click.stop>
                                        {{ task.related_label }}
                                    </Link>
                                    <template v-else>{{ display(task.related_label) }}</template>
                                </td>
                                <td>{{ display(task.contact_name) }}</td>
                                <td>
                                    <span class="crm-tk-due" :class="`is-${dueOf(task).tone}`" :title="formatDay(task.due_on)">
                                        {{ dueOf(task).label }}
                                    </span>
                                </td>
                                <td>
                                    <span class="crm-tk-status" :style="{ '--c': (STATUS[task.status] ?? STATUS['Not Started']).color }">
                                        <Icon :icon="(STATUS[task.status] ?? STATUS['Not Started']).icon" aria-hidden="true" />
                                        {{ task.status }}
                                    </span>
                                </td>
                                <td>
                                    <span class="crm-tk-prio" :style="{ '--c': (PRIORITY[task.priority] ?? PRIORITY.Normal).color }">
                                        <Icon icon="lucide:flag" aria-hidden="true" />
                                        {{ display(task.priority) }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="tasks" class="crm-cal-listfoot">
                    <span class="crm-db-range crm-tk-range">{{ rangeLabel }}</span>
                    <PaginationBar :paginator="tasks" />
                </div>
            </section>

            <!-- Board -->
            <section v-else class="crm-tk-board" :class="pending ? 'crm-db-list-pending' : ''" :aria-busy="pending" aria-label="Task board">
                <div
                    v-for="column in columns"
                    :key="column.status"
                    class="crm-tk-col"
                    :class="dragOver === column.status ? 'is-over' : ''"
                    :data-status="column.status"
                    :style="{ '--c': column.color }"
                >
                    <header class="crm-tk-col-head">
                        <span class="crm-tk-col-dot" />
                        <h2>{{ column.status }}</h2>
                        <span class="crm-tk-col-count tabular-nums">{{ column.cards.length }}</span>
                    </header>

                    <div class="crm-tk-col-body">
                        <p v-if="column.cards.length === 0" class="crm-tk-col-empty">Drop tasks here</p>

                        <article
                            v-for="card in column.cards"
                            :key="card.id"
                            class="crm-tk-item"
                            :class="[card.completed ? 'is-done' : '', card.can_update ? 'is-draggable' : '', dragging === card.id ? 'is-dragging' : '']"
                            @pointerdown="onCardDown($event, card)"
                            @dragstart.prevent
                            @click.capture="onCardClick"
                        >
                            <Link :href="route('tasks.show', card.id)" class="crm-tk-item-title">{{ display(card.subject) }}</Link>

                            <p v-if="card.related_label" class="crm-tk-item-line">
                                <Icon icon="lucide:link-2" aria-hidden="true" />{{ card.related_label }}
                            </p>

                            <div class="crm-tk-item-foot">
                                <span class="crm-tk-due" :class="`is-${dueOf(card).tone}`">
                                    <Icon icon="lucide:calendar" aria-hidden="true" />{{ dueOf(card).label }}
                                </span>
                                <span class="crm-tk-prio crm-tk-prio-icon" :style="{ '--c': (PRIORITY[card.priority] ?? PRIORITY.Normal).color }" :title="`${card.priority ?? 'Normal'} priority`">
                                    <Icon icon="lucide:flag" aria-hidden="true" />
                                </span>
                                <span v-if="card.assignee" class="crm-tk-avatar" :title="card.assignee">{{ initials(card.assignee) }}</span>
                            </div>
                        </article>
                    </div>
                </div>

                <p v-if="board && board.length >= 300" class="crm-tk-cap">Showing the first 300 tasks.</p>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
