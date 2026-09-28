export function caseFormData(caseRecord = null) {
    return {
        contact_id: caseRecord?.contact_id ? String(caseRecord.contact_id) : '',
        account_id: caseRecord?.account_id ? String(caseRecord.account_id) : '',
        subject: caseRecord?.subject ?? '',
        description: caseRecord?.description ?? '',
        internal_comments: caseRecord?.internal_comments ?? '',
        status: caseRecord?.status && caseRecord.status !== 'Closed' ? caseRecord.status : 'New',
        priority: caseRecord?.priority ?? '',
        type: caseRecord?.type ?? '',
        origin: caseRecord?.origin ?? '',
        reason: caseRecord?.reason ?? '',
        web_email: caseRecord?.web_email ?? '',
        web_name: caseRecord?.web_name ?? '',
        web_company: caseRecord?.web_company ?? '',
        web_phone: caseRecord?.web_phone ?? '',
        owner_id: caseRecord?.owner_id ? String(caseRecord.owner_id) : '',
    };
}

export function contactLabel(contact) {
    return [contact.first_name, contact.last_name].filter(Boolean).join(' ') || `Contact #${contact.id}`;
}

export function priorityClass(priority) {
    const value = String(priority ?? '').toLowerCase();

    if (value === 'high') {
        return 'text-danger';
    }

    if (value === 'medium') {
        return 'text-warning';
    }

    if (value === 'low') {
        return 'text-success';
    }

    return 'text-text';
}
