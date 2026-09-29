/* Shared by the task list, board and detail pages. */

export const STATUS = {
    'Not Started': { color: '#64748b', icon: 'lucide:circle-dashed' },
    'In Progress': { color: '#0176d3', icon: 'lucide:loader' },
    Completed: { color: '#2e844a', icon: 'lucide:circle-check' },
    Deferred: { color: '#c9691a', icon: 'lucide:pause-circle' },
};

export const PRIORITY = {
    High: { color: '#ba0517' },
    Normal: { color: '#64748b' },
    Low: { color: '#2e844a' },
};

const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

/**
 * How a due date reads at a glance: "Today", "Tomorrow", "3 days overdue"…
 * `tone` drives its colour: none | overdue | today | soon | later | done.
 * Completed tasks never read as late.
 */
export function dueInfo(task, today, { addDays, parseYmd }) {
    const due = task.due_on ? String(task.due_on).slice(0, 10) : null;

    if (!due) {
        return { label: 'No date', tone: 'none' };
    }

    const d = parseYmd(due);
    const short = `${d.getDate()} ${MONTHS[d.getMonth()]}${d.getFullYear() === parseYmd(today).getFullYear() ? '' : ` ${d.getFullYear()}`}`;

    if (task.completed) {
        return { label: short, tone: 'done' };
    }

    const days = Math.round((parseYmd(due) - parseYmd(today)) / 86400000);

    if (days < 0) {
        return { label: days === -1 ? 'Yesterday' : `${-days} days overdue`, tone: 'overdue' };
    }

    if (days === 0) {
        return { label: 'Today', tone: 'today' };
    }

    if (days === 1) {
        return { label: 'Tomorrow', tone: 'soon' };
    }

    if (days <= 6) {
        return { label: `In ${days} days`, tone: 'soon' };
    }

    return { label: short, tone: 'later' };
}
