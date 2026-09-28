export function eventFormData(event = null, defaults = null, userId = '') {
    return {
        subject: event?.subject ?? '',
        assigned_to_id: event?.assigned_to_id ? String(event.assigned_to_id) : String(userId ?? ''),
        related_type: event?.related_key ?? '',
        related_id: event?.related_id ? String(event.related_id) : '',
        contact_id: event?.contact_id ? String(event.contact_id) : '',
        all_day: event ? Boolean(event.all_day) : Boolean(defaults?.all_day),
        starts_at: event?.starts_input ?? defaults?.starts_at ?? '',
        ends_at: event?.ends_input ?? defaults?.ends_at ?? '',
        location: event?.location ?? '',
        show_time_as: event?.show_time_as ?? 'Busy',
        is_private: Boolean(event?.is_private),
        description: event?.description ?? '',
    };
}
