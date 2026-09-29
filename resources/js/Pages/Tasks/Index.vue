<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import PaginationBar from '@/Components/PaginationBar.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { display, formatDay } from '@/display';
import { priorityClass } from '@/forms/task';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    tasks: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        required: true,
    },
    can: {
        type: Object,
        required: true,
    },
});

const views = [
    { key: 'open', label: 'Open tasks' },
    { key: 'completed', label: 'Completed tasks' },
    { key: 'today', label: "Today's tasks" },
    { key: 'overdue', label: 'Overdue tasks' },
];

const columns = [
    { key: 'subject', label: 'Subject' },
    { key: 'related', label: 'Related to' },
    { key: 'contact', label: 'Contact' },
    { key: 'due_on', label: 'Due date' },
    { key: 'status', label: 'Status' },
    { key: 'priority', label: 'Priority' },
];

function listQuery(extra = {}) {
    return {
        view: props.filters.view,
        sort: props.filters.sort,
        direction: props.filters.direction,
        per_page: props.filters.per_page,
        ...extra,
    };
}

function changeView(view) {
    router.get(route('tasks.index'), listQuery({ view }), {
        preserveState: true,
        replace: true,
    });
}

function sortHref(column) {
    const direction = props.filters.sort === column && props.filters.direction === 'asc' ? 'desc' : 'asc';

    return route('tasks.index', listQuery({ sort: column, direction }));
}

function changePerPage(event) {
    router.get(route('tasks.index'), listQuery({ per_page: event.target.value }), {
        preserveState: true,
        replace: true,
    });
}

function complete(task, checked) {
    if (!checked || !task.can_complete) {
        return;
    }

    router.post(route('tasks.complete', task.id), {}, { preserveScroll: true });
}
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Tasks" />

        <div class="crm-page">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h1 class="text-h1">Tasks</h1>
                    <p class="mt-1 text-small text-text-muted">
                        Showing {{ tasks.from ?? 0 }}–{{ tasks.to ?? 0 }} of {{ tasks.total }}
                    </p>
                </div>
                <Link
                    v-if="can.create"
                    :href="route('tasks.create')"
                    class="crm-btn-primary"
                >
                    New task
                </Link>
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
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
            </div>

            <form class="mt-4 flex flex-wrap items-end gap-3" @submit.prevent>
                <div>
                    <label class="text-small text-text-muted" for="task-per-page">Rows</label>
                    <select
                        id="task-per-page"
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
            </form>

            <div class="mt-4 crm-table-wrap">
                <table class="crm-table">
                    <thead>
                        <tr>
                            <th scope="col" class="px-3 py-2">
                                <span class="inline-flex min-h-11 items-center text-small text-text">Done</span>
                            </th>
                            <th v-for="column in columns" :key="column.key" scope="col" class="px-3 py-2">
                                <Link
                                    v-if="['subject', 'due_on', 'status', 'priority'].includes(column.key)"
                                    :href="sortHref(column.key)"
                                    class="inline-flex min-h-11 items-center gap-1 text-small text-text"
                                >
                                    {{ column.label }}
                                    <span v-if="filters.sort === column.key">
                                        {{ filters.direction === 'asc' ? '↑' : '↓' }}
                                    </span>
                                </Link>
                                <span v-else class="inline-flex min-h-11 items-center text-small text-text">
                                    {{ column.label }}
                                </span>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="tasks.data.length === 0">
                            <td colspan="7" class="px-4 py-8 text-center text-body text-text-muted">
                                No tasks in this view.
                            </td>
                        </tr>
                        <tr v-for="task in tasks.data" :key="task.id" class="border-t border-border">
                            <td class="px-3 py-2">
                                <Checkbox
                                    :checked="task.completed"
                                    :disabled="!task.can_complete"
                                    :aria-label="`Complete ${task.subject}`"
                                    @update:checked="(checked) => complete(task, checked)"
                                />
                            </td>
                            <td class="px-3 py-2">
                                <Link :href="route('tasks.show', task.id)" class="text-secondary underline">
                                    {{ display(task.subject) }}
                                </Link>
                            </td>
                            <td class="px-3 py-2">
                                <Link
                                    v-if="task.related_href"
                                    :href="task.related_href"
                                    class="text-secondary underline"
                                >
                                    {{ task.related_label }}
                                </Link>
                                <span v-else>{{ display(task.related_label) }}</span>
                            </td>
                            <td class="px-3 py-2">{{ display(task.contact_name) }}</td>
                            <td class="px-3 py-2" :class="task.overdue ? 'text-danger' : ''">
                                {{ formatDay(task.due_on) }}
                            </td>
                            <td class="px-3 py-2">{{ display(task.status) }}</td>
                            <td class="px-3 py-2">
                                <span :class="priorityClass(task.priority)">{{ display(task.priority) }}</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                <PaginationBar :paginator="tasks" />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
