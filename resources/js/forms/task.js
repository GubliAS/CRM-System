export function taskFormData(task = null, userId = '') {
    return {
        subject: task?.subject ?? '',
        assigned_to_id: task?.assigned_to_id ? String(task.assigned_to_id) : String(userId ?? ''),
        related_type: task?.related_key ?? '',
        related_id: task?.related_id ? String(task.related_id) : '',
        contact_id: task?.contact_id ? String(task.contact_id) : '',
        due_on: task?.due_on ? String(task.due_on).slice(0, 10) : '',
        status: task?.status ?? 'Not Started',
        priority: task?.priority ?? 'Normal',
        comments: task?.comments ?? '',
        reminder_set: Boolean(task?.reminder_set),
        reminder_date: task?.reminder_date ?? '',
        reminder_time: task?.reminder_time ?? '',
    };
}

export function priorityClass(priority) {
    const value = String(priority ?? '').toLowerCase();

    if (value === 'high') {
        return 'text-danger';
    }

    if (value === 'low') {
        return 'text-success';
    }

    return 'text-text';
}
