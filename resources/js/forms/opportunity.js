export function opportunityFormData(opportunity = null) {
    return {
        name: opportunity?.name ?? '',
        account_id: opportunity?.account_id ? String(opportunity.account_id) : '',
        amount: opportunity?.amount ?? '',
        close_date: opportunity?.close_date
            ? String(opportunity.close_date).slice(0, 10)
            : '',
        stage: opportunity?.stage ?? 'Qualification',
        type: opportunity?.type ?? '',
        lead_source: opportunity?.lead_source ?? '',
        next_step: opportunity?.next_step ?? '',
        description: opportunity?.description ?? '',
        owner_id: opportunity?.owner_id ? String(opportunity.owner_id) : '',
    };
}

export function stageToneClass(stage) {
    const value = String(stage ?? '');

    if (value === 'Closed Won') {
        return 'text-success';
    }

    if (value === 'Closed Lost') {
        return 'text-danger';
    }

    if (value === 'Negotiation/Review' || value === 'Proposal/Price Quote') {
        return 'text-warning';
    }

    return 'text-text';
}
